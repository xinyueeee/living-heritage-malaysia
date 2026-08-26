<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\TripPlan;
use App\Models\TripPlanItem;

class TripPlannerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Plan Trip
    |--------------------------------------------------------------------------
    */

    public function plan(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;
        $userId = auth()->id();


        /*
        |--------------------------------------------------------------------------
        | Create or get user's Trip Plan
        |--------------------------------------------------------------------------
        */

        $tripPlan = TripPlan::firstOrCreate(
            [
                'user_id' => $userId,
                'trip_date' => $date,
            ],
            [
                'status' => 'active',
            ]
            
        );

        $savedTripItems = TripPlanItem::where(
            'trip_plan_id',
            $tripPlan->id
        )
        ->with('experience')
        ->orderBy(
            'display_order',
            'asc'
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | STEP 1
        | Get ALL festivals selected by this user for this date
        |--------------------------------------------------------------------------
        */

        $notifications = Notification::where(
            'user_id',
            $userId
        )
        ->where(
            'notification_type',
            'festival_reminder'
        )
        ->whereDate(
            'selected_date',
            $date
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | STEP 2
        | Get selected Festival experiences
        |--------------------------------------------------------------------------
        */

        $selectedFestivalIds = $notifications
            ->pluck('experience_id')
            ->filter()
            ->unique();


        $selectedFestivals = Experience::whereIn(
            'experiences_id',
            $selectedFestivalIds
        )
        ->where(
            'type_id',
            2
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | No selected festivals
        |--------------------------------------------------------------------------
        */

        if ($selectedFestivals->isEmpty())
        {
            return response()->json([
                'success' => false,

                'message' =>
                    'No selected festivals were found for this date.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | STEP 3
        | Find Cultural Experiences
        |--------------------------------------------------------------------------
        */

        $culturalExperiences = Experience::where(
            'type_id',
            1
        )
        ->whereNotIn(
            'experiences_id',
            $selectedFestivalIds
        )
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get();


        /*
        |--------------------------------------------------------------------------
        | STEP 4
        | Calculate distance to ALL selected festivals
        |--------------------------------------------------------------------------
        */

        $culturalExperiences = $culturalExperiences->map(
            function ($culturalExperience) use ($selectedFestivals)
            {
                $distances = [];


                foreach ($selectedFestivals as $festival)
                {
                    if (
                        $festival->latitude === null ||
                        $festival->longitude === null
                    )
                    {
                        continue;
                    }


                    $lat1 = deg2rad(
                        $festival->latitude
                    );

                    $lon1 = deg2rad(
                        $festival->longitude
                    );

                    $lat2 = deg2rad(
                        $culturalExperience->latitude
                    );

                    $lon2 = deg2rad(
                        $culturalExperience->longitude
                    );


                    $latDifference =
                        $lat2 - $lat1;

                    $lonDifference =
                        $lon2 - $lon1;


                    $a =
                        sin($latDifference / 2) ** 2
                        +
                        cos($lat1)
                        *
                        cos($lat2)
                        *
                        sin($lonDifference / 2) ** 2;


                    $c = 2 * asin(
                        sqrt($a)
                    );


                    $distance = 6371 * $c;


                    $distances[] = [
                        'festival_id' =>
                            $festival->experiences_id,

                        'festival_name' =>
                            $festival->experiences_name,

                        'distance_km' =>
                            round($distance, 2),
                    ];
                }


                $culturalExperience->festival_distances =
                    $distances;


                return $culturalExperience;
            }
        );


        /*
        |--------------------------------------------------------------------------
        | STEP 5
        | Keep Cultural Experiences within 15 km
        | of at least one selected Festival
        |--------------------------------------------------------------------------
        */

        $maximumDistance = 15;


        $culturalExperiences = $culturalExperiences
            ->filter(function ($experience) use ($maximumDistance)
            {
                return collect(
                    $experience->festival_distances
                )->contains(function ($distance)
                    use ($maximumDistance)
                {
                    return $distance['distance_km']
                        <= $maximumDistance;
                });
            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | STEP 6
        | Return Trip Planner Result
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'date' => $date,

            'selected_festivals' =>
                $selectedFestivals->map(function ($festival)
                {
                    return [
                        'id' =>
                            $festival->experiences_id,

                        'name' =>
                            $festival->experiences_name,

                        'location' =>
                            $festival->location_name,

                        'duration' =>
                            $festival->duration,

                        'latitude' =>
                            $festival->latitude,

                        'longitude' =>
                            $festival->longitude,
                    ];
                }),

            'recommended_cultural_experiences' =>
                $culturalExperiences->map(
                    function ($experience)
                    {
                        return [
                            'id' =>
                                $experience->experiences_id,

                            'name' =>
                                $experience->experiences_name,

                            'location' =>
                                $experience->location_name,

                            'duration' =>
                                $experience->duration,

                            'latitude' =>
                                $experience->latitude,

                            'longitude' =>
                                $experience->longitude,

                            'distances' =>
                                $experience->festival_distances,
                        ];
                    }
                ),
                'saved_trip_items' =>
                    $savedTripItems->map(function ($item)
                    {
                        return [
                            'id' =>
                                $item->experience->experiences_id,

                            'name' =>
                                $item->experience->experiences_name,

                            'location' =>
                                $item->experience->location_name,

                            'duration' =>
                                $item->experience->duration,

                            'latitude' =>
                                $item->experience->latitude,

                            'longitude' =>
                                $item->experience->longitude,

                            'item_type' =>
                                $item->item_type,

                            'display_order' =>
                                $item->display_order,
                        ];
                    }),

        ]);

        
    }


    /*
    |--------------------------------------------------------------------------
    | Add Cultural Experience to Trip
    |--------------------------------------------------------------------------
    */

    public function add(Request $request)
    {
        $request->validate([
            'date' => 'required|date',

            'experience_id' =>
                'required|integer',
        ]);

        $userId = auth()->id();


        /*
        |--------------------------------------------------------------------------
        | Find or create Trip Plan
        |--------------------------------------------------------------------------
        */

        $tripPlan = TripPlan::firstOrCreate(
            [
                'user_id' => $userId,

                'trip_date' =>
                    $request->date,
            ],
            [
                'status' => 'active',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Check if already added
        |--------------------------------------------------------------------------
        */

        $existingItem = TripPlanItem::where(
            'trip_plan_id',
            $tripPlan->id
        )
        ->where(
            'experience_id',
            $request->experience_id
        )
        ->first();


        if ($existingItem)
        {
            return response()->json([
                'success' => true,

                'message' =>
                    'This experience is already in your trip.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Add Cultural Experience
        |--------------------------------------------------------------------------
        */

        TripPlanItem::create([
            'trip_plan_id' =>
                $tripPlan->id,

            'experience_id' =>
                $request->experience_id,

            'item_type' =>
                'cultural',

            'display_order' =>
                0,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Return Success
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Experience added to your trip.',
        ]);
    }
}