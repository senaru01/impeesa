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
        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20);
            $table->string('name', 120);
            $table->string('code', 4)->nullable();
            $table->unsignedTinyInteger('size')->nullable();
            $table->string('color', 40)->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedInteger('min_stock')->default(0);
            $table->unsignedInteger('purchase_price')->default(0);
            $table->unsignedInteger('selling_price')->default(0);
            $table->decimal('filament_grams', 7, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'color']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_items');
    }
};
