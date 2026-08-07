<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;


class CalendarController extends Controller
{


    public function index()
    {
        return view('festival.calendar');
    }



    public function calendarEvents(): JsonResponse
    {

        $events = DB::table('experiences')
            ->select(
                'experiences_id as id',
                'experiences_name as title',
                'start_date',
                'end_date'
            )
            ->get();


        return response()->json($events);

    }


}