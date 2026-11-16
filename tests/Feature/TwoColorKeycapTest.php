<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StoreItemCategory;
use App\Models\FilamentLot;
use App\Models\Sale;
use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoColorKeycapTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::SuperAdmin]);
    }

    public function test_two_color_keycap_takes_five_percent_from_the_letter_roll(): void
    {
        $capLot = FilamentLot::factory()->create(['color' => 'Putih', 'weight_grams' => 1000, 'remaining_grams' => 1000, 'price' => 200000]);
        $letterLot = FilamentLot::factory()->create(['color' => 'Hitam', 'weight_grams' => 1000, 'remaining_grams' => 1000, 'price' => 400000]);
        $keycap = StoreItem::factory()->keycap('A', 'Putih', 'Hitam')->create(['stock' => 0, 'fixed_cost' => 300, 'filament_grams' => 4, 'purchase_price' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.items.restock', $keycap), [
                'type' => 'production',
                'quantity' => 10,
                'filament_lot_id' => $capLot->id,
                'letter_filament_lot_id' => $letterLot->id,
            ])
            ->assertSessionHasNoErrors('restock');

        // Rp300 + 3,8 g × Rp200/g + 0,2 g × Rp400/g = Rp1.140 per pcs
        $this->assertSame(1140, $keycap->fresh()->purchase_price);
        $this->assertSame(962.0, $capLot->fresh()->remaining_grams);
        $this->assertSame(998.0, $letterLot->fresh()->remaining_grams);

        $movement = $keycap->movements()->sole();
        $this->assertSame(38.0, $movement->filament_grams);
        $this->assertSame(2.0, $movement->letter_filament_grams);
        $this->assertSame($letterLot->id, $movement->letter_filament_lot_id);
        $this->assertSame('Keycap A · Putih, huruf Hitam', $keycap->label());

        $this->delete(route('admin.store.filament.destroy', $letterLot));
        $this->assertModelExists($letterLot);
    }

    public function test_batch_creates_two_color_keycaps_and_checks_both_rolls(): void
    {
        $capLot = FilamentLot::factory()->create(['color' => 'Putih', 'weight_grams' => 1000, 'remaining_grams' => 1000, 'price' => 200000]);
        $letterLot = FilamentLot::factory()->create(['color' => 'Hitam', 'weight_grams' => 1000, 'remaining_grams' => 1, 'price' => 400000]);
        $payload = [
            'category' => StoreItemCategory::Keycap->value,
            'color' => 'putih',
            'letter_color' => 'hitam',
            'codes' => 'AB',
            'stock' => 5,
            'min_stock' => 2,
            'fixed_cost' => 300,
            'selling_price' => 3000,
            'filament_grams' => 4,
            'filament_lot_id' => $capLot->id,
            'letter_filament_lot_id' => $letterLot->id,
        ];

        // Huruf butuh 2 × 5 × 0,2 g = 2 g, roll hitam hanya sisa 1 g.
        $this->actingAs($this->admin)
            ->post(route('admin.store.items.batch'), $payload)
            ->assertSessionHasErrors(['filament_lot_id'], errorBag: 'batch');

        $letterLot->update(['remaining_grams' => 1000]);
        $this->post(route('admin.store.items.batch'), $payload)->assertSessionHasNoErrors('batch');

        $this->assertSame(2, StoreItem::query()->where('color', 'Putih')->where('letter_color', 'Hitam')->count());
        $this->assertSame(998.0, $letterLot->fresh()->remaining_grams);
        $this->assertSame(1140, StoreItem::query()->where('code', 'A')->sole()->purchase_price);

        // Kombinasi warna lain dengan badan yang sama tetap boleh dibuat.
        $this->post(route('admin.store.items.batch'), [...$payload, 'letter_color' => 'Merah', 'filament_lot_id' => null, 'purchase_price' => 900, 'stock' => 0])
            ->assertSessionHasNoErrors('batch');
        $this->assertSame(4, StoreItem::query()->where('category', StoreItemCategory::Keycap)->count());
    }

    public function test_printing_from_a_roll_requires_filament_grams(): void
    {
        $lot = FilamentLot::factory()->create(['remaining_grams' => 1000]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.items.batch'), [
                'category' => StoreItemCategory::Keycap->value,
                'color' => 'Ice Blue',
                'letter_color' => 'Hitam',
                'codes' => 'A',
                'stock' => 5,
                'min_stock' => 3,
                'fixed_cost' => 80,
                'selling_price' => 1500,
                'filament_grams' => 0,
                'filament_lot_id' => $lot->id,
            ])
            ->assertSessionHasErrors(['filament_grams'], errorBag: 'batch');
        $this->assertSame(0, StoreItem::query()->count());

        $keycap = StoreItem::factory()->keycap('A', 'Ice Blue', 'Hitam')->create(['filament_grams' => 0]);
        $this->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 5, 'filament_lot_id' => $lot->id])
            ->assertSessionHasErrors(['filament_lot_id'], errorBag: 'restock');

        $keycap->update(['filament_grams' => 4]);
        $this->get(route('admin.store.items.index'))
            ->assertOk()
            ->assertSee('3,8 g Ice Blue')
            ->assertSee('0,2 g Hitam')
            ->assertSee('m-table-wrap--sticky', false);
    }

    public function test_cashier_mixes_keycap_color_combinations_per_letter(): void
    {
        $switch = StoreItem::factory()->switch()->create();
        StoreItem::factory()->baseCap(2)->create();
        $whiteBlack = StoreItem::factory()->keycap('O', 'Putih', 'Hitam')->create();
        $redWhite = StoreItem::factory()->keycap('K', 'Merah', 'Putih')->create();
        StoreItem::factory()->keycap('K', 'Putih', 'Hitam')->create();

        $this->actingAs($this->admin)
            ->get(route('admin.store.pos'))
            ->assertOk()
            ->assertSee('Putih, huruf Hitam')
            ->assertSee('Merah, huruf Putih');

        $this->post(route('admin.store.cart.keychain'), [
            'text' => 'ok',
            'keycap_color' => 'Putih|Hitam',
            'letter_colors' => [1 => 'Merah|Putih'],
            'base_color' => 'Hitam',
            'switch_item_id' => $switch->id,
            'quantity' => 1,
        ])->assertSessionHasNoErrors('keychain');

        $items = session(Sale::CART_SESSION_KEY)[0]['items'];
        $this->assertSame(1, $items[$whiteBlack->id]);
        $this->assertSame(1, $items[$redWhite->id]);

        $this->post(route('admin.store.cart.keychain'), [
            'text' => 'ok',
            'keycap_color' => 'Merah|Putih',
            'base_color' => 'Hitam',
            'switch_item_id' => $switch->id,
            'quantity' => 1,
        ])->assertSessionHasErrors(['text' => 'Keycap "O" warna Merah, huruf Putih belum ada di daftar item.'], errorBag: 'keychain');
    }
}
