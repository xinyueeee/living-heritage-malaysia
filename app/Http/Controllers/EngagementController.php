<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class EngagementController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return view('engagement.login');
        }

        return view('engagement.index', [
            'passportStamps' => collect([]),
            'achievements' => collect([]),
            'experienceHistory' => collect([])
        ]);
    }
}