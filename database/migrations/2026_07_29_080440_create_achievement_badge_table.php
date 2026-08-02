<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievement_badge', function (Blueprint $table) {
            $table->id('badge_id');
            $table->string('badge_name', 100);
            $table->text('description')->nullable();
            $table->text('requirement')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_badge');
    }
};