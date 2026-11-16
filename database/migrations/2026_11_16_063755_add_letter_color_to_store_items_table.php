<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keycap dicetak dua filament (AMS): warna cap dan warna huruf.
     */
    public function up(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            $table->string('letter_color', 40)->nullable()->after('color');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('letter_filament_lot_id')->nullable()->after('filament_grams')->constrained('filament_lots')->nullOnDelete();
            $table->decimal('letter_filament_grams', 9, 2)->default(0)->after('letter_filament_lot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('letter_filament_lot_id');
            $table->dropColumn('letter_filament_grams');
        });

        Schema::table('store_items', function (Blueprint $table) {
            $table->dropColumn('letter_color');
        });
    }
};
