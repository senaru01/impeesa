<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Enums\StoreItemCategory;
use App\Models\Sale;
use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private StoreItem $switch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::SuperAdmin]);
        $this->switch = StoreItem::factory()->switch()->create(['stock' => 50]);
        StoreItem::factory()->baseCap(5)->create(['stock' => 4]);

        foreach (['F', 'A', 'T', 'H'] as $code) {
            StoreItem::factory()->keycap($code)->create(['stock' => 10]);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function keychain(array $overrides = []): array
    {
        return [
            'text' => 'fatah',
            'keycap_color' => 'Putih',
            'base_color' => 'Hitam',
            'switch_item_id' => $this->switch->id,
            'quantity' => 1,
            ...$overrides,
        ];
    }

    private function item(StoreItemCategory $category, ?string $code = null, ?int $size = null, ?string $color = null): StoreItem
    {
        return StoreItem::query()
            ->where('category', $category)
            ->when($code, fn ($query) => $query->where('code', $code))
            ->when($size, fn ($query) => $query->where('size', $size))
            ->when($color, fn ($query) => $query->where('color', $color))
            ->sole();
    }

    public function test_members_cannot_open_the_store(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.store.pos'))
            ->assertForbidden();
    }

    public function test_keychain_uses_base_caps_and_one_switch_per_letter(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.store.cart.keychain'), $this->keychain())
            ->assertRedirect(route('admin.store.pos'))
            ->assertSessionHas('status', 'Gantungan kunci "FATAH" masuk keranjang.');

        $cart = session(Sale::CART_SESSION_KEY);
        $this->assertCount(1, $cart);
        $this->assertSame([
            $this->item(StoreItemCategory::BaseCap, size: 5)->id => 1,
            $this->item(StoreItemCategory::Keycap, 'F')->id => 1,
            $this->item(StoreItemCategory::Keycap, 'A')->id => 2,
            $this->item(StoreItemCategory::Keycap, 'T')->id => 1,
            $this->item(StoreItemCategory::Keycap, 'H')->id => 1,
            $this->switch->id => 5,
        ], $cart[0]['items']);

        // base 5 huruf Rp9.000 + 5 keycap @Rp3.000 + 5 switch @Rp2.500
        $this->get(route('admin.store.pos'))
            ->assertOk()
            ->assertSee('Rp 36.500')
            ->assertDontSee('Laba kotor')
            ->assertDontSee('Modal');
    }

    public function test_checkout_saves_sale_reduces_stock_and_prints_receipt(): void
    {
        $this->actingAs($this->admin)->post(route('admin.store.cart.keychain'), $this->keychain());

        $response = $this->post(route('admin.store.sales.store'), [
            'customer_name' => 'Budi',
            'payment_method' => 'cash',
            'paid_amount' => 50000,
        ]);

        $sale = Sale::sole();
        $response->assertRedirect(route('admin.store.sales.show', $sale));
        $this->assertSame(36500, $sale->total);
        $this->assertSame(13500, $sale->change());
        $this->assertSame(5 * 800 + 2500 + 5 * 1200, $sale->total_cost);
        $this->assertNull(session(Sale::CART_SESSION_KEY));

        $this->assertSame(3, $this->item(StoreItemCategory::BaseCap, size: 5)->stock);
        $this->assertSame(8, $this->item(StoreItemCategory::Keycap, 'A')->stock);
        $this->assertSame(9, $this->item(StoreItemCategory::Keycap, 'F')->stock);
        $this->assertSame(45, $this->switch->fresh()->stock);
        $this->assertSame(6, $sale->movements()->where('type', StockMovementType::Sale)->count());

        $this->get(route('admin.store.sales.show', $sale))->assertOk()->assertSee('Budi');
        $this->get(route('admin.store.items.edit', $this->item(StoreItemCategory::Keycap, 'A')))
            ->assertOk()
            ->assertSee('Stok sekarang: 8')
            ->assertSee($sale->number);

        $this->get(route('admin.store.sales.receipt', $sale))
            ->assertOk()
            ->assertSee($sale->number)
            ->assertSee('Gantungan kunci &quot;FATAH&quot;', false)
            ->assertSee('36.500')
            ->assertSee('13.500');
    }

    public function test_letters_can_use_different_keycap_colors(): void
    {
        StoreItem::factory()->keycap('A', 'Merah')->create(['stock' => 1]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.cart.keychain'), $this->keychain(['letter_colors' => [1 => 'Merah']]))
            ->assertSessionHasNoErrors('keychain');

        $items = session(Sale::CART_SESSION_KEY)[0]['items'];
        $this->assertSame(1, $items[$this->item(StoreItemCategory::Keycap, 'A', color: 'Merah')->id]);
        $this->assertSame(1, $items[$this->item(StoreItemCategory::Keycap, 'A', color: 'Putih')->id]);
    }

    public function test_keychain_is_rejected_when_stock_or_item_is_missing(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.store.cart.keychain'), $this->keychain(['text' => 'FATAHX']))
            ->assertSessionHasErrors(['base_color', 'text'], errorBag: 'keychain');

        $this->post(route('admin.store.cart.keychain'), $this->keychain(['quantity' => 5]))
            ->assertSessionHasErrors(['stock'], errorBag: 'keychain');

        $this->assertNull(session(Sale::CART_SESSION_KEY));
    }

    public function test_checkout_rechecks_stock_and_payment(): void
    {
        $this->actingAs($this->admin)->post(route('admin.store.cart.keychain'), $this->keychain());

        $this->post(route('admin.store.sales.store'), ['payment_method' => 'cash', 'paid_amount' => 10000])
            ->assertSessionHasErrors(['paid_amount'], errorBag: 'checkout');

        $this->item(StoreItemCategory::Keycap, 'A')->update(['stock' => 1]);

        $this->post(route('admin.store.sales.store'), ['payment_method' => 'qris'])
            ->assertSessionHasErrors(['cart'], errorBag: 'checkout');

        $this->assertDatabaseEmpty('sales');
        $this->assertSame(4, $this->item(StoreItemCategory::BaseCap, size: 5)->stock);
    }

    public function test_batch_creates_keycap_set_and_base_caps_with_step_pricing(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.store.items.batch'), [
                'category' => 'keycap',
                'color' => 'biru',
                'stock' => 5,
                'min_stock' => 2,
                'purchase_price' => 700,
                'fixed_cost' => 0,
                'selling_price' => 3000,
                'filament_grams' => 2.4,
            ])
            ->assertSessionHas('status', '36 item dibuat.');

        $this->post(route('admin.store.items.batch'), [
            'category' => 'base_cap',
            'color' => 'Biru',
            'size_from' => 1,
            'size_to' => 10,
            'stock' => 0,
            'min_stock' => 2,
            'purchase_price' => 1000,
            'fixed_cost' => 0,
            'selling_price' => 5000,
            'filament_grams' => 4,
            'selling_price_step' => 1000,
            'filament_grams_step' => 1.5,
        ])->assertSessionHas('status', '10 item dibuat.');

        $this->assertSame(5, $this->item(StoreItemCategory::Keycap, 'Z', color: 'Biru')->stock);
        $base = $this->item(StoreItemCategory::BaseCap, size: 5, color: 'Biru');
        $this->assertSame(9000, $base->selling_price);
        $this->assertSame(10.0, $base->filament_grams);
        $this->assertSame(36, StoreItem::query()->where('color', 'Biru')->where('category', StoreItemCategory::Keycap)->count());
    }

    public function test_production_is_counted_as_cash_out(): void
    {
        $base = $this->item(StoreItemCategory::BaseCap, size: 5);

        $this->actingAs($this->admin)
            ->post(route('admin.store.items.restock', $base), ['type' => 'production', 'quantity' => 10, 'unit_cost' => 2000])
            ->assertSessionHasNoErrors('restock');

        $this->assertSame(14, $base->fresh()->stock);

        $this->post(route('admin.store.items.restock', $base), ['type' => 'adjustment', 'quantity' => -20])
            ->assertSessionHasErrors(['quantity'], errorBag: 'restock');

        $this->post(route('admin.store.cart.keychain'), $this->keychain());
        $this->post(route('admin.store.sales.store'), ['payment_method' => 'cash']);

        $this->get(route('admin.store.sales.index'))
            ->assertOk()
            ->assertSee('Rp 36.500')
            ->assertSee('Rp 20.000')
            ->assertSee('Rp 16.500');
    }

    public function test_admin_creates_item_with_generated_name_and_initial_stock(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.store.items.store'), [
                'category' => 'keycap',
                'code' => 'q',
                'color' => 'hijau',
                'stock' => 6,
                'min_stock' => 2,
                'purchase_price' => 700,
                'fixed_cost' => 0,
                'selling_price' => 3000,
                'filament_grams' => 2.5,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.store.items.index'));

        $item = $this->item(StoreItemCategory::Keycap, 'Q', color: 'Hijau');
        $this->assertSame('Keycap Q · Hijau', $item->label());
        $this->assertSame(6, $item->movements()->sole()->quantity);
        $this->get(route('admin.store.items.index'))->assertOk()->assertSee('Keycap Q · Hijau');
    }

    public function test_bought_switch_needs_no_printing_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.store.items.store'), [
                'category' => 'switch',
                'name' => 'Switch Gateron Red',
                'stock' => 30,
                'min_stock' => 10,
                'purchase_price' => 1300,
                'selling_price' => 2500,
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.store.items.index'));

        $switch = StoreItem::query()->where('name', 'Switch Gateron Red')->sole();
        $this->assertSame(0, $switch->fixed_cost);
        $this->assertSame(0.0, $switch->filament_grams);
        $this->assertSame(30 * 1300, $switch->movements()->sole()->cashOut());

        $this->get(route('admin.store.items.create'))
            ->assertOk()
            ->assertSee('data-show-for="keycap"', false)
            ->assertSee('data-show-for="base_cap"', false);
    }
}
