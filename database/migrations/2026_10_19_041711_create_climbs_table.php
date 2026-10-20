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
        Schema::create('climbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mountain_name');
            $table->string('route')->nullable();
            $table->unsignedSmallInteger('elevation')->nullable();
            $table->date('started_on');
            $table->date('finished_on')->nullable();
            $table->string('status')->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('climbs');
    }
};
