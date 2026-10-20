<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil singkat anggota yang dipilih super admin untuk tampil di web utama.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_place', 100)->nullable()->after('bio');
            $table->timestamp('profile_featured_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['profile_featured_at']);
            $table->dropColumn(['signature_place', 'profile_featured_at']);
        });
    }
};
