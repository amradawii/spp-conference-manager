<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    protected $fillable = [
        'author_name',
        'body',
    ];

    /**
     * @param  Builder<Note>  $query
     */
    public function scopeForConference(Builder $query, Conference $conference): void
    {
        $query->whereHasMorph('notable', [ConferenceSession::class, AgendaItem::class], function (Builder $notable) use ($conference): void {
            $notable->forConference($conference);
        });
    }

    /** @return MorphTo<Model, $this> */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
