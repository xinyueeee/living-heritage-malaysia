<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Profile Overview page filters/joins on these foreign-key columns.
 * Postgres does not auto-index FK columns (unlike MySQL), so without
 * these the queries fall back to sequential scans as the tables grow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completed_experience', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('digital_cultural_passport', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('user_passport_stamp', function (Blueprint $table) {
            $table->index('passport_id');
        });

        Schema::table('profile_photos', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('completed_experience', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('digital_cultural_passport', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('user_passport_stamp', function (Blueprint $table) {
            $table->dropIndex(['passport_id']);
        });

        Schema::table('profile_photos', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
