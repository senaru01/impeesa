<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data KTP dan medis untuk kartu darurat (QR) yang dipakai tim SAR.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('nik')->nullable();
            $table->string('religion', 30)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('nationality', 30)->nullable();
            $table->text('id_card_address')->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->string('emergency_token', 64)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['emergency_token']);
            $table->dropColumn([
                'nik', 'religion', 'marital_status', 'occupation', 'nationality', 'id_card_address',
                'emergency_contact_relation', 'allergies', 'medications', 'emergency_token',
            ]);
        });
    }
};
