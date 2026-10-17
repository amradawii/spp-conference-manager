<?php

namespace Database\Factories;

use App\Models\AgendaItem;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notable_type' => 'item',
            'notable_id' => AgendaItem::factory(),
            'author_name' => fake()->firstName(),
            'body' => fake()->sentence(),
        ];
    }
}
