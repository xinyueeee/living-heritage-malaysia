<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievement', function (Blueprint $table) {
            $table->id('user_achievement_id');
            $table->uuid('user_id');
            $table->foreignId('badge_id')->constrained('achievement_badge', 'badge_id');
            $table->timestamp('unlocked_date')->useCurrent();
 
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('user_achievement');
    }
};