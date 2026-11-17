<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keycap jadi Rp1.000 untuk semua warna. Rantai digratiskan karena biayanya sudah
     * masuk ke harga base cap; modal rantai tetap tercatat supaya laba tetap benar.
     */
    public function up(): void
    {
        DB::table('store_items')
            ->where('category', 'keycap')
            ->update(['selling_price' => 1000, 'updated_at' => now()]);

        DB::table('store_items')
            ->where('category', 'accessory')
            ->where('name', 'like', 'Rantai%')
            ->update(['selling_price' => 0, 'updated_at' => now()]);
    }

    /**
     * Harga lama tidak disimpan, jadi tidak dikembalikan.
     */
    public function down(): void
    {
        //
    }
};
