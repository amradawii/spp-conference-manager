<?php

namespace App\Http\Resources;

use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conference */
class ConferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $team = ! $request->routeIs('schedule.public');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $team ? route('schedule.show', $this->resource, absolute: false) : route('schedule.public', absolute: false),
            'pdf_url' => route('schedule.pdf', absolute: false),
            $this->mergeWhen($team, fn () => [
                'public_url' => route('schedule.public'),
            ]),
            'cities' => [
                'conference' => Conference::cityOf($this->timezone),
                'team' => Conference::cityOf($this->team_timezone),
            ],
            'days' => DayResource::collection($this->days),
        ];
    }
}
