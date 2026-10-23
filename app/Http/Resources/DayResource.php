<?php

namespace App\Http\Resources;

use App\Models\Day;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Day */
class DayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'title' => $this->title,
            'zoom_url' => $this->zoom_url,
            'note' => $this->note,
            'time' => $this->timeRange($this->sessions->min('starts_at'), $this->sessions->max('ends_at')),
            'utc_offsets' => $this->utcOffsets(),
            'sessions' => ConferenceSessionResource::collection($this->sessions),
        ];
    }
}
