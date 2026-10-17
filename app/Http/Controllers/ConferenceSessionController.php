<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveConferenceSessionRequest;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Day;
use App\Support\Editor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ConferenceSessionController extends Controller
{
    public function store(SaveConferenceSessionRequest $request, Conference $conference): RedirectResponse
    {
        $day = Day::query()->whereBelongsTo($conference)->findOrFail($request->validated('day_id'));

        $day->sessions()->create([...$request->sessionAttributes(), 'updated_by' => Editor::name($request)]);

        Inertia::flash('toast', 'Session added');

        return back();
    }

    public function update(SaveConferenceSessionRequest $request, Conference $conference, ConferenceSession $session): RedirectResponse
    {
        $session->update([...$request->sessionAttributes(), 'updated_by' => Editor::name($request)]);

        Inertia::flash('toast', 'Session saved');

        return back();
    }

    public function destroy(Conference $conference, ConferenceSession $session): RedirectResponse
    {
        $session->delete();

        Inertia::flash('toast', 'Session deleted');

        return back();
    }
}
