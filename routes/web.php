<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\PersonalInformationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\SavedExperienceController;
use App\Http\Controllers\SavedPostController;
use App\Http\Controllers\EngagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;

Route::get('/', [ExperienceController::class, 'home'])->name('home');
Route::get('/experiences', [ExperienceController::class, 'index'])->name('experiences.index');
Route::get('/experiences/{experience}', [ExperienceController::class, 'show'])->name('experiences.show');
Route::get('/recommendations', [ExperienceController::class, 'recommendations'])->name('recommendations.index');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::get('/auth/callback', [AuthController::class, 'callback'])->name('auth.callback');
Route::post('/auth/sync', [AuthController::class, 'sync'])->name('auth.sync');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
Route::get('/engagement', [EngagementController::class, 'index'])->name('engagement.index');
Route::get('/community', [CommunityController::class, 'index'])->name('community.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile/personal-information', [PersonalInformationController::class, 'show'])
        ->name('profile.personal-information');
    Route::patch('/profile/personal-information/{field}', [PersonalInformationController::class, 'update'])
        ->whereIn('field', ['user_name', 'user_email', 'bio', 'gender', 'birthday'])
        ->name('profile.personal-information.update');
    Route::post('/profile/photo', [ProfilePhotoController::class, 'store'])->name('profile.photo.store');
    Route::get('/profile/interests', [InterestController::class, 'show'])->name('profile.interests');
    Route::put('/profile/interests', [InterestController::class, 'update'])->name('profile.interests.update');

    Route::get('/profile/saved-experiences', [SavedExperienceController::class, 'index'])
        ->name('profile.saved-experiences');
    Route::get('/profile/my-posts', [ProfileController::class, 'myPosts'])->name('profile.my-posts');
    Route::get('/profile/saved-posts', [SavedPostController::class, 'index'])->name('profile.saved-posts');

    Route::post('/experiences/{experience}/save', [SavedExperienceController::class, 'store'])
        ->name('experiences.saved.store');
    Route::delete('/experiences/{experience}/save', [SavedExperienceController::class, 'destroy'])
        ->name('experiences.saved.destroy');

    Route::post('/community/posts/{post}/save', [SavedPostController::class, 'store'])
        ->name('community.posts.saved.store');
    Route::delete('/community/posts/{post}/save', [SavedPostController::class, 'destroy'])
        ->name('community.posts.saved.destroy');

    Route::get('/engagement/passport', [EngagementController::class, 'passport'])
        ->name('engagement.passport');
    Route::get('/engagement/passport/customize',[EngagementController::class, 'customizePassport'])->name('engagement.passport.customize');
    Route::put('/engagement/passport/customize',[EngagementController::class, 'updatePassportCustomization'])->name('engagement.passport.customization.update');

    Route::get('/engagement/achievements', [EngagementController::class, 'achievements'])
        ->name('engagement.achievements');

    Route::get('/engagement/history', [EngagementController::class, 'history'])
        ->name('engagement.history');

    Route::get('/community/create', [CommunityController::class, 'create'])->name('community.create');
    Route::post('/community', [CommunityController::class, 'store'])->name('community.store');
});



Route::get('/festival/calendar', [CalendarController::class, 'index'])->name('festival.calendar');
Route::get('/calendar/events', [CalendarController::class, 'calendarEvents']);

Route::get('/calendar/login', function () {return view('calendar.login-required');})->name('calendar.login');

Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index')
    ->middleware('auth');

Route::post('/calendar/reminder', [NotificationController::class, 'storeReminder'])
    ->name('calendar.reminder')
    ->middleware('auth');
