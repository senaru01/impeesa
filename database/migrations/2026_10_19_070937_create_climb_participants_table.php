<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anggota yang diajak ke sebuah pendakian. Saat ajakan disetujui, peserta mendapat
     * salinan pendakian di catatannya sendiri (joined_climb_id).
     */
    public function up(): void
    {
        Schema::create('climb_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('climb_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('joined_climb_id')->nullable()->constrained('climbs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['climb_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('climb_participants');
    }
};
