<?php

use App\Http\Controllers\AgendaItemController;
use App\Http\Controllers\AgendaItemStatusController;
use App\Http\Controllers\ConferenceSessionController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScheduleController::class, 'public'])->name('schedule.public');

Route::prefix('s/{conference:access_token}')->scopeBindings()->group(function () {
    Route::get('/', [ScheduleController::class, 'show'])->name('schedule.show');

    Route::post('editor', [EditorController::class, 'store'])->middleware('throttle:20,1')->name('editor.store');
    Route::delete('editor', [EditorController::class, 'destroy'])->middleware('throttle:20,1')->name('editor.destroy');

    Route::middleware(['editor', 'throttle:60,1'])->group(function () {
        Route::patch('items/{item}/status', [AgendaItemStatusController::class, 'update'])->name('items.status');

        Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
        Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

        Route::post('sessions', [ConferenceSessionController::class, 'store'])->name('sessions.store');
        Route::put('sessions/{session}', [ConferenceSessionController::class, 'update'])->name('sessions.update');
        Route::delete('sessions/{session}', [ConferenceSessionController::class, 'destroy'])->name('sessions.destroy');

        Route::post('sessions/{session}/items', [AgendaItemController::class, 'store'])->name('items.store');
        Route::put('items/{item}', [AgendaItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [AgendaItemController::class, 'destroy'])->name('items.destroy');
    });
});
