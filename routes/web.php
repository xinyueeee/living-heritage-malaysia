<?php

use App\Http\Controllers\ExperienceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExperienceController::class, 'home'])->name('home');
Route::get('/experiences', [ExperienceController::class, 'index'])->name('experiences.index');
