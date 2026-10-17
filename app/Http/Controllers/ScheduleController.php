<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConferenceResource;
use App\Models\Conference;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function show(Conference $conference): Response
    {
        $conference->load([
            'days.sessions.notes',
            'days.sessions.items.notes',
        ]);

        return Inertia::render('Schedule/Show', [
            'conference' => ConferenceResource::make($conference),
        ]);
    }
}
