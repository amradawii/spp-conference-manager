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

    public function test_programme_downloads_as_a_pdf(): void
    {
        $item = AgendaItem::factory()->create();
        $conference = $item->conferenceSession->day->conference;
        $conference->update(['name' => 'SPP Online Conference 2026']);

        $response = $this->get(route('schedule.pdf'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('spp-online-conference-2026-programme.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pdf_shows_the_programme_without_internal_details(): void
    {
        $item = AgendaItem::factory()->create(['speaker_name' => 'Prof. Nader Shahata', 'title' => null, 'updated_by' => 'Noha']);
        $item->conferenceSession->forceFill(['kind' => 'session', 'number' => 1, 'journal_moderator' => null])->save();
        Note::factory()->create(['notable_id' => $item->id, 'body' => 'Internal reminder']);
        $conference = Conference::with('days.sessions.items')->first();

        $html = view('pdf.programme', ['conference' => $conference])->render();

        $this->assertStringContainsString('Prof. Nader Shahata', $html);
        $this->assertStringContainsString('Topic TBD', $html);
        $this->assertStringContainsString('To be announced', $html);
        $this->assertStringNotContainsString('Internal reminder', $html);
        $this->assertStringNotContainsString('Noha', $html);
        $this->assertStringNotContainsString('Pending', $html);
    }

    public function test_pdf_is_not_found_when_there_is_no_conference(): void
    {
        $this->get(route('schedule.pdf'))->assertNotFound();
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
