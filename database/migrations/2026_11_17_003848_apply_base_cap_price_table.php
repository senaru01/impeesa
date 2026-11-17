<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Harga jual base cap baru (subsidi silang). Disalin di sini supaya migrasi tidak
     * berubah bila tabel harga di model diganti lagi nanti.
     *
     * @var array<int, int>
     */
    private const PRICES = [1 => 9000, 2 => 10000, 3 => 11000, 4 => 12000, 5 => 15000, 6 => 16000, 7 => 17000, 8 => 18000, 9 => 18000, 10 => 18000];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::PRICES as $size => $price) {
            DB::table('store_items')
                ->where('category', 'base_cap')
                ->where('size', $size)
                ->update(['selling_price' => $price, 'updated_at' => now()]);
        }
    }

    /**
     * Harga lama tidak disimpan, jadi tidak dikembalikan.
     */
    public function down(): void
    {
        //
    }
};
