<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CommunityGroupController;
use App\Http\Controllers\DiscoveryActivityController;
use App\Http\Controllers\DiscoveryAssistantController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PersonalInformationController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\SavedExperienceCollectionController;
use App\Http\Controllers\SavedExperienceController;
use App\Http\Controllers\SavedPostController;
use App\Http\Controllers\TripPlannerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExperienceController::class, 'home'])->name('home');
Route::get('/experiences', [ExperienceController::class, 'index'])->name('experiences.index');
Route::get('/experiences/map', [ExperienceController::class, 'map'])->name('experiences.map');
Route::get('/experiences/trending', [ExperienceController::class, 'trending'])->name('experiences.trending');
Route::get('/experiences/{experience}', [ExperienceController::class, 'show'])->name('experiences.show');
Route::get('/recommendations', [ExperienceController::class, 'recommendations'])->name('recommendations.index');
Route::post('/discover-assistant/message', DiscoveryAssistantController::class)
    ->name('discover-assistant.message');
Route::delete('/discover-assistant/context', [DiscoveryAssistantController::class, 'reset'])
    ->name('discover-assistant.reset');

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
        ->whereIn('field', ['user_name', 'bio', 'gender', 'birthday'])
        ->name('profile.personal-information.update');
    Route::post('/profile/photo', [ProfilePhotoController::class, 'store'])->name('profile.photo.store');
    Route::patch('/profile/photo/{photo}/restore', [ProfilePhotoController::class, 'restore'])->name('profile.photo.restore');
    Route::get('/profile/interests', [InterestController::class, 'show'])->name('profile.interests');
    Route::put('/profile/interests', [InterestController::class, 'update'])->name('profile.interests.update');

    Route::get('/profile/recent-activity', [DiscoveryActivityController::class, 'index'])->name('profile.recent-activity');
    Route::delete('/profile/recent-activity', [DiscoveryActivityController::class, 'clear'])->name('profile.recent-activity.clear');

   


    Route::get(
        '/trip-planner/create',
        [TripPlannerController::class, 'create']
    )->name('trip.planner.create');


    Route::get(
        '/trip-planner/my-trips',
        [TripPlannerController::class, 'myTrips']
    )->name('trip.planner.my-trips');

    Route::get(
        '/trip-planner',
        [TripPlannerController::class, 'index']
    )->name('trip.planner.index');

    Route::get(
        '/trip-planner/events',
        [TripPlannerController::class, 'events']
    )->name('trip.planner.events');


    Route::post(
        '/trip-planner/add',
        [TripPlannerController::class, 'addToTrip']
    )->name('trip.planner.add');

    Route::delete(
        '/trip-planner/remove',
        [TripPlannerController::class, 'removeFromTrip']
    )->name('trip.planner.remove');

    Route::get(
        '/trip-planner/nearby',
        [TripPlannerController::class, 'nearby']
    )->name('trip.planner.nearby');

    Route::delete(
        '/trip-planner/{trip}',
        [TripPlannerController::class, 'destroy']
    )->name('trip.planner.destroy');
            

    Route::get(
        '/trip-planner/nearby/trips',
        [TripPlannerController::class, 'nearbyTrips']
    )->middleware('auth')->name('trip.planner.nearby.trips');

    Route::get('/alerts/personalize', [AlertController::class, 'create'])
        ->name('alerts.create');



    Route::post('/alerts/personalize', [AlertController::class, 'store'])
        ->name('alerts.store');
    if (app()->isLocal()) {
        Route::get('/alerts/test-matching', [AlertController::class, 'testMatchingEvents'])
            ->name('alerts.test-matching');
        Route::get('/alerts/test-email', [AlertController::class, 'testEmail'])
            ->name('alerts.test-email');
    }


    Route::get('/profile/saved-experiences', [SavedExperienceController::class, 'index'])
        ->name('profile.saved-experiences');
    Route::get('/profile/my-posts', [ProfileController::class, 'myPosts'])->name('profile.my-posts');
    Route::get('/profile/saved-posts', [SavedPostController::class, 'index'])->name('profile.saved-posts');
    Route::get('/profile/achievements', [ProfileController::class, 'achievements'])->name('profile.achievements');

    Route::post('/experiences/{experience}/save', [SavedExperienceController::class, 'store'])
        ->name('experiences.saved.store');
    Route::delete('/experiences/{experience}/save', [SavedExperienceController::class, 'destroy'])
        ->name('experiences.saved.destroy');
    Route::patch('/experiences/{experience}/save/collection', [SavedExperienceController::class, 'move'])
        ->name('experiences.saved.move');
    Route::post('/profile/saved-experience-collections', [SavedExperienceCollectionController::class, 'store'])
        ->name('saved-experience-collections.store');
    Route::patch('/profile/saved-experience-collections/{collection}', [SavedExperienceCollectionController::class, 'update'])
        ->name('saved-experience-collections.update');
    Route::delete('/profile/saved-experience-collections/{collection}', [SavedExperienceCollectionController::class, 'destroy'])
        ->name('saved-experience-collections.destroy');

    Route::post('/community/posts/{post}/save', [SavedPostController::class, 'store'])
        ->name('community.posts.saved.store');
    Route::delete('/community/posts/{post}/save', [SavedPostController::class, 'destroy'])
        ->name('community.posts.saved.destroy');

    Route::post('/community/posts/{post}/like', [PostLikeController::class, 'like'])
        ->name('community.posts.like');
    Route::delete('/community/posts/{post}/like', [PostLikeController::class, 'unlike'])
        ->name('community.posts.unlike');
    Route::post('/community/posts/{postId}/comments', [CommentController::class, 'store'])
        ->name('comments.store');

    Route::get('/engagement/passport', [EngagementController::class, 'passport'])->name('engagement.passport');

    Route::patch('/engagement/passport/notifications/read', [EngagementController::class, 'acknowledgeStampNotifications'])->name('engagement.passport.notifications.read');

    Route::get('/engagement/passport/customize', [EngagementController::class, 'customizePassport'])->name('engagement.passport.customize');

    Route::put('/engagement/passport/customize', [EngagementController::class, 'updatePassportCustomization'])->name('engagement.passport.customization.update');

    Route::get('/engagement/achievements', [EngagementController::class, 'achievements'])->name('engagement.achievements');

    Route::patch('/engagement/achievements/notifications/read', [EngagementController::class, 'acknowledgeAchievementNotifications'])->name('engagement.achievements.notifications.read');

    Route::get('/engagement/history', [EngagementController::class, 'history'])->name('engagement.history');

    Route::get('/engagement/leaderboard',[EngagementController::class, 'leaderboard'])->name('engagement.leaderboard');

    Route::get('/community/create', [CommunityController::class, 'create'])->name('community.create');
    Route::post('/community', [CommunityController::class, 'store'])->name('community.store');

    /*
    |--------------------------------------------------------------------------
    | COMMUNITY GROUP MEMBERSHIP
    |--------------------------------------------------------------------------
    */
    Route::get('/community/groups', [CommunityGroupController::class, 'index'])
    ->name('community.groups.index');

    Route::get('/community/groups/{groupId}', [CommunityGroupController::class, 'show'])
    ->name('community.groups.show');

    Route::post('/community/groups/{groupId}/join', [CommunityGroupController::class, 'join'])
    ->name('community.groups.join');

    Route::delete('/community/groups/{groupId}/leave', [CommunityGroupController::class, 'leave'])
    ->name('community.groups.leave');

    // Profile Albums
    Route::get('/profile/albums', [AlbumController::class, 'index'])
        ->name('profile.albums.index');

    Route::get('/profile/albums/create', [AlbumController::class, 'create'])
        ->name('profile.albums.create');

    Route::post('/profile/albums', [AlbumController::class, 'store'])
        ->name('profile.albums.store');

    Route::get('/profile/albums/{album}', [AlbumController::class, 'show'])
        ->name('profile.albums.show');
    Route::get('/profile/albums/{album}/edit', [AlbumController::class, 'edit'])
        ->name('profile.albums.edit');

    Route::put('/profile/albums/{album}', [AlbumController::class, 'update'])
        ->name('profile.albums.update');

    Route::delete('/profile/albums/{album}', [AlbumController::class, 'destroy'])
        ->name('profile.albums.destroy');

    // Photo Management
    Route::get('/profile/albums/{album}/photos/create', [AlbumController::class, 'createPhotos'])
        ->name('profile.albums.photos.create');

    Route::post('/profile/albums/{album}/photos', [AlbumController::class, 'storePhotos'])
        ->name('profile.albums.photos.store');

    Route::delete('/profile/albums/{album}/photos/{photo}', [AlbumController::class, 'deletePhoto'])
        ->name('profile.albums.photos.destroy');
    // Cover Photo
    Route::patch('/profile/albums/{album}/cover/{photo}', [AlbumController::class, 'updateCover'])
        ->name('profile.albums.cover.update');
});

Route::view('/about', 'pages.about')->name('pages.about');
Route::view('/help-center', 'pages.help-center')->name('pages.help-center');
Route::view('/contact-us', 'pages.contact-us')->name('pages.contact-us');
Route::view('/privacy-policy', 'pages.privacy-policy')->name('pages.privacy-policy');
Route::view('/terms-of-use', 'pages.terms-of-use')->name('pages.terms-of-use');

Route::get('/festival/calendar', [CalendarController::class, 'index'])->name('festival.calendar');
Route::get('/calendar/events', [CalendarController::class, 'calendarEvents']);

Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index')
    ->middleware('auth');

Route::post('/calendar/reminder', [NotificationController::class, 'storeReminder'])
    ->name('calendar.reminder')
    ->middleware('auth');

Route::get('/festival/login-required', function () {
    return view('festival.login-required');
})->name('festival.login-required');


Route::get(
    '/experience/{id}',
    [ExperienceController::class, 'show']
)->name('experience.show');

Route::get('/notifications/count', [NotificationController::class, 'count'])
    ->name('notifications.count')
    ->middleware('auth');

