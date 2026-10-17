<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The person making changes, identified only by the name they typed once
 * per device (there are no user accounts).
 */
final class Editor
{
    public const COOKIE = 'editor_name';

    public static function name(Request $request): ?string
    {
        $name = trim((string) $request->cookie(self::COOKIE));

        return $name === '' ? null : $name;
    }
}
