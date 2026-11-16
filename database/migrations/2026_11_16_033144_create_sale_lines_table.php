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
        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('group_index');
            $table->string('group_label', 120);
            $table->string('description', 120);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('unit_cost');
            $table->decimal('filament_grams', 7, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
    }
};
