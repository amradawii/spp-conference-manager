<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Day extends Model
{
    /** @use HasFactory<DayFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'title',
        'zoom_url',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Conference, $this> */
    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    /** @return HasMany<ConferenceSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ConferenceSession::class)->chaperone()->orderBy('starts_at');
    }

    /**
     * Times are stored in the conference timezone; the team timezone is derived
     * per day because the two cities switch daylight saving time on different dates.
     *
     * @return array{start: string, end: string, team_start: string, team_end: string}|null
     */
    public function timeRange(?string $start, ?string $end): ?array
    {
        if ($start === null || $end === null) {
            return null;
        }

        return [
            'start' => $this->at($start)->format('H:i'),
            'end' => $this->at($end)->format('H:i'),
            'team_start' => $this->toTeamTime($start),
            'team_end' => $this->toTeamTime($end),
        ];
    }

    public function toTeamTime(string $time): string
    {
        return $this->at($time)->setTimezone($this->conference->team_timezone)->format('H:i');
    }

    /**
     * @return array{conference: string, team: string}
     */
    public function utcOffsets(): array
    {
        $noon = $this->at('12:00');

        return [
            'conference' => $noon->format('P'),
            'team' => $noon->setTimezone($this->conference->team_timezone)->format('P'),
        ];
    }

    private function at(string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date->format('Y-m-d').' '.$time, $this->conference->timezone);
    }
}
