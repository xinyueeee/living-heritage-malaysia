<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\PersonalInformationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EngagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommunityController;

Route::get('/', [ExperienceController::class, 'home'])->name('home');
Route::get('/experiences', [ExperienceController::class, 'index'])->name('experiences.index');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::get('/auth/callback', [AuthController::class, 'callback'])->name('auth.callback');
Route::post('/auth/sync', [AuthController::class, 'sync'])->name('auth.sync');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

Route::middleware('auth')->group(function () {
    Route::get('/profile/personal-information', [PersonalInformationController::class, 'show'])
        ->name('profile.personal-information');
    Route::patch('/profile/personal-information/{field}', [PersonalInformationController::class, 'update'])
        ->whereIn('field', ['user_name', 'user_email', 'bio', 'gender', 'birthday', 'phone_number', 'nationality'])
        ->name('profile.personal-information.update');
});

Route::get('/engagement', [EngagementController::class, 'index'])->name('engagement.index');
Route::get('/engagement/passport', [EngagementController::class, 'passport'])->name('engagement.passport');
Route::get('/engagement/achievements', [EngagementController::class, 'achievements'])->name('engagement.achievements');
Route::get('/engagement/history', [EngagementController::class, 'history'])->name('engagement.history');

Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
Route::get('/community/create', [CommunityController::class, 'create'])->name('community.create');
Route::post('/community', [CommunityController::class, 'store'])->name('community.store');

