<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::get('/s/{conference:access_token}', [ScheduleController::class, 'show'])->name('schedule.show');
