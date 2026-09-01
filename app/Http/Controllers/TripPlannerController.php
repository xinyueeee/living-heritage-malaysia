<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\TripPlan;
use App\Models\TripPlanItem;


class TripPlannerController extends Controller
{
    public function index()
    {
        return view('trip-planner.index');
    }

    public function nearby(Request $request)
    {
        // Get user's existing trips
        $trips = TripPlan::where('user_id', auth()->id())
            ->orderBy('trip_date', 'asc')
            ->get();

        // Area reference coordinates
        $areaCoordinates = [

            'Kuala Lumpur' => [
                'latitude' => 3.1390,
                'longitude' => 101.6869,
            ],

            'Selangor' => [
                'latitude' => 3.0738,
                'longitude' => 101.5183,
            ],

            'Penang' => [
                'latitude' => 5.4141,
                'longitude' => 100.3288,
            ],

            'Johor' => [
                'latitude' => 1.4927,
                'longitude' => 103.7414,
            ],

            'Perak' => [
                'latitude' => 4.5975,
                'longitude' => 101.0901,
            ],

            'Melaka' => [
                'latitude' => 2.1896,
                'longitude' => 102.2501,
            ],

            'Negeri Sembilan' => [
                'latitude' => 2.7258,
                'longitude' => 101.9424,
            ],

            'Pahang' => [
                'latitude' => 3.8126,
                'longitude' => 103.3256,
            ],

            'Terengganu' => [
                'latitude' => 5.3117,
                'longitude' => 103.1324,
            ],

            'Kelantan' => [
                'latitude' => 6.1254,
                'longitude' => 102.2381,
            ],

            'Kedah' => [
                'latitude' => 6.1184,
                'longitude' => 100.3685,
            ],

            'Perlis' => [
                'latitude' => 6.4414,
                'longitude' => 100.1986,
            ],

            'Sabah' => [
                'latitude' => 5.9804,
                'longitude' => 116.0735,
            ],

            'Sarawak' => [
                'latitude' => 1.5533,
                'longitude' => 110.3592,
            ],

            'Putrajaya' => [
                'latitude' => 2.9264,
                'longitude' => 101.6964,
            ],

            'Labuan' => [
                'latitude' => 5.2831,
                'longitude' => 115.2308,
            ],
        ];


        // ========================================
        // Normal page request
        // ========================================

        if (
            !$request->filled('latitude') ||
            !$request->filled('longitude')
        ) {
            return view('trip-planner.nearby', [
                'trips' => $trips,
            ]);
        }


        // ========================================
        // User's current location
        // ========================================

        $userLatitude = (float) $request->latitude;
        $userLongitude = (float) $request->longitude;

        $radius = (int) $request->get('radius', 10);

        // Only allow these radius values
        $allowedRadius = [10, 15, 20, 30, 50];

        if (!in_array($radius, $allowedRadius)) {
            $radius = 10;
        }


        // ========================================
        // Get cultural experiences
        // ========================================

        $experiences = Experience::where('type_id', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();


        $earthRadius = 6371;


        // ========================================
        // Calculate distance + nearest area
        // ========================================

        $experiences = $experiences
            ->map(function ($experience) use (
                $userLatitude,
                $userLongitude,
                $earthRadius,
                $areaCoordinates
            ) {

                // Distance from user's current location
                $lat1 = deg2rad($userLatitude);
                $lon1 = deg2rad($userLongitude);

                $lat2 = deg2rad((float) $experience->latitude);
                $lon2 = deg2rad((float) $experience->longitude);

                $latDifference = $lat2 - $lat1;
                $lonDifference = $lon2 - $lon1;

                $a =
                    sin($latDifference / 2) ** 2
                    +
                    cos($lat1)
                    * cos($lat2)
                    * sin($lonDifference / 2) ** 2;

                $c = 2 * atan2(
                    sqrt($a),
                    sqrt(1 - $a)
                );

                $distance = $earthRadius * $c;

                $experience->distance_km =
                    round($distance, 1);


                // ========================================
                // Find nearest Malaysian area
                // ========================================

                $nearestArea = null;
                $nearestAreaDistance = PHP_FLOAT_MAX;

                foreach ($areaCoordinates as $area => $coordinates) {

                    $areaLat = deg2rad(
                        $coordinates['latitude']
                    );

                    $areaLon = deg2rad(
                        $coordinates['longitude']
                    );

                    $areaLatDifference =
                        $lat2 - $areaLat;

                    $areaLonDifference =
                        $lon2 - $areaLon;

                    $areaA =
                        sin($areaLatDifference / 2) ** 2
                        +
                        cos($areaLat)
                        * cos($lat2)
                        * sin($areaLonDifference / 2) ** 2;

                    $areaC = 2 * atan2(
                        sqrt($areaA),
                        sqrt(1 - $areaA)
                    );

                    $areaDistance =
                        $earthRadius * $areaC;

                    if (
                        $areaDistance <
                        $nearestAreaDistance
                    ) {
                        $nearestAreaDistance =
                            $areaDistance;

                        $nearestArea = $area;
                    }
                }

                // Store nearest area
                $experience->nearby_area =
                    $nearestArea;


                return $experience;
            })
            ->filter(function ($experience) use ($radius) {
                return $experience->distance_km <= $radius;
            })
            ->sortBy('distance_km')
            ->values();


        return response()->json([
            'success' => true,
            'experiences' => $experiences,
        ]);
    }

    public function create(Request $request)
    {
        return view('trip-planner.create', [
            'experienceId' => $request->experience_id,
            'experienceArea' => $request->area,
        ]);
    }

    public function events(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | If viewing an existing trip
    |--------------------------------------------------------------------------
    */

    if ($request->filled('trip_id')) {

        // Get the EXACT trip that the user clicked
        $tripPlan = TripPlan::where('id', $request->trip_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Get ALL trip information from this selected trip
        $date = $tripPlan->trip_date->format('Y-m-d');
        $tripName = $tripPlan->trip_name;
        $area = $tripPlan->area;

    } else {

        /*
        |--------------------------------------------------------------------------
        | Creating a new trip
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'trip_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'trip_name' => [
                'required',
                'string',
                'max:100',
            ],

            'area' => [
                'required',
                'string',
            ],
        ]);

        $date = $request->trip_date;
        $tripName = $request->trip_name;
        $area = $request->area;

        $experienceId = $request->experience_id;

        // Create NEW trip
        $tripPlan = TripPlan::create([
            'user_id' => auth()->id(),
            'trip_name' => $tripName,
            'area' => $area,
            'trip_date' => $date,
            'status' => 'planning',
        ]);

        // Add initial experience if provided
        if ($experienceId) {
            TripPlanItem::create([
                'trip_plan_id' => $tripPlan->id,
                'experience_id' => $experienceId,
                'item_type' => 'cultural',
            ]);
        }

        // Redirect to the newly created trip using ONLY its ID
        return redirect()->route('trip.planner.events', [
            'trip_id' => $tripPlan->id,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Your existing area coordinates
    |--------------------------------------------------------------------------
    */

    $areaCoordinates = [

        'Kuala Lumpur' => [
            'latitude' => 3.1390,
            'longitude' => 101.6869,
        ],

        'Selangor' => [
            'latitude' => 3.0738,
            'longitude' => 101.5183,
        ],

        'Penang' => [
            'latitude' => 5.4141,
            'longitude' => 100.3288,
        ],

        'Johor' => [
            'latitude' => 1.4927,
            'longitude' => 103.7414,
        ],

        'Perak' => [
            'latitude' => 4.5975,
            'longitude' => 101.0901,
        ],

        'Melaka' => [
            'latitude' => 2.1896,
            'longitude' => 102.2501,
        ],

        'Negeri Sembilan' => [
            'latitude' => 2.7258,
            'longitude' => 101.9424,
        ],

        'Pahang' => [
            'latitude' => 3.8126,
            'longitude' => 103.3256,
        ],

        'Terengganu' => [
            'latitude' => 5.3117,
            'longitude' => 103.1324,
        ],

        'Kelantan' => [
            'latitude' => 6.1254,
            'longitude' => 102.2381,
        ],

        'Kedah' => [
            'latitude' => 6.1184,
            'longitude' => 100.3685,
        ],

        'Perlis' => [
            'latitude' => 6.4414,
            'longitude' => 100.1986,
        ],

        'Sabah' => [
            'latitude' => 5.9804,
            'longitude' => 116.0735,
        ],

        'Sarawak' => [
            'latitude' => 1.5533,
            'longitude' => 110.3592,
        ],

        'Putrajaya' => [
            'latitude' => 2.9264,
            'longitude' => 101.6964,
        ],

        'Labuan' => [
            'latitude' => 5.2831,
            'longitude' => 115.2308,
        ],
    ];


    /*
    |--------------------------------------------------------------------------
    | Get festivals
    |--------------------------------------------------------------------------
    */

    $festivals = Experience::where('type_id', 2)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->whereDate('start_date', '<=', $date)
        ->whereDate('end_date', '>=', $date)
        ->get();

    $festivals = $festivals
        ->map(function ($festival) use ($areaCoordinates) {

            $festivalLat = deg2rad((float) $festival->latitude);
            $festivalLon = deg2rad((float) $festival->longitude);

            $nearestArea = null;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($areaCoordinates as $areaName => $coordinates) {

                $areaLat = deg2rad($coordinates['latitude']);
                $areaLon = deg2rad($coordinates['longitude']);

                $latDifference = $festivalLat - $areaLat;
                $lonDifference = $festivalLon - $areaLon;

                $a =
                    sin($latDifference / 2) ** 2
                    +
                    cos($areaLat)
                    * cos($festivalLat)
                    * sin($lonDifference / 2) ** 2;

                $c = 2 * atan2(
                    sqrt($a),
                    sqrt(1 - $a)
                );

                $distance = 6371 * $c;

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestArea = $areaName;
                }
            }

            $festival->nearby_area = $nearestArea;

            return $festival;
        })
        ->filter(function ($festival) use ($area) {
            return $festival->nearby_area === $area;
        })
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Get selected area's coordinates
    |--------------------------------------------------------------------------
    */

    $areaLocation = $areaCoordinates[$area];


    /*
    |--------------------------------------------------------------------------
    | Get cultural experiences
    |--------------------------------------------------------------------------
    */

    $culturalExperiences = Experience::where('type_id', 1)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Filter cultural experiences by area
    |--------------------------------------------------------------------------
    */

    $radius = 50;

    $culturalExperiences = $culturalExperiences
        ->filter(function ($experience) use ($areaLocation, $radius) {

            $earthRadius = 6371;

            $lat1 = deg2rad($areaLocation['latitude']);
            $lon1 = deg2rad($areaLocation['longitude']);

            $lat2 = deg2rad($experience->latitude);
            $lon2 = deg2rad($experience->longitude);

            $latDifference = $lat2 - $lat1;
            $lonDifference = $lon2 - $lon1;

            $a =
                sin($latDifference / 2) ** 2
                +
                cos($lat1)
                * cos($lat2)
                * sin($lonDifference / 2) ** 2;

            $c = 2 * atan2(
                sqrt($a),
                sqrt(1 - $a)
            );

            $distance = $earthRadius * $c;

            if ($distance <= $radius) {
                $experience->distance_km = round($distance, 1);
                return true;
            }

            return false;
        })
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Get experiences already inside THIS trip
    |--------------------------------------------------------------------------
    */

    $addedExperienceIds = TripPlanItem::where('trip_plan_id', $tripPlan->id)
        ->pluck('experience_id')
        ->toArray();


    /*
    |--------------------------------------------------------------------------
    | Put added experiences first
    |--------------------------------------------------------------------------
    */

    $culturalExperiences = $culturalExperiences
        ->sortByDesc(function ($experience) use ($addedExperienceIds) {

            return in_array(
                $experience->experiences_id,
                $addedExperienceIds
            );
        })
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Display the selected trip
    |--------------------------------------------------------------------------
    */

    return view('trip-planner.events', [
        'date' => $date,
        'area' => $area,
        'festivals' => $festivals,
        'culturalExperiences' => $culturalExperiences,
        'addedExperienceIds' => $addedExperienceIds,
        'tripPlan' => $tripPlan,
    ]);
}

    public function myTrips(Request $request)
    {
        $sort = $request->get('sort', 'latest');

        $trips = TripPlan::where('user_id', auth()->id())
            ->with('items.experience');

        if ($sort === 'oldest') {
            $trips->orderBy('trip_date', 'asc');
        } else {
            $trips->orderBy('trip_date', 'desc');
        }

        $trips = $trips->get();

        return view('trip-planner.my-trips', compact('trips'));
    }

    public function destroy(TripPlan $trip)
    {
        // Make sure the trip belongs to the logged-in user
        if ($trip->user_id !== auth()->id()) {
            abort(403);
        }

        // Delete all items belonging to this trip
        TripPlanItem::where('trip_plan_id', $trip->id)->delete();

        // Delete the trip itself
        $trip->delete();

        return redirect()
            ->route('trip.planner.my-trips')
            ->with('success', 'Trip deleted successfully.');
    }

    public function addToTrip(Request $request)
    {
        $request->validate([
            'trip_id' => [
                'required',
                'integer',
                'exists:trip_plans,id',
            ],

            'experience_id' => [
                'required',
            ],

            'item_type' => [
                'required',
                'in:festival,cultural',
            ],
        ]);

        $trip = TripPlan::where('id', $request->trip_id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

        $existingItem = TripPlanItem::where('trip_plan_id', $trip->id)
            ->where('experience_id', $request->experience_id)
            ->first();

        if ($existingItem) {
            return response()->json([
                'success' => false,
                'already_added' => true,
                'message' => 'This experience is already in your trip.',
            ]);
        }

        $nextOrder = TripPlanItem::where('trip_plan_id', $trip->id)
            ->max('display_order');

        TripPlanItem::create([
            'trip_plan_id' => $trip->id,
            'experience_id' => $request->experience_id,
            'item_type' => $request->item_type,
            'display_order' => ($nextOrder ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event added to your trip.',
        ]);
    }

    public function removeFromTrip(Request $request)
{
    $request->validate([
        'trip_id' => [
            'required',
            'integer',
            'exists:trip_plans,id',
        ],

        'experience_id' => [
            'required',
        ],
    ]);

    // Find the EXACT trip that the user is viewing
    $trip = TripPlan::where('id', $request->trip_id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    // Remove the experience from THIS trip only
    $deleted = TripPlanItem::where('trip_plan_id', $trip->id)
        ->where('experience_id', $request->experience_id)
        ->delete();

    return response()->json([
        'success' => $deleted > 0,
        'message' => $deleted > 0
            ? 'Experience removed from your trip.'
            : 'Experience was not found in your trip.',
    ]);
}

    public function remove(Request $request)
{
    $request->validate([
        'trip_id' => [
            'required',
            'integer',
            'exists:trip_plans,id',
        ],

        'experience_id' => [
            'required',
        ],
    ]);

    $tripPlan = TripPlan::where('id', $request->trip_id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    $tripItem = TripPlanItem::where('trip_plan_id', $tripPlan->id)
        ->where('experience_id', $request->experience_id)
        ->first();

    if (!$tripItem) {
        return response()->json([
            'success' => false,
            'message' => 'This event is not in your trip.'
        ]);
    }

    $tripItem->delete();

    return response()->json([
        'success' => true,
        'message' => 'Event removed from your trip.'
    ]);
}

    public function nearbyTrips(Request $request)
{
    $userId = auth()->id();

    $trips = TripPlan::with('items')
        ->where('user_id', $userId)
        ->orderBy('trip_date')
        ->get();

    return response()->json([
        'success' => true,
        'trips' => $trips->map(function ($trip) {

            return [
                'id' => $trip->id,
                'trip_name' => $trip->trip_name,
                'area' => $trip->area,
                'trip_date' => $trip->trip_date->format('d F Y'),

                'experience_ids' => $trip->items
                    ->pluck('experience_id')
                    ->values(),
            ];

        }),
    ]);
}


    
}