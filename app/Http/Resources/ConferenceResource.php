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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => route('schedule.show', $this->resource, absolute: false),
            'cities' => [
                'conference' => Conference::cityOf($this->timezone),
                'team' => Conference::cityOf($this->team_timezone),
            ],
            'days' => DayResource::collection($this->days),
        ];
    }
}
