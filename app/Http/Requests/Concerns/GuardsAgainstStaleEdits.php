<?php

namespace App\Http\Requests\Concerns;

use App\Models\Conference;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

/**
 * Nobody gets live updates, so a form may have been opened on an older
 * version of the record. Saving such a form must not silently overwrite
 * someone else's change.
 */
trait GuardsAgainstStaleEdits
{
    /**
     * @return Closure(Validator): void
     */
    protected function staleEditGuard(Model $record, Conference $conference): Closure
    {
        return function (Validator $validator) use ($record, $conference): void {
            if ($this->input('expected_updated_at') === $record->updated_at?->toIso8601String()) {
                return;
            }

            $who = $record->updated_by ?? 'Someone';
            $when = $record->updated_at?->setTimezone($conference->team_timezone)->format('h:i A');
            $city = Conference::cityOf($conference->team_timezone);

            $validator->errors()->add(
                'stale',
                "{$who} changed this at {$when} ({$city} time) after you opened it. Close to see their version, or save again to replace it with yours.",
            );
        };
    }
}
