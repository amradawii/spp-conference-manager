<?php

namespace App\Http\Middleware;

use App\Support\Editor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEditorName
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Editor::name($request) === null) {
            return back()->withErrors(['editor_name' => 'Please tell us your name before making changes.']);
        }

        return $next($request);
    }
}
