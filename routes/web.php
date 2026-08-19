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
use App\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
<<<<<<< HEAD
use App\Http\Controllers\AlertController;
=======
use App\Http\Controllers\PostLikeController;
>>>>>>> origin/main

Route::get('/', [ExperienceController::class, 'home'])->name('home');
Route::get('/experiences', [ExperienceController::class, 'index'])->name('experiences.index');
Route::get('/experiences/map', [ExperienceController::class, 'map'])->name('experiences.map');
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

    Route::get('/alerts/personalize', [AlertController::class, 'create'])
    ->name('alerts.create');

Route::post('/alerts/personalize', [AlertController::class, 'store'])
    ->name('alerts.store');
    Route::get('/alerts/test-matching', [AlertController::class, 'testMatchingEvents'])
    ->name('alerts.test-matching');
    Route::get('/alerts/test-email', [AlertController::class, 'testEmail'])
    ->name('alerts.test-email');


#Route::get('/engagement', [EngagementController::class, 'index'])->name('engagement.index');
#Route::get('/engagement/passport', [EngagementController::class, 'passport'])->name('engagement.passport');
#Route::get('/engagement/achievements', [EngagementController::class, 'achievements'])->name('engagement.achievements');
#Route::get('/engagement/history', [EngagementController::class, 'history'])->name('engagement.history');


    Route::get('/profile/saved-experiences', [SavedExperienceController::class, 'index'])
        ->name('profile.saved-experiences');
    Route::get('/profile/my-posts', [ProfileController::class, 'myPosts'])->name('profile.my-posts');
    Route::get('/profile/saved-posts', [SavedPostController::class, 'index'])->name('profile.saved-posts');
    Route::get('/profile/achievements', [ProfileController::class, 'achievements'])->name('profile.achievements');
    Route::get('/profile/feedback', [FeedbackController::class, 'index'])->name('profile.feedback');
    Route::post('/profile/feedback', [FeedbackController::class, 'store'])->name('profile.feedback.store');

    Route::post('/experiences/{experience}/save', [SavedExperienceController::class, 'store'])
        ->name('experiences.saved.store');
    Route::delete('/experiences/{experience}/save', [SavedExperienceController::class, 'destroy'])
        ->name('experiences.saved.destroy');

    Route::post('/community/posts/{post}/save', [SavedPostController::class, 'store'])
        ->name('community.posts.saved.store');
    Route::delete('/community/posts/{post}/save', [SavedPostController::class, 'destroy'])
        ->name('community.posts.saved.destroy');
    
    Route::post('/community/posts/{post}/like', [PostLikeController::class, 'like'])
        ->name('community.posts.like');
    Route::delete('/community/posts/{post}/like', [PostLikeController::class, 'unlike'])
        ->name('community.posts.unlike');

    Route::get('/engagement/passport',[EngagementController::class, 'passport'])->name('engagement.passport');

    Route::patch('/engagement/passport/notifications/read',[EngagementController::class, 'acknowledgeStampNotifications'])->name('engagement.passport.notifications.read');

    Route::get('/engagement/passport/customize',[EngagementController::class, 'customizePassport'])->name('engagement.passport.customize');

    Route::put('/engagement/passport/customize',[EngagementController::class, 'updatePassportCustomization'])->name('engagement.passport.customization.update');

    Route::get('/engagement/achievements',[EngagementController::class, 'achievements'])->name('engagement.achievements');

    Route::patch('/engagement/achievements/notifications/read',[EngagementController::class, 'acknowledgeAchievementNotifications'])->name('engagement.achievements.notifications.read');

    Route::get('/engagement/history',[EngagementController::class, 'history'])->name('engagement.history');

    Route::get('/community/create', [CommunityController::class, 'create'])->name('community.create');
    Route::post('/community', [CommunityController::class, 'store'])->name('community.store');
});


Route::get('/festival/calendar', [CalendarController::class, 'index'])->name('festival.calendar');
Route::get('/calendar/events', [CalendarController::class, 'calendarEvents']);


Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index')
    ->middleware('auth');

Route::post('/calendar/reminder', [NotificationController::class, 'storeReminder'])
    ->name('calendar.reminder')
    ->middleware('auth');


Route::get('/festival/login-required', function () {return view('festival.login-required');})->name('festival.login-required');
    

