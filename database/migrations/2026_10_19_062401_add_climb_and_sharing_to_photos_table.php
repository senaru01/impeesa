<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * climb_id menautkan foto ke catatan pendakian (untuk peta dashboard).
     * shared_at menandai foto yang dibagikan ke Galeri Bersama, termasuk foto
     * pribadi yang tetap tinggal di galeri pemiliknya.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->foreignId('climb_id')->nullable()->after('uploaded_by')->constrained()->nullOnDelete();
            $table->timestamp('shared_at')->nullable()->after('taken_on');
            $table->index(['shared_at', 'approved_at']);
        });

        // Sebelumnya foto Galeri Bersama ditandai dengan user_id kosong.
        DB::table('photos')->whereNull('user_id')->update(['shared_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['shared_at', 'approved_at']);
            $table->dropColumn('shared_at');
            $table->dropConstrainedForeignId('climb_id');
        });
    }
};
