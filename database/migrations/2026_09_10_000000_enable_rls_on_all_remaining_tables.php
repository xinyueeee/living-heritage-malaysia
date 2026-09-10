<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The 2026-08-09 RLS migration only listed the tables that existed on
     * that date. Tables added afterwards — search_history,
     * experience_view_history, post_save, profile_photos, sessions,
     * community_group / community_group_member, post_comment, post_like,
     * album_photo, and anything else — were never locked, so Supabase's
     * PostgREST Data API could still reach them with the public anon key.
     *
     * This migration reads every table in the public schema and enables
     * Row Level Security with no policies on each one. With no policy, the
     * anon and authenticated PostgREST roles are denied all access.
     * Laravel is unaffected: it connects as the `postgres` role, which
     * bypasses RLS regardless.
     *
     * Enabling RLS on a table that already has it is a harmless no-op, so
     * this migration is safe to re-run and safely overlaps the earlier
     * per-table migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->publicTables() as $table) {
            DB::statement("ALTER TABLE public.\"{$table}\" ENABLE ROW LEVEL SECURITY");
        }
    }

    /**
     * Reverse the migration.
     *
     * Only disables RLS on tables that have no policies (the deny-all
     * tables this migration is responsible for). Any table that later gains
     * a real policy is left untouched.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $withPolicies = collect(DB::select(
            "SELECT DISTINCT tablename FROM pg_policies WHERE schemaname = 'public'"
        ))->pluck('tablename')->all();

        foreach ($this->publicTables() as $table) {
            if (! in_array($table, $withPolicies, true)) {
                DB::statement("ALTER TABLE public.\"{$table}\" DISABLE ROW LEVEL SECURITY");
            }
        }
    }

    /**
     * @return list<string>
     */
    private function publicTables(): array
    {
        return collect(DB::select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
        ))->pluck('tablename')->all();
    }
};
