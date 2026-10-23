<?php

namespace Database\Factories;

use App\Enums\SessionKind;
use App\Models\ConferenceSession;
use App\Models\Day;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConferenceSession>
 */
class ConferenceSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'day_id' => Day::factory(),
            'kind' => SessionKind::Session,
            'number' => fake()->numberBetween(1, 9),
            'journal' => fake()->words(3, true).' Journal',
            'title' => fake()->sentence(5),
            'starts_at' => '09:00',
            'ends_at' => '10:30',
            'chair' => fake()->name(),
            'co_chair' => null,
        ];
    }

    public function ceremony(): static
    {
        return $this->state(fn () => [
            'kind' => SessionKind::Ceremony,
            'number' => null,
            'journal' => null,
            'title' => 'Opening Ceremony',
            'chair' => null,
        ]);
    }
}
