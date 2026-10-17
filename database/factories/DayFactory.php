<?php

namespace Database\Factories;

use App\Models\Conference;
use App\Models\Day;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Day>
 */
class DayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conference_id' => Conference::factory(),
            'date' => fake()->unique()->dateTimeBetween('+1 week', '+1 year')->format('Y-m-d'),
            'title' => fake()->words(3, true),
        ];
    }
}
