<?php

namespace App\Http\Resources;

use App\Models\AgendaItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AgendaItem */
class AgendaItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->label,
            'time' => $this->conferenceSession->day->timeRange($this->starts_at, $this->ends_at),
            'speaker_name' => $this->speaker_name,
            'speaker_role' => $this->speaker_role,
            'affiliation' => $this->affiliation,
            'emails' => $this->emails ?? [],
            'title' => $this->title,
            'speaker_status' => $this->speaker_status,
            'assets_status' => $this->assets_status,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'notes' => NoteResource::collection($this->notes),
        ];
    }
}
