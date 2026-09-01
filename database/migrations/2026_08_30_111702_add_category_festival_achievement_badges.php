<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('achievement_badge')->insert([
            [
                'badge_name' => 'Nature Festival Explorer',
                'description' => 'Celebrate Malaysia through nature festivals.',
                'requirement' => 'Complete 3 Nature Festival experiences.',
                'badge_image' =>
                    'images/engagement/badges/nature-festival-explorer.png',
                'criteria_type' => 'nature_festival',
                'target_count' => 3,
            ],
            [
                'badge_name' => 'Music Festival Explorer',
                'description' => 'Experience Malaysia through music festivals.',
                'requirement' => 'Complete 3 Music Festival experiences.',
                'badge_image' =>
                    'images/engagement/badges/music-festival-explorer.png',
                'criteria_type' => 'music_festival',
                'target_count' => 3,
            ],
            [
                'badge_name' => 'Sports Festival Explorer',
                'description' => 'Experience exciting Malaysian sports festivals.',
                'requirement' => 'Complete 3 Sports Festival experiences.',
                'badge_image' =>
                    'images/engagement/badges/sports-festival-explorer.png',
                'criteria_type' => 'sports_festival',
                'target_count' => 3,
            ],
        ]);

        DB::table('achievement_badge')
            ->where('criteria_type', 'all_badges')
            ->update(['target_count' => 17]);
    }

    public function down(): void
    {
        $badgeIds = DB::table('achievement_badge')
            ->whereIn('criteria_type', [
                'nature_festival',
                'music_festival',
                'sports_festival',
            ])
            ->pluck('badge_id');

        DB::table('user_achievement')
            ->whereIn('badge_id', $badgeIds)
            ->delete();

        DB::table('achievement_badge')
            ->whereIn('badge_id', $badgeIds)
            ->delete();

        DB::table('achievement_badge')
            ->where('criteria_type', 'all_badges')
            ->update(['target_count' => 14]);
    }
};