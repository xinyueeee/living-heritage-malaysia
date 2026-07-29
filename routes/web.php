<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DevLoginController;
use App\Http\Controllers\ExperienceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExperienceController::class, 'home'])->name('home');
Route::get('/experiences', [ExperienceController::class, 'index'])->name('experiences.index');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::get('/auth/callback', [AuthController::class, 'callback'])->name('auth.callback');
Route::post('/auth/sync', [AuthController::class, 'sync'])->name('auth.sync');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

if (app()->environment('local')) {
    Route::get('/dev/login', [DevLoginController::class, 'index'])->name('dev.login');
    Route::post('/dev/login/{user}', [DevLoginController::class, 'login'])->name('dev.login.as');
}
