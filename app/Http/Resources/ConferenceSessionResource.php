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
            'spp_moderator' => $this->spp_moderator,
            'journal_moderator' => $this->journal_moderator,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'items' => AgendaItemResource::collection($this->items),
            'notes' => NoteResource::collection($this->notes),
        ];
    }
}
