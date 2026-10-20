<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto utama pendakian, tampil di daftar pendakian dan peta dashboard.
     */
    public function up(): void
    {
        Schema::table('climbs', function (Blueprint $table) {
            $table->foreignId('cover_photo_id')->nullable()->after('notes')->constrained('photos')->nullOnDelete();
        });

        // Pendakian yang sudah punya foto memakai foto pertamanya sebagai foto utama.
        DB::table('climbs')->orderBy('id')->each(function (object $climb) {
            $firstPhotoId = DB::table('photos')->where('climb_id', $climb->id)->orderBy('id')->value('id');

            if ($firstPhotoId !== null) {
                DB::table('climbs')->where('id', $climb->id)->update(['cover_photo_id' => $firstPhotoId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('climbs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cover_photo_id');
        });
    }
};
