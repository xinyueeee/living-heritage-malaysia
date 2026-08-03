<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievement_badge', function (Blueprint $table) {
            $table->string('badge_image')->nullable();
            $table->string('criteria_type', 50);
            $table->unsignedInteger('target_count')->default(1);
            $table->unique('badge_name');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_badge', function (Blueprint $table) {
            $table->dropUnique(['badge_name']);
            $table->dropColumn([
                'badge_image',
                'criteria_type',
                'target_count',
            ]);
        });
    }
};