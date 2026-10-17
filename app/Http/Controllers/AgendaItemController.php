<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAgendaItemRequest;
use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Support\Editor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AgendaItemController extends Controller
{
    public function store(SaveAgendaItemRequest $request, Conference $conference, ConferenceSession $session): RedirectResponse
    {
        $position = (int) AgendaItem::query()->where('conference_session_id', $session->id)->max('position') + 1;

        $session->items()->create([
            ...$request->itemAttributes(),
            'position' => $position,
            'updated_by' => Editor::name($request),
        ]);

        Inertia::flash('toast', 'Item added');

        return back();
    }

    public function update(SaveAgendaItemRequest $request, Conference $conference, AgendaItem $item): RedirectResponse
    {
        $item->update([...$request->itemAttributes($item), 'updated_by' => Editor::name($request)]);

        Inertia::flash('toast', 'Item saved');

        return back();
    }

    public function destroy(Conference $conference, AgendaItem $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', 'Item deleted');

        return back();
    }
}
