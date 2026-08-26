<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * These tables were added after the original bulk RLS migration
 * (2026_08_09_124526) and were left exposed to the public anon key via
 * Supabase's PostgREST API. Laravel is unaffected either way since it
 * connects as the `postgres` role, which bypasses RLS.
 */
return new class extends Migration
{
    private const TABLES = [
        'personalized_alert_sent',
        'trip_plan_items',
        'trip_plans',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE public.\"{$table}\" ENABLE ROW LEVEL SECURITY");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE public.\"{$table}\" DISABLE ROW LEVEL SECURITY");
        }
    }
};
