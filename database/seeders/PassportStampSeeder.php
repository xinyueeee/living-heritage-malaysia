<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PassportStampSeeder extends Seeder
{
    public function run(): void
    {
        $stampImages = [
            'Workshop'
                => 'images/engagement/stamps/workshop-stamp.png',

            'Culinary'
                => 'images/engagement/stamps/culinary-stamp.png',

            'Heritage'
                => 'images/engagement/stamps/heritage-stamp.png',

            'Adventure'
                => 'images/engagement/stamps/adventure-stamp.png',

            'Wildlife'
                => 'images/engagement/stamps/wildlife-stamp.png',

            'Arts & Crafts'
                => 'images/engagement/stamps/arts-and-crafts-stamp.png',

            'Arts & Culture'
                => 'images/engagement/stamps/arts-and-culture-stamp.png',

            'Foods & Drinks'
                => 'images/engagement/stamps/foods-and-drinks-stamp.png',

            'Entertainment'
                => 'images/engagement/stamps/entertainment-stamp.png',

            'Festival'
                => 'images/engagement/stamps/festival-stamp.png',

            'Sports'
                => 'images/engagement/stamps/sports-stamp.png',

            'Music'
                => 'images/engagement/stamps/music-stamp.png',

            'Nature'
                => 'images/engagement/stamps/nature-stamp.png',

            'Cultural Festival'
                => 'images/engagement/stamps/cultural-festival-stamp.png',

            'Food Festival'
                => 'images/engagement/stamps/food-festival-stamp.png',

            'Music Festival'
                => 'images/engagement/stamps/music-festival-stamp.png',

            'National Celebration'
                => 'images/engagement/stamps/national-celebration-stamp.png',

            'Nature Festival'
                => 'images/engagement/stamps/nature-festival-stamp.png',

            'Sports Festival'
                => 'images/engagement/stamps/sports-festival-stamp.png',
        ];

        foreach ($stampImages as $categoryName => $imagePath) {
            $category = DB::table('category')
                ->where('category_name', $categoryName)
                ->first();

            if (! $category) {
                $this->command?->warn(
                    "Category not found: {$categoryName}"
                );

                continue;
            }

            DB::table('passport_stamp')->updateOrInsert(
                [
                    'category_id' => $category->category_id,
                ],
                [
                    'category' => $category->category_name,
                    'state' => null,
                    'stamp_image' => $imagePath,
                ]
            );
        }
    }
}