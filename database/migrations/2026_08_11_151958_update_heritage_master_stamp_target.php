<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('achievement_badge')
            ->where('badge_name', 'Heritage Master')
            ->update([
                'requirement' => 'Collect all 19 passport stamps.',
                'target_count' => 19,
            ]);
    }

    public function down(): void
    {
        DB::table('achievement_badge')
            ->where('badge_name', 'Heritage Master')
            ->update([
                'requirement' => 'Collect all 13 passport stamps.',
                'target_count' => 13,
            ]);
    }
};