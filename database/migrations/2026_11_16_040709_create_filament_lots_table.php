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
        Schema::create('filament_lots', function (Blueprint $table) {
            $table->id();
            $table->string('material', 20);
            $table->string('color', 40);
            $table->string('brand', 60)->nullable();
            $table->unsignedInteger('weight_grams');
            $table->decimal('remaining_grams', 9, 2);
            $table->unsignedInteger('price');
            $table->date('purchased_on');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('purchased_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_lots');
    }
};
