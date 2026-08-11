<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables Supabase's PostgREST API would otherwise expose directly to
     * anyone holding the public anon key, bypassing Laravel entirely.
     *
     * @var list<string>
     */
    private const TABLES = [
        'achievement_badge',
        'album',
        'alert',
        'cache',
        'cache_locks',
        'calendar',
        'category',
        'completed_experience',
        'digital_cultural_passport',
        'experience_type',
        'experiences',
        'failed_jobs',
        'favourite',
        'feedback',
        'feedback_photo',
        'interest',
        'job_batches',
        'jobs',
        'migrations',
        'notification',
        'passport_stamp',
        'post',
        'review',
        'user_achievement',
        'user_interest',
        'user_passport_stamp',
        'users',
    ];

    /**
     * Run the migrations.
     *
     * Enables Row Level Security with no policies, which denies all access
     * to the anon/authenticated PostgREST roles by default. Laravel itself
     * is unaffected since it connects as the `postgres` role, which bypasses
     * RLS regardless.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE public.\"{$table}\" ENABLE ROW LEVEL SECURITY");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE public.\"{$table}\" DISABLE ROW LEVEL SECURITY");
        }
    }
};
