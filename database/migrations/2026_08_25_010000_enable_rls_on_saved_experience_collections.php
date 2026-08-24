<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE = 'public.saved_experience_collections';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE '.self::TABLE.' ENABLE ROW LEVEL SECURITY');

        DB::statement('CREATE POLICY saved_experience_collections_select_own
            ON '.self::TABLE.' FOR SELECT TO authenticated
            USING (user_id = auth.uid())');

        DB::statement('CREATE POLICY saved_experience_collections_insert_own
            ON '.self::TABLE.' FOR INSERT TO authenticated
            WITH CHECK (user_id = auth.uid())');

        DB::statement('CREATE POLICY saved_experience_collections_update_own
            ON '.self::TABLE.' FOR UPDATE TO authenticated
            USING (user_id = auth.uid())
            WITH CHECK (user_id = auth.uid())');

        DB::statement('CREATE POLICY saved_experience_collections_delete_own
            ON '.self::TABLE.' FOR DELETE TO authenticated
            USING (user_id = auth.uid())');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS saved_experience_collections_select_own ON '.self::TABLE);
        DB::statement('DROP POLICY IF EXISTS saved_experience_collections_insert_own ON '.self::TABLE);
        DB::statement('DROP POLICY IF EXISTS saved_experience_collections_update_own ON '.self::TABLE);
        DB::statement('DROP POLICY IF EXISTS saved_experience_collections_delete_own ON '.self::TABLE);
        DB::statement('ALTER TABLE '.self::TABLE.' DISABLE ROW LEVEL SECURITY');
    }
};
