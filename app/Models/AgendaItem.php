<?php

namespace App\Models;

use App\Enums\AgendaItemType;
use App\Enums\AssetsStatus;
use App\Enums\SpeakerStatus;
use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AgendaItem extends Model
{
    /** @use HasFactory<AgendaItemFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'label',
        'starts_at',
        'ends_at',
        'position',
        'speaker_name',
        'speaker_role',
        'affiliation',
        'emails',
        'title',
        'speaker_status',
        'assets_status',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AgendaItemType::class,
            'speaker_status' => SpeakerStatus::class,
            'assets_status' => AssetsStatus::class,
            'emails' => 'array',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<ConferenceSession, $this> */
    public function conferenceSession(): BelongsTo
    {
        return $this->belongsTo(ConferenceSession::class);
    }

    /** @return MorphMany<Note, $this> */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest('id');
    }
}
