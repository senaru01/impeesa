<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['number', 'user_id', 'customer_name', 'payment_method', 'total', 'total_cost', 'paid_amount', 'filament_grams', 'notes'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * Kunci session tempat keranjang kasir disimpan.
     */
    public const CART_SESSION_KEY = 'store.cart';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'total' => 'integer',
            'total_cost' => 'integer',
            'paid_amount' => 'integer',
            'filament_grams' => 'float',
        ];
    }

    /**
     * Teks QRIS dengan nominal transaksi ini, atau null bila bukan QRIS / QRIS toko belum diisi.
     */
    public function qrisPayload(): ?string
    {
        $staticPayload = config('services.qris.payload');

        if ($this->payment_method !== PaymentMethod::Qris || blank($staticPayload) || $this->total <= 0) {
            return null;
        }

        return self::dynamicQris($staticPayload, $this->total);
    }

    /**
     * Gambar QR (SVG) dari QRIS bernominal, siap ditaruh di struk/layar.
     */
    public function qrisSvg(): ?string
    {
        $payload = $this->qrisPayload();

        if ($payload === null) {
            return null;
        }

        return (new QRCode(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'quietzoneSize' => 2,
            'cssClass' => 'qris-code',
        ])))->render($payload);
    }

    /**
     * Ubah QRIS statis jadi QRIS dinamis bernominal (standar EMVCo): tag 01 jadi "12",
     * tag 54 berisi nominal, lalu CRC16 (tag 63) dihitung ulang.
     */
    public static function dynamicQris(string $staticPayload, int $amount): string
    {
        $fields = [];
        $offset = 0;
        $payload = trim($staticPayload);

        while ($offset + 4 <= strlen($payload)) {
            $tag = substr($payload, $offset, 2);
            $length = (int) substr($payload, $offset + 2, 2);
            $fields[] = [$tag, substr($payload, $offset + 4, $length)];
            $offset += 4 + $length;
        }

        $result = '';
        $amountAdded = false;

        foreach ($fields as [$tag, $value]) {
            if ($tag === '54' || $tag === '63') {
                continue;
            }

            if (! $amountAdded && (int) $tag > 54) {
                $result .= self::qrisField('54', (string) $amount);
                $amountAdded = true;
            }

            $result .= self::qrisField($tag, $tag === '01' ? '12' : $value);
        }

        if (! $amountAdded) {
            $result .= self::qrisField('54', (string) $amount);
        }

        $result .= '6304';

        return $result.self::crc16($result);
    }

    private static function qrisField(string $tag, string $value): string
    {
        return $tag.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    /**
     * CRC16-CCITT (poly 0x1021, awal 0xFFFF) sesuai spesifikasi QRIS.
     */
    public static function crc16(string $data): string
    {
        $crc = 0xFFFF;

        foreach (str_split($data) as $character) {
            $crc ^= ord($character) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return sprintf('%04X', $crc);
    }

    public function profit(): int
    {
        return $this->total - $this->total_cost;
    }

    public function change(): int
    {
        return max(0, $this->paid_amount - $this->total);
    }

    /**
     * Baris penjualan dikelompokkan per produk (mis. satu gantungan kunci).
     *
     * @return Collection<int, array{label: string, lines: EloquentCollection<int, SaleLine>, total: int}>
     */
    public function groupedLines(): Collection
    {
        return $this->lines->groupBy('group_index')->map(fn (EloquentCollection $lines) => [
            'label' => $lines->first()->group_label,
            'lines' => $lines,
            'total' => $lines->sum(fn (SaleLine $line) => $line->subtotal()),
        ])->values();
    }

    /**
     * Hitung isi keranjang memakai harga & stok terbaru.
     *
     * @param  array<int, array{label: string, items: array<int, int>}>  $cart
     * @param  EloquentCollection<int, StoreItem>|null  $items  item yang sudah dimuat, di-key berdasarkan id
     * @return array{groups: list<array{index: int, label: string, lines: list<array{item: StoreItem, quantity: int, subtotal: int}>, total: int}>, total: int, cost: int, filament: float, demand: array<int, int>, shortages: list<string>}
     */
    public static function summarizeCart(array $cart, ?EloquentCollection $items = null): array
    {
        $items ??= StoreItem::query()->whereIn('id', self::cartItemIds($cart))->get()->keyBy('id');

        $groups = [];
        $demand = [];
        $shortages = [];
        $total = 0;
        $cost = 0;
        $filament = 0.0;

        foreach ($cart as $index => $entry) {
            $lines = [];

            foreach ($entry['items'] as $itemId => $quantity) {
                $item = $items->get($itemId);

                if (! $item) {
                    $shortages[] = "Salah satu item di \"{$entry['label']}\" sudah dihapus dari daftar.";

                    continue;
                }

                $lines[] = ['item' => $item, 'quantity' => $quantity, 'subtotal' => $quantity * $item->selling_price];
                $demand[$itemId] = ($demand[$itemId] ?? 0) + $quantity;
                $total += $quantity * $item->selling_price;
                $cost += $quantity * $item->purchase_price;
                $filament += $quantity * $item->filament_grams;
            }

            $groups[] = [
                'index' => $index,
                'label' => $entry['label'],
                'lines' => $lines,
                'total' => array_sum(array_column($lines, 'subtotal')),
            ];
        }

        foreach ($demand as $itemId => $quantity) {
            $item = $items->get($itemId);

            if (! $item->is_active) {
                $shortages[] = "{$item->label()} sedang nonaktif.";
            } elseif ($item->stock < $quantity) {
                $shortages[] = "Stok {$item->label()} kurang: butuh {$quantity}, sisa {$item->stock}.";
            }
        }

        return [
            'groups' => $groups,
            'total' => $total,
            'cost' => $cost,
            'filament' => round($filament, 2),
            'demand' => $demand,
            'shortages' => array_values(array_unique($shortages)),
        ];
    }

    /**
     * Simpan transaksi dari keranjang, kurangi stok, dan catat mutasi stok.
     *
     * @param  array<int, array{label: string, items: array<int, int>}>  $cart
     * @param  array{customer_name?: string|null, payment_method: string, paid_amount?: int|null, notes?: string|null}  $payment
     *
     * @throws ValidationException
     */
    public static function checkout(User $cashier, array $cart, array $payment): self
    {
        return DB::transaction(function () use ($cashier, $cart, $payment) {
            $items = StoreItem::query()
                ->whereIn('id', self::cartItemIds($cart))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $summary = self::summarizeCart($cart, $items);

            if ($summary['demand'] === []) {
                throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.'])->errorBag('checkout');
            }

            if ($summary['shortages'] !== []) {
                throw ValidationException::withMessages(['cart' => $summary['shortages']])->errorBag('checkout');
            }

            $paidAmount = $payment['paid_amount'] ?? $summary['total'];

            if ($paidAmount < $summary['total']) {
                throw ValidationException::withMessages(['paid_amount' => 'Uang dibayar kurang dari total belanja.'])->errorBag('checkout');
            }

            $sale = self::create([
                'number' => self::nextNumber(),
                'user_id' => $cashier->id,
                'customer_name' => $payment['customer_name'] ?? null,
                'payment_method' => $payment['payment_method'],
                'total' => $summary['total'],
                'total_cost' => $summary['cost'],
                'paid_amount' => $paidAmount,
                'filament_grams' => $summary['filament'],
                'notes' => $payment['notes'] ?? null,
            ]);

            foreach ($summary['groups'] as $group) {
                foreach ($group['lines'] as $line) {
                    $sale->lines()->create([
                        'store_item_id' => $line['item']->id,
                        'group_index' => $group['index'],
                        'group_label' => $group['label'],
                        'description' => $line['item']->label(),
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['item']->selling_price,
                        'unit_cost' => $line['item']->purchase_price,
                        'filament_grams' => $line['item']->filament_grams,
                    ]);
                }
            }

            foreach ($summary['demand'] as $itemId => $quantity) {
                $item = $items->get($itemId);
                $item->decrement('stock', $quantity);

                $sale->movements()->create([
                    'store_item_id' => $itemId,
                    'user_id' => $cashier->id,
                    'type' => StockMovementType::Sale,
                    'quantity' => -$quantity,
                    'unit_cost' => $item->purchase_price,
                ]);
            }

            return $sale;
        });
    }

    /**
     * Nomor struk harian, mis. IMP-261116-003.
     */
    public static function nextNumber(): string
    {
        $prefix = 'IMP-'.now()->format('ymd').'-';
        $last = self::query()->where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array{label: string, items: array<int, int>}>  $cart
     * @return list<int>
     */
    public static function cartItemIds(array $cart): array
    {
        return collect($cart)->flatMap(fn (array $entry) => array_keys($entry['items']))->unique()->values()->all();
    }

    /**
     * @return HasMany<SaleLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class)->orderBy('group_index')->orderBy('id');
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
