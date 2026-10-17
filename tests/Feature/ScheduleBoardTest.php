<?php

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Day;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScheduleBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_does_not_reveal_any_conference(): void
    {
        Conference::factory()->create(['name' => 'Secret Conference']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Secret Conference')
            ->assertInertia(fn (Assert $page) => $page->component('Home')->missing('conference'));
    }

    public function test_board_renders_for_a_valid_token(): void
    {
        $item = AgendaItem::factory()->create();
        $conference = $item->conferenceSession->day->conference;

        $this->get(route('schedule.show', $conference))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Schedule/Show')
                ->where('conference.name', $conference->name)
                ->where('conference.cities', ['conference' => 'Prague', 'team' => 'Cairo'])
                ->has('conference.days', 1)
                ->has('conference.days.0.sessions.0.items', 1)
                ->where('conference.days.0.sessions.0.items.0.speaker_name', $item->speaker_name)
                ->where('conference.days.0.sessions.0.items.0.speaker_status', 'pending')
                ->missing('conference.access_token'));
    }

    public function test_unknown_token_returns_not_found(): void
    {
        Conference::factory()->create();

        $this->get('/s/not-a-real-token')->assertNotFound();
    }

    public function test_board_pages_ask_search_engines_not_to_index(): void
    {
        $conference = Conference::factory()->create();

        $this->get(route('schedule.show', $conference))
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<meta name="referrer" content="same-origin">', false);
    }

    public function test_board_is_not_framed_cached_or_indexed(): void
    {
        $conference = Conference::factory()->create();

        $this->get(route('schedule.show', $conference))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_times_are_shown_in_conference_and_team_timezones_across_the_clock_change(): void
    {
        $conference = Conference::factory()->create();
        $saturday = Day::factory()->for($conference)->create(['date' => '2026-10-24']);
        $sunday = Day::factory()->for($conference)->create(['date' => '2026-10-25']);
        ConferenceSession::factory()->for($saturday)->create(['starts_at' => '09:00', 'ends_at' => '09:30']);
        ConferenceSession::factory()->for($sunday)->create(['starts_at' => '09:00', 'ends_at' => '09:30']);

        $this->get(route('schedule.show', $conference))
            ->assertInertia(fn (Assert $page) => $page
                ->where('conference.days.0.sessions.0.time', [
                    'start' => '09:00', 'end' => '09:30', 'team_start' => '10:00', 'team_end' => '10:30',
                ])
                ->where('conference.days.0.utc_offsets', ['conference' => '+02:00', 'team' => '+03:00'])
                ->where('conference.days.1.sessions.0.time', [
                    'start' => '09:00', 'end' => '09:30', 'team_start' => '11:00', 'team_end' => '11:30',
                ])
                ->where('conference.days.1.utc_offsets', ['conference' => '+01:00', 'team' => '+03:00']));
    }

    public function test_items_are_ordered_by_time_with_untimed_items_last(): void
    {
        $session = ConferenceSession::factory()->create();
        AgendaItem::factory()->for($session)->create(['label' => 'Untimed', 'starts_at' => null, 'ends_at' => null, 'position' => 1]);
        AgendaItem::factory()->for($session)->create(['label' => 'Late', 'starts_at' => '10:00', 'ends_at' => '10:15', 'position' => 2]);
        AgendaItem::factory()->for($session)->create(['label' => 'Early', 'starts_at' => '09:00', 'ends_at' => '09:15', 'position' => 3]);

        $this->get(route('schedule.show', $session->day->conference))
            ->assertInertia(fn (Assert $page) => $page
                ->where('conference.days.0.sessions.0.items.0.label', 'Early')
                ->where('conference.days.0.sessions.0.items.1.label', 'Late')
                ->where('conference.days.0.sessions.0.items.2.label', 'Untimed')
                ->where('conference.days.0.sessions.0.items.2.time', null));
    }

    public function test_notes_are_included_newest_first(): void
    {
        $item = AgendaItem::factory()->create();
        Note::factory()->create(['notable_id' => $item->id, 'body' => 'First']);
        Note::factory()->create(['notable_id' => $item->id, 'body' => 'Second']);

        $this->get(route('schedule.show', $item->conferenceSession->day->conference))
            ->assertInertia(fn (Assert $page) => $page
                ->has('conference.days.0.sessions.0.items.0.notes', 2)
                ->where('conference.days.0.sessions.0.items.0.notes.0.body', 'Second'));
    }

    public function test_a_token_only_shows_its_own_conference(): void
    {
        $mine = AgendaItem::factory()->create(['speaker_name' => 'Prof. Mine']);
        AgendaItem::factory()->create(['speaker_name' => 'Prof. Other']);

        $this->get(route('schedule.show', $mine->conferenceSession->day->conference))
            ->assertSee('Prof. Mine')
            ->assertDontSee('Prof. Other');
    }
}
