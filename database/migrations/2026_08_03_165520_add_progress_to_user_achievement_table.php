<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_achievement', function (Blueprint $table) {
            $table->unsignedInteger('current_progress')->default(0);
            $table->boolean('is_unlocked')->default(false);
            $table->unique(
                ['user_id', 'badge_id'],
                'user_achievement_user_badge_unique'
            );
        });

        DB::statement(
            'ALTER TABLE user_achievement
             ALTER COLUMN unlocked_date DROP DEFAULT'
        );

        DB::statement(
            'ALTER TABLE user_achievement
             ALTER COLUMN unlocked_date DROP NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('user_achievement', function (Blueprint $table) {
            $table->dropUnique('user_achievement_user_badge_unique');
            $table->dropColumn([
                'current_progress',
                'is_unlocked',
            ]);
        });

        DB::statement(
            'ALTER TABLE user_achievement
             ALTER COLUMN unlocked_date SET DEFAULT CURRENT_TIMESTAMP'
        );

        DB::statement(
            'UPDATE user_achievement
             SET unlocked_date = CURRENT_TIMESTAMP
             WHERE unlocked_date IS NULL'
        );

        DB::statement(
            'ALTER TABLE user_achievement
             ALTER COLUMN unlocked_date SET NOT NULL'
        );
    }
};