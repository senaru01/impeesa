<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto Galeri Bersama kiriman member baru tampil setelah disetujui super admin.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('taken_on');
        });

        // Foto bersama yang sudah ada diunggah super admin, jadi langsung dianggap disetujui.
        DB::table('photos')->whereNull('user_id')->update(['approved_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
