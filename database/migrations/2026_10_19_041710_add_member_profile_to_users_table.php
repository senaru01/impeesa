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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('member_number')->nullable()->unique()->after('is_active');
            $table->string('nickname')->nullable()->after('name');
            $table->string('position')->nullable();
            $table->date('joined_on')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('blood_type', 3)->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['member_number']);
            $table->dropColumn([
                'role', 'is_active', 'member_number', 'nickname', 'position', 'joined_on', 'phone', 'gender',
                'birth_place', 'birth_date', 'blood_type', 'city', 'address', 'emergency_contact_name',
                'emergency_contact_phone', 'medical_notes', 'bio', 'avatar_path',
            ]);
        });
    }
};
