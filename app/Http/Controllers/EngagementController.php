<?php

namespace App\Http\Controllers;

class EngagementController extends Controller
{
    public function index()
    {
        return view('engagement.index', [
            'passportStamps' => collect([]),
            'achievements' => collect([]),
            'experienceHistory' => collect([])
        ]);
    }
}