<?php

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_the_newest_conference_read_only(): void
    {
        AgendaItem::factory()->create(['speaker_name' => 'Prof. Old']);
        $item = AgendaItem::factory()->create(['speaker_name' => 'Prof. New', 'emails' => ['new@example.com']]);
        $conference = $item->conferenceSession->day->conference;

        $this->get('/')
            ->assertOk()
            ->assertSee('Prof. New')
            ->assertDontSee('Prof. Old')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Schedule/Public')
                ->where('conference.name', $conference->name)
                ->where('conference.url', '/')
                ->where('conference.days.0.sessions.0.items.0.emails', ['new@example.com'])
                ->has('conference.days.0.sessions.0.spp_moderator'));
    }

    public function test_home_page_hides_the_team_link_and_internal_fields(): void
    {
        $item = AgendaItem::factory()->create(['updated_by' => 'Noha']);
        $session = $item->conferenceSession;
        $session->forceFill(['updated_by' => 'Sara'])->save();
        Note::factory()->create(['notable_id' => $item->id, 'body' => 'Internal reminder']);
        $conference = $session->day->conference;

        $this->get('/')
            ->assertDontSee($conference->access_token)
            ->assertDontSee('Internal reminder')
            ->assertDontSee('Noha')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('conference.public_url')
                ->missing('conference.days.0.sessions.0.notes')
                ->missing('conference.days.0.sessions.0.updated_by')
                ->missing('conference.days.0.sessions.0.items.0.speaker_status')
                ->missing('conference.days.0.sessions.0.items.0.assets_status')
                ->missing('conference.days.0.sessions.0.items.0.updated_by')
                ->missing('conference.days.0.sessions.0.items.0.notes'));
    }

    public function test_nothing_can_be_changed_without_the_team_link(): void
    {
        $item = AgendaItem::factory()->create();

        $this->withCookie('editor_name', 'Visitor')
            ->patch("/items/{$item->id}/status", ['speaker_status' => 'confirmed'])
            ->assertNotFound();

        $this->assertSame('pending', $item->fresh()->speaker_status->value);
    }

    public function test_home_page_says_the_programme_is_coming_when_there_is_no_conference(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home')->missing('conference'));
    }

    public function test_home_page_is_not_framed_cached_or_indexed(): void
    {
        Conference::factory()->create();

        $this->get('/')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Cache-Control', 'no-store, private');
    }
}
