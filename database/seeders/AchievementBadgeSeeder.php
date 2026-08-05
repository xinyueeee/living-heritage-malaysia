<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AchievementBadgeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('achievement_badge')->insert([

            [
                'badge_name' => 'First Footsteps',
                'description' => 'Begin your cultural journey by completing your first cultural experience.',
                'requirement' => 'Visit your first cultural experience.',
                'badge_image' => 'images/engagement/badges/first-footsteps.png',
                'criteria_type' => 'total_experiences',
                'target_count' => 1,
            ],

            [
                'badge_name' => 'Journey Begins',
                'description' => 'Continue exploring Malaysia through cultural experiences.',
                'requirement' => 'Visit 5 cultural experiences.',
                'badge_image' => 'images/engagement/badges/journey-begins.png',
                'criteria_type' => 'total_experiences',
                'target_count' => 5,
            ],

            [
                'badge_name' => 'Experienced Traveller',
                'description' => 'Become a seasoned cultural explorer.',
                'requirement' => 'Visit 10 cultural experiences.',
                'badge_image' => 'images/engagement/badges/experienced-traveller.png',
                'criteria_type' => 'total_experiences',
                'target_count' => 10,
            ],

            [
                'badge_name' => 'Creative Soul',
                'description' => 'Experience the beauty of Malaysian arts and crafts.',
                'requirement' => 'Visit 3 Arts & Crafts experiences.',
                'badge_image' => 'images/engagement/badges/creative-soul.png',
                'criteria_type' => 'arts_crafts',
                'target_count' => 3,
            ],

            [
                'badge_name' => 'Culture Enthusiast',
                'description' => 'Discover Malaysia\'s rich cultural heritage.',
                'requirement' => 'Visit 3 Arts & Culture experiences.',
                'badge_image' => 'images/engagement/badges/culture-enthusiast.png',
                'criteria_type' => 'arts_culture',
                'target_count' => 3,
            ],

            [
                'badge_name' => 'Food Trailblazer',
                'description' => 'Embark on a culinary adventure across Malaysia.',
                'requirement' => 'Visit 5 Culinary experiences.',
                'badge_image' => 'images/engagement/badges/food-trailblazer.png',
                'criteria_type' => 'culinary',
                'target_count' => 5,
            ],

            [
                'badge_name' => 'Taste of Malaysia',
                'description' => 'Experience the flavours of Malaysia.',
                'requirement' => 'Visit 5 Foods & Drinks experiences.',
                'badge_image' => 'images/engagement/badges/taste-of-malaysia.png',
                'criteria_type' => 'foods_drinks',
                'target_count' => 5,
            ],

            [
                'badge_name' => 'Festival Lover',
                'description' => 'Celebrate Malaysia\'s vibrant festivals.',
                'requirement' => 'Visit 3 Festival experiences.',
                'badge_image' => 'images/engagement/badges/festival-lover.png',
                'criteria_type' => 'festival',
                'target_count' => 3,
            ],

            [
                'badge_name' => 'Music Explorer',
                'description' => 'Enjoy Malaysia\'s musical heritage.',
                'requirement' => 'Visit 3 Music experiences.',
                'badge_image' => 'images/engagement/badges/music-explorer.png',
                'criteria_type' => 'music',
                'target_count' => 3,
            ],

            [
                'badge_name' => 'Nature Wanderer',
                'description' => 'Explore Malaysia\'s natural beauty.',
                'requirement' => 'Visit 3 Nature experiences.',
                'badge_image' => 'images/engagement/badges/nature-wanderer.png',
                'criteria_type' => 'nature',
                'target_count' => 3,
            ],

            [
                'badge_name' => 'Wildlife Protector',
                'description' => 'Discover Malaysia\'s amazing wildlife.',
                'requirement' => 'Visit 3 Wildlife experiences.',
                'badge_image' => 'images/engagement/badges/wildlife-protector.png',
                'criteria_type' => 'wildlife',
                'target_count' => 3,
            ],

            [
                'badge_name' => 'Sports Adventurer',
                'description' => 'Take part in exciting sports experiences.',
                'requirement' => 'Visit 3 Sports experiences.',
                'badge_image' => 'images/engagement/badges/sports-adventurer.png',
                'criteria_type' => 'sports',
                'target_count' => 3,
            ],

            [
                'badge_name' => 'Stamp Collector',
                'description' => 'Collect passport stamps throughout your journey.',
                'requirement' => 'Collect 5 passport stamps.',
                'badge_image' => 'images/engagement/badges/stamp-collector.png',
                'criteria_type' => 'total_stamps',
                'target_count' => 5,
            ],

            [
                'badge_name' => 'Heritage Master',
                'description' => 'Collect every passport stamp available.',
                'requirement' => 'Collect all 13 passport stamps.',
                'badge_image' => 'images/engagement/badges/heritage-master.png',
                'criteria_type' => 'total_stamps',
                'target_count' => 13,
            ],

            [
                'badge_name' => 'Living Heritage Champion',
                'description' => 'Unlock every other achievement badge.',
                'requirement' => 'Unlock every other achievement badge.',
                'badge_image' => 'images/engagement/badges/living-heritage-champion.png',
                'criteria_type' => 'all_badges',
                'target_count' => 14,
            ],

        ]);
    }
}