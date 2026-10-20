<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto tanpa pemilik (user_id null) masuk Galeri Bersama; uploaded_by mencatat siapa yang mengunggah.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('uploaded_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
