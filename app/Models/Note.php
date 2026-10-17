<?php

namespace App\Models;

use Database\Factories\NoteFactory;
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

    /** @return MorphTo<Model, $this> */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
