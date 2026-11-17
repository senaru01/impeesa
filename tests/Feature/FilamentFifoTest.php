<?php

namespace Tests\Feature;

use App\Enums\FilamentWasteReason;
use App\Enums\Role;
use App\Models\FilamentLot;
use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentFifoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::SuperAdmin]);
    }

    public function test_production_uses_the_oldest_roll_first_and_spills_over_to_the_next(): void
    {
        $old = FilamentLot::factory()->create(['color' => 'Hitam', 'remaining_grams' => 20, 'price' => 100000, 'purchased_on' => now()->subDays(10)]);
        $new = FilamentLot::factory()->create(['color' => 'Hitam', 'remaining_grams' => 1000, 'price' => 200000, 'purchased_on' => now()]);
        $keycap = StoreItem::factory()->keycap('A')->create(['color' => 'Hitam', 'stock' => 0, 'fixed_cost' => 0, 'filament_grams' => 3]);

        // Walau roll baru yang terpilih, roll lama tetap dihabiskan dulu.
        $this->actingAs($this->admin)
            ->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 10, 'filament_lot_id' => $new->id])
            ->assertSessionHasNoErrors('restock');

        $this->assertSame(0.0, $old->fresh()->remaining_grams);
        $this->assertSame(990.0, $new->fresh()->remaining_grams);

        // 20 g × Rp100 + 10 g × Rp200 = Rp4.000 untuk 10 pcs
        $this->assertSame(400, $keycap->fresh()->purchase_price);
        $this->assertSame($old->id, $keycap->movements()->sole()->filament_lot_id);
    }

    public function test_shortage_counts_every_roll_of_the_same_color(): void
    {
        $lot = FilamentLot::factory()->create(['color' => 'Hitam', 'remaining_grams' => 20]);
        FilamentLot::factory()->create(['color' => 'Hitam', 'remaining_grams' => 20]);
        FilamentLot::factory()->create(['color' => 'Putih', 'remaining_grams' => 1000]);
        $keycap = StoreItem::factory()->keycap('A')->create(['color' => 'Hitam', 'stock' => 0, 'filament_grams' => 3]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 13, 'filament_lot_id' => $lot->id])
            ->assertSessionHasNoErrors('restock');

        $this->post(route('admin.store.items.restock', $keycap), ['type' => 'production', 'quantity' => 1, 'filament_lot_id' => $lot->id])
            ->assertSessionHasErrors(['filament_lot_id'], errorBag: 'restock');
    }

    public function test_production_options_show_one_active_roll_per_color(): void
    {
        $old = FilamentLot::factory()->create(['color' => 'Hitam', 'brand' => 'Sunlu', 'remaining_grams' => 297, 'purchased_on' => now()->subDay()]);
        $new = FilamentLot::factory()->create(['color' => 'Hitam', 'brand' => 'Sunlu', 'remaining_grams' => 1000]);
        StoreItem::factory()->keycap('A')->create(['color' => 'Hitam', 'filament_grams' => 3]);

        $this->actingAs($this->admin)
            ->get(route('admin.store.items.index'))
            ->assertOk()
            ->assertSee('PLA Hitam · roll dipakai Sunlu sisa 297 g (+1 roll, total 1.297 g)')
            ->assertSee('value="'.$old->id.'"', false)
            ->assertDontSee('value="'.$new->id.'"', false);

        $this->get(route('admin.store.filament.index'))
            ->assertOk()
            ->assertSeeInOrder(['PLA Hitam', 'Dipakai', 'Cadangan 1']);
    }

    public function test_wasting_filament_reduces_the_roll_without_returning_cost(): void
    {
        $lot = FilamentLot::factory()->create(['remaining_grams' => 120, 'price' => 200000, 'weight_grams' => 1000]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.filament.waste'), ['filament_lot_id' => $lot->id, 'reason' => FilamentWasteReason::FailedPrint->value, 'grams' => 20])
            ->assertSessionHasNoErrors('waste')
            ->assertRedirect(route('admin.store.filament.index'));

        $this->assertSame(100.0, $lot->fresh()->remaining_grams);
        $this->assertSame(4000, $lot->wastes()->sole()->value);

        // Gram kosong = buang seluruh sisa roll.
        $this->post(route('admin.store.filament.waste'), ['filament_lot_id' => $lot->id, 'reason' => FilamentWasteReason::Discarded->value]);
        $this->assertSame(0.0, $lot->fresh()->remaining_grams);
        $this->assertSame(120.0, (float) $lot->wastes()->sum('grams'));

        $this->post(route('admin.store.filament.waste'), ['filament_lot_id' => $lot->id, 'reason' => FilamentWasteReason::Discarded->value, 'grams' => 5])
            ->assertSessionHasErrors(['filament_lot_id'], errorBag: 'waste');

        $this->get(route('admin.store.sales.index'))->assertOk()->assertSee('filament terbuang/NG 120 g');
    }

    public function test_cannot_waste_more_than_the_roll_has_left(): void
    {
        $lot = FilamentLot::factory()->create(['remaining_grams' => 50]);

        $this->actingAs($this->admin)
            ->post(route('admin.store.filament.waste'), ['filament_lot_id' => $lot->id, 'reason' => FilamentWasteReason::Discarded->value, 'grams' => 60])
            ->assertSessionHasErrors(['grams'], errorBag: 'waste');

        $this->assertSame(50.0, $lot->fresh()->remaining_grams);
    }

    public function test_base_cap_set_uses_the_price_table_when_selling_price_is_empty(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.store.items.batch'), [
                'category' => 'base_cap', 'color' => 'Hitam', 'size_from' => 1, 'size_to' => 10,
                'stock' => 0, 'min_stock' => 1, 'fixed_cost' => 0, 'purchase_price' => 1000, 'filament_grams' => 5,
            ])
            ->assertSessionHasNoErrors('batch');

        $this->assertSame(
            [9000, 10000, 11000, 12000, 15000, 16000, 17000, 18000, 18000, 18000],
            StoreItem::query()->orderBy('size')->pluck('selling_price')->all(),
        );
    }

    public function test_pos_preselects_the_free_chain_matching_the_base_color(): void
    {
        StoreItem::factory()->keycap('A')->create(['stock' => 5]);
        StoreItem::factory()->baseCap(1, 'Putih')->create(['stock' => 5]);
        StoreItem::factory()->switch()->create(['stock' => 5]);
        $black = StoreItem::factory()->create(['category' => 'accessory', 'name' => 'Rantai', 'color' => 'Hitam', 'selling_price' => 0, 'stock' => 5]);
        $white = StoreItem::factory()->create(['category' => 'accessory', 'name' => 'Rantai', 'color' => 'Putih', 'selling_price' => 0, 'stock' => 5]);

        $this->actingAs($this->admin)
            ->get(route('admin.store.pos'))
            ->assertOk()
            ->assertSee('value="'.$white->id.'" data-color="Putih" selected>Rantai · Putih (gratis)', false)
            ->assertDontSee('value="'.$black->id.'" data-color="Hitam" selected', false);
    }
}
