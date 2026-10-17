<?php

namespace Database\Factories;

use App\Models\Conference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conference>
 */
class ConferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Conference',
            'timezone' => 'Europe/Prague',
            'team_timezone' => 'Africa/Cairo',
        ];
    }
}
