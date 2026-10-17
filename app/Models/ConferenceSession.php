<?php

namespace App\Models;

use App\Enums\SessionKind;
use Database\Factories\ConferenceSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ConferenceSession extends Model
{
    /** @use HasFactory<ConferenceSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'kind',
        'number',
        'journal',
        'title',
        'starts_at',
        'ends_at',
        'spp_moderator',
        'journal_moderator',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'kind' => SessionKind::class,
            'number' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Items are removed by the foreign key cascade, which skips model events,
        // so their notes (a polymorphic relation without a foreign key) go here.
        static::deleting(function (ConferenceSession $session): void {
            Note::query()
                ->where('notable_type', 'item')
                ->whereIn('notable_id', AgendaItem::query()->where('conference_session_id', $session->id)->select('id'))
                ->delete();
            $session->notes()->delete();
        });
    }

    /**
     * @param  Builder<ConferenceSession>  $query
     */
    public function scopeForConference(Builder $query, Conference $conference): void
    {
        $query->whereHas('day', fn (Builder $day) => $day->where('conference_id', $conference->id));
    }

    /** @return BelongsTo<Day, $this> */
    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    /** @return HasMany<AgendaItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AgendaItem::class)
            ->chaperone('conferenceSession')
            ->orderByRaw('starts_at is null')
            ->orderBy('starts_at')
            ->orderBy('position');
    }

    /** @return MorphMany<Note, $this> */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest('id');
    }
}
