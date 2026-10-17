<?php

namespace Database\Factories;

use App\Enums\AgendaItemType;
use App\Enums\AssetsStatus;
use App\Enums\SpeakerStatus;
use App\Models\AgendaItem;
use App\Models\ConferenceSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaItem>
 */
class AgendaItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conference_session_id' => ConferenceSession::factory(),
            'type' => AgendaItemType::Keynote,
            'label' => 'Keynote 1',
            'starts_at' => '09:00',
            'ends_at' => '09:15',
            'position' => 1,
            'speaker_name' => 'Prof. '.fake()->name(),
            'speaker_role' => null,
            'affiliation' => null,
            'emails' => [fake()->safeEmail()],
            'title' => fake()->sentence(6),
            'speaker_status' => SpeakerStatus::Pending,
            'assets_status' => AssetsStatus::Pending,
        ];
    }

    public function segment(): static
    {
        return $this->state(fn () => [
            'type' => AgendaItemType::Segment,
            'label' => null,
            'speaker_name' => null,
            'emails' => null,
            'title' => 'Discussions & Closing (15 min)',
            'assets_status' => AssetsStatus::NotNeeded,
        ]);
    }
}
