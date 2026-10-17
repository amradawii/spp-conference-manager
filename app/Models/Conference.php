<?php

namespace App\Models;

use Database\Factories\ConferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conference extends Model
{
    /** @use HasFactory<ConferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'timezone',
        'team_timezone',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conference $conference): void {
            $conference->access_token ??= self::newAccessToken();
        });
    }

    public static function newAccessToken(): string
    {
        return Str::random(40);
    }

    public function rotateAccessToken(): void
    {
        $this->forceFill(['access_token' => self::newAccessToken()])->save();
    }

    public function shareUrl(): string
    {
        return route('schedule.show', $this);
    }

    /**
     * "Europe/Prague" becomes "Prague".
     */
    public static function cityOf(string $timezone): string
    {
        return str_replace('_', ' ', Str::afterLast($timezone, '/'));
    }

    /** @return HasMany<Day, $this> */
    public function days(): HasMany
    {
        return $this->hasMany(Day::class)->chaperone()->orderBy('date');
    }
}
