<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAgendaItemStatusRequest;
use App\Models\AgendaItem;
use App\Models\Conference;
use App\Support\Editor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AgendaItemStatusController extends Controller
{
    public function update(UpdateAgendaItemStatusRequest $request, Conference $conference, AgendaItem $item): RedirectResponse
    {
        $item->update([...$request->validated(), 'updated_by' => Editor::name($request)]);

        Inertia::flash('toast', 'Status saved');

        return back();
    }
}
