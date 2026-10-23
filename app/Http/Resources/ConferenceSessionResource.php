<?php

namespace App\Http\Resources;

use App\Models\ConferenceSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConferenceSession */
class ConferenceSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'number' => $this->number,
            'journal' => $this->journal,
            'title' => $this->title,
            'time' => $this->day->timeRange($this->starts_at, $this->ends_at),
            'chair' => $this->chair,
            'co_chair' => $this->co_chair,
            'items' => AgendaItemResource::collection($this->items),
            $this->mergeUnless($request->routeIs('schedule.public'), fn () => [
                'updated_by' => $this->updated_by,
                'updated_at' => $this->updated_at?->toIso8601String(),
                'notes' => NoteResource::collection($this->notes),
            ]),
        ];
    }
}
