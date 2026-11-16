<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Enums\StoreItemCategory;
use App\Models\FilamentLot;
use App\Models\Sale;
use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentCostingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::SuperAdmin]);
    }

    public function test_buying_filament_rolls_is_recorded_as_cash_out(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.store.filament.store'), [
                'material' => 'PLA',
                'color' => 'putih',
                'quantity' => 2,
                'weight_grams' => 1000,
                'price' => 180000,
                'purchased_on' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.store.filament.index'));

        $this->assertSame(2, FilamentLot::query()->where('color', 'Putih')->where('remaining_grams', 1000)->count());

        $this->get(route('admin.store.sales.index'))
            ->assertOk()
            ->assertSee('Rp 360.000')
            ->assertSee('-Rp 360.000');

        $this->get(route('admin.store.filament.index'))
            ->assertOk()
            ->assertSee('Rp 180,0')
            ->assertSee('name="quantity"', false)
            ->assertDontSee('name="remaining_grams"', false);
    }

    public function test_production_from_a_roll_computes_cost_and_uses_filament(): void
    {
        $lot = FilamentLot::factory()->create(['weight_grams' => 1000, 'remaining_grams' => 1000, 'price' => 200000]);
        $keycap = StoreItem::factory()->keycap('A')->create(['stock' => 0, 'fixed_cost' => 300, 'filament_grams' => 2.5, 'purchase_price' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 10, 'filament_lot_id' => $lot->id])
            ->assertSessionHasNoErrors('restock');

        // Rp300 biaya tetap + 2,5 g × Rp200/g = Rp800 per pcs
        $keycap->refresh();
        $this->assertSame(10, $keycap->stock);
        $this->assertSame(800, $keycap->purchase_price);
        $this->assertSame(975.0, $lot->fresh()->remaining_grams);

        $movement = $keycap->movements()->sole();
        $this->assertSame(25.0, $movement->filament_grams);
        $this->assertSame(0, $movement->cashOut());

        $this->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 1000, 'filament_lot_id' => $lot->id])
            ->assertSessionHasErrors(['filament_lot_id'], errorBag: 'restock');
    }

    public function test_cash_flow_counts_filament_and_bought_goods_but_not_printing(): void
    {
        $lot = FilamentLot::factory()->create(['price' => 200000]);
        $keycap = StoreItem::factory()->keycap('A')->create(['stock' => 0, 'fixed_cost' => 300, 'filament_grams' => 2.5]);
        $switch = StoreItem::factory()->switch()->create(['stock' => 0]);

        $this->actingAs($this->admin);
        $this->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 10, 'filament_lot_id' => $lot->id]);
        $this->post(route('admin.store.items.restock', $switch), ['type' => 'production', 'quantity' => 20, 'unit_cost' => 1500]);

        $this->get(route('admin.store.sales.index'))
            ->assertOk()
            ->assertSee('Rp 230.000')
            ->assertSee('barang jadi Rp 30.000')
            ->assertSee('-Rp 230.000');
    }

    public function test_batch_from_a_roll_sets_cost_and_deducts_grams(): void
    {
        $lot = FilamentLot::factory()->create(['weight_grams' => 1000, 'remaining_grams' => 1000, 'price' => 150000]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.items.batch'), [
                'category' => 'keycap',
                'color' => 'Putih',
                'codes' => 'ABC',
                'stock' => 4,
                'min_stock' => 1,
                'fixed_cost' => 200,
                'selling_price' => 3000,
                'filament_grams' => 2,
                'filament_lot_id' => $lot->id,
            ])
            ->assertSessionHas('status', '3 item dibuat.');

        // Rp200 + 2 g × Rp150/g
        $this->assertSame([500, 500, 500], StoreItem::query()->pluck('purchase_price')->all());
        $this->assertSame(976.0, $lot->fresh()->remaining_grams);

        $this->post(route('admin.store.items.batch'), [
            'category' => 'keycap', 'color' => 'Putih', 'codes' => 'XYZ', 'stock' => 200, 'min_stock' => 1,
            'fixed_cost' => 0, 'selling_price' => 3000, 'filament_grams' => 2, 'filament_lot_id' => $lot->id,
        ])->assertSessionHasErrors(['filament_lot_id'], errorBag: 'batch');
    }

    public function test_used_filament_roll_cannot_be_deleted(): void
    {
        $lot = FilamentLot::factory()->create();
        $keycap = StoreItem::factory()->keycap('A')->create(['stock' => 0]);
        $this->actingAs($this->admin)->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 2, 'filament_lot_id' => $lot->id]);

        $this->delete(route('admin.store.filament.destroy', $lot));

        $this->assertModelExists($lot);
    }

    public function test_bulk_reset_and_delete_items(): void
    {
        $items = StoreItem::factory()->count(3)->create(['stock' => 7]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.items.bulk'), ['action' => 'reset_stock', 'ids' => [$items[0]->id, $items[1]->id]])
            ->assertSessionHas('status', 'Stok 2 item direset ke 0.');

        $this->assertSame([0, 0, 7], StoreItem::query()->orderBy('id')->pluck('stock')->all());
        $this->assertSame(-7, $items[0]->movements()->where('type', StockMovementType::Adjustment)->sole()->quantity);

        $this->post(route('admin.store.items.bulk'), ['action' => 'delete'])
            ->assertSessionHasErrors(['ids'], errorBag: 'bulk');

        // "Semua" mengikuti filter: hanya keycap yang terhapus, switch tetap ada.
        $switch = StoreItem::factory()->switch()->create();
        StoreItem::factory()->keycap('A')->create();
        StoreItem::factory()->keycap('B')->create();

        $this->get(route('admin.store.items.index', ['kategori' => 'keycap']))->assertSee('Semua hasil filter (2)');
        $this->post(route('admin.store.items.bulk'), ['action' => 'delete', 'all_items' => 1, 'kategori' => 'keycap', 'warna' => '', 'q' => '', 'menipis' => 0])
            ->assertSessionHas('status', '2 item dihapus. Riwayat penjualan tetap tersimpan.');
        $this->assertModelExists($switch);
        $switch->delete();

        $this->post(route('admin.store.items.bulk'), ['action' => 'delete', 'all_items' => 1])
            ->assertSessionHas('status', '3 item dihapus. Riwayat penjualan tetap tersimpan.');

        $this->assertDatabaseEmpty('store_items');
        $this->assertDatabaseEmpty('stock_movements');
    }

    public function test_deleting_a_sold_item_keeps_the_sale_history(): void
    {
        $base = StoreItem::factory()->baseCap(1)->create(['stock' => 3]);
        $keycap = StoreItem::factory()->keycap('A')->create(['stock' => 3]);
        $switch = StoreItem::factory()->switch()->create(['stock' => 3]);

        $this->actingAs($this->admin)->post(route('admin.store.cart.keychain'), [
            'text' => 'A', 'keycap_color' => 'Putih', 'base_color' => 'Hitam', 'switch_item_id' => $switch->id, 'quantity' => 1,
        ]);
        $this->post(route('admin.store.sales.store'), ['payment_method' => 'cash']);

        $this->delete(route('admin.store.items.destroy', $keycap))->assertRedirect(route('admin.store.items.index'));

        $sale = Sale::sole();
        $this->assertModelMissing($keycap);
        $this->assertSame(3, $sale->lines()->count());
        $this->get(route('admin.store.sales.receipt', $sale))->assertOk()->assertSee('Keycap A · Putih');
        $this->assertSame(StoreItemCategory::BaseCap, $base->fresh()->category);
    }
}
