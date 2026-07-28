<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'faculty', 'admin'])->default('user')->after('password');
            $table->boolean('consent_given')->default(false)->after('role');
            $table->timestamp('consent_given_at')->nullable()->after('consent_given');
            $table->timestamp('identity_verified_at')->nullable()->after('consent_given_at');
            $table->boolean('is_active')->default(true)->after('identity_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'consent_given', 'consent_given_at', 'identity_verified_at', 'is_active']);
        });
    }
};
