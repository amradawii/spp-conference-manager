<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use App\Support\Editor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class EditorController extends Controller
{
    public function store(Request $request, Conference $conference): RedirectResponse
    {
        $validated = $request->validate(
            ['name' => ['required', 'string', 'min:2', 'max:60']],
            ['name.required' => 'Please enter your name.', 'name.min' => 'Please enter your name.'],
        );

        $name = trim($validated['name']);

        Cookie::queue(Cookie::forever(Editor::COOKIE, $name));
        Inertia::flash('toast', "Thanks, {$name}. Your changes will show your name.");

        return back();
    }
}
