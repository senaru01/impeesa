<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            $table->unsignedInteger('fixed_cost')->default(0)->after('purchase_price');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('filament_lot_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->decimal('filament_grams', 9, 2)->default(0)->after('unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('filament_lot_id');
            $table->dropColumn('filament_grams');
        });

        Schema::table('store_items', function (Blueprint $table) {
            $table->dropColumn('fixed_cost');
        });
    }
};
