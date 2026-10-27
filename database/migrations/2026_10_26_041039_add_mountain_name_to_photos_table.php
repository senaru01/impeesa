<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lokasi (nama gunung) untuk foto yang tidak terhubung ke pendakian,
     * dipakai untuk menaruh foto di peta jejak Galeri Bersama.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('mountain_name', 100)->nullable()->after('climb_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('mountain_name');
        });
    }
};
