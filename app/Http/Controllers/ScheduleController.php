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

    /**
     * The read-only programme of the newest conference, for speakers and attendees.
     */
    public function public(): Response
    {
        $conference = Conference::latest('id')->with('days.sessions.items')->first();

        if ($conference === null) {
            return Inertia::render('Home');
        }

        return Inertia::render('Schedule/Public', [
            'conference' => ConferenceResource::make($conference),
        ]);
    }
}
