<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Note;
use App\Support\Editor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class NoteController extends Controller
{
    public function store(StoreNoteRequest $request, Conference $conference): RedirectResponse
    {
        $notable = match ($request->validated('notable_type')) {
            'session' => ConferenceSession::query()->forConference($conference),
            'item' => AgendaItem::query()->forConference($conference),
        };

        $notable->findOrFail($request->validated('notable_id'))->notes()->create([
            'author_name' => Editor::name($request),
            'body' => trim($request->validated('body')),
        ]);

        Inertia::flash('toast', 'Note added');

        return back();
    }

    public function destroy(Conference $conference, Note $note): RedirectResponse
    {
        $note->delete();

        Inertia::flash('toast', 'Note deleted');

        return back();
    }
}
