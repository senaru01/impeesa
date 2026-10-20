<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cerita publik bisa dibaca semua member; hanya cerita publik yang boleh tampil di beranda.
     */
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('cover_path')->index();
        });

        // Cerita yang sudah tampil di beranda memang sudah publik.
        DB::table('stories')->whereNotNull('featured_at')->update(['is_public' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
