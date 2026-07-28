<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores the GCash number a graduate voluntarily submits after
        // finishing their survey, in exchange for a free-load reward.
        // is_done starts at false/0 and is only flipped by an admin from
        // Admin > Integrations once the reward has actually been sent -
        // it's a manual fulfillment checkbox, not something the user
        // controls.
        Schema::create('user_number', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('number');
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_number');
    }
};
