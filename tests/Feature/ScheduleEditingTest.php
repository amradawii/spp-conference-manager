<?php

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Day;
use App\Models\Note;
use App\Support\Editor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ScheduleEditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withCookie(Editor::COOKIE, 'Yomna');
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(array $overrides = []): array
    {
        return [
            'kind' => 'session',
            'number' => 9,
            'journal' => 'Journal of Testing (JT)',
            'title' => 'Testing in Practice',
            'starts_at' => '16:30',
            'ends_at' => '17:30',
            'chair' => 'Dr. Ahmed Sharf',
            'co_chair' => null,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(array $overrides = []): array
    {
        return [
            'type' => 'keynote',
            'label' => 'Keynote 1',
            'starts_at' => '09:00',
            'ends_at' => '09:15',
            'speaker_name' => 'Prof. New Speaker',
            'speaker_role' => 'Editor-in-Chief',
            'affiliation' => null,
            'emails' => ['new@example.com', ' '],
            'title' => 'A new topic',
            ...$overrides,
        ];
    }

    public function test_a_session_can_be_added_to_a_day(): void
    {
        $day = Day::factory()->create();

        $this->post(route('sessions.store', $day->conference), [...$this->sessionPayload(), 'day_id' => $day->id])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', 'Session added');

        $session = $day->sessions()->sole();
        $this->assertSame(9, $session->number);
        $this->assertSame('Yomna', $session->updated_by);
    }

    public function test_a_session_cannot_be_added_to_another_conferences_day(): void
    {
        $conference = Conference::factory()->create();
        $foreignDay = Day::factory()->create();

        $this->post(route('sessions.store', $conference), [...$this->sessionPayload(), 'day_id' => $foreignDay->id])
            ->assertSessionHasErrors('day_id');

        $this->assertSame(0, ConferenceSession::count());
    }

    public function test_ceremonies_have_no_number_and_need_a_title(): void
    {
        $day = Day::factory()->create();

        $this->post(route('sessions.store', $day->conference), [...$this->sessionPayload(['kind' => 'ceremony', 'journal' => null, 'title' => '']), 'day_id' => $day->id])
            ->assertSessionHasErrors('title');

        $this->post(route('sessions.store', $day->conference), [...$this->sessionPayload(['kind' => 'ceremony', 'journal' => null, 'title' => 'Awards']), 'day_id' => $day->id])
            ->assertSessionHasNoErrors();

        $this->assertNull($day->sessions()->sole()->number);
    }

    public function test_session_times_must_be_in_order(): void
    {
        $day = Day::factory()->create();

        $this->post(route('sessions.store', $day->conference), [...$this->sessionPayload(['starts_at' => '10:00', 'ends_at' => '09:00']), 'day_id' => $day->id])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_a_session_can_be_updated(): void
    {
        $session = ConferenceSession::factory()->create();

        $this->put(route('sessions.update', [$session->day->conference, $session]), [
            ...$this->sessionPayload(['co_chair' => 'Prof. Magda EL-Massik']),
            'expected_updated_at' => $session->updated_at->toIso8601String(),
        ])->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertSame('Prof. Magda EL-Massik', $session->co_chair);
        $this->assertSame('Yomna', $session->updated_by);
    }

    public function test_an_outdated_session_form_does_not_overwrite_newer_changes(): void
    {
        $session = ConferenceSession::factory()->create(['chair' => 'Prof. Nader Shahata']);
        $openedAt = $session->updated_at->toIso8601String();

        Date::setTestNow(now()->addMinute());
        $session->update(['chair' => 'Prof. Kamal Alameh', 'updated_by' => 'Noha']);

        $this->put(route('sessions.update', [$session->day->conference, $session]), [
            ...$this->sessionPayload(['chair' => 'Prof. Nour F. Attia']),
            'expected_updated_at' => $openedAt,
        ])->assertSessionHasErrors('stale');

        $this->assertSame('Prof. Kamal Alameh', $session->fresh()->chair);
    }

    public function test_deleting_a_session_removes_its_items_and_all_their_notes(): void
    {
        $item = AgendaItem::factory()->create();
        $session = $item->conferenceSession;
        $itemNote = Note::factory()->create(['notable_id' => $item->id]);
        $sessionNote = Note::factory()->create(['notable_type' => 'session', 'notable_id' => $session->id]);
        $unrelatedNote = Note::factory()->create();

        $this->delete(route('sessions.destroy', [$session->day->conference, $session]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', 'Session deleted');

        $this->assertModelMissing($session);
        $this->assertModelMissing($item);
        $this->assertModelMissing($itemNote);
        $this->assertModelMissing($sessionNote);
        $this->assertModelExists($unrelatedNote);
    }

    public function test_an_item_is_appended_to_its_session(): void
    {
        $existing = AgendaItem::factory()->create(['position' => 4]);
        $session = $existing->conferenceSession;

        $this->post(route('items.store', [$session->day->conference, $session]), $this->itemPayload())
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', 'Item added');

        $item = $session->items()->where('speaker_name', 'Prof. New Speaker')->sole();
        $this->assertSame(5, $item->position);
        $this->assertSame(['new@example.com'], $item->emails);
        $this->assertSame('pending', $item->speaker_status->value);
        $this->assertSame('pending', $item->assets_status->value);
        $this->assertSame('Yomna', $item->updated_by);
    }

    public function test_segments_drop_speaker_details_and_do_not_track_assets(): void
    {
        $session = ConferenceSession::factory()->create();

        $this->post(route('items.store', [$session->day->conference, $session]), $this->itemPayload([
            'type' => 'segment',
            'title' => 'Discussions & Closing (15 min)',
        ]))->assertSessionHasNoErrors();

        $segment = $session->items()->sole();
        $this->assertNull($segment->speaker_name);
        $this->assertNull($segment->emails);
        $this->assertSame('not_needed', $segment->assets_status->value);
    }

    public function test_item_validation(): void
    {
        $session = ConferenceSession::factory()->create();
        $url = route('items.store', [$session->day->conference, $session]);

        $this->post($url, $this->itemPayload(['speaker_name' => '']))->assertSessionHasErrors('speaker_name');
        $this->post($url, $this->itemPayload(['type' => 'segment', 'title' => '']))->assertSessionHasErrors('title');
        $this->post($url, $this->itemPayload(['starts_at' => '09:30', 'ends_at' => '09:00']))->assertSessionHasErrors('ends_at');
        $this->post($url, $this->itemPayload(['starts_at' => '09:30', 'ends_at' => null]))->assertSessionHasErrors('ends_at');
        $this->post($url, $this->itemPayload(['emails' => ['not-an-email']]))->assertSessionHasErrors('emails.0');

        $this->post($url, $this->itemPayload(['starts_at' => null, 'ends_at' => null, 'title' => null]))->assertSessionHasNoErrors();
    }

    public function test_changing_a_segment_into_a_keynote_starts_tracking_assets(): void
    {
        $segment = AgendaItem::factory()->segment()->create();

        $this->put(route('items.update', [$segment->conferenceSession->day->conference, $segment]), [
            ...$this->itemPayload(),
            'expected_updated_at' => $segment->updated_at->toIso8601String(),
        ])->assertSessionHasNoErrors();

        $segment->refresh();
        $this->assertSame('keynote', $segment->type->value);
        $this->assertSame('pending', $segment->assets_status->value);
    }

    public function test_editing_details_keeps_the_existing_statuses(): void
    {
        $item = AgendaItem::factory()->create(['speaker_status' => 'confirmed', 'assets_status' => 'ready']);

        $this->put(route('items.update', [$item->conferenceSession->day->conference, $item]), [
            ...$this->itemPayload(['title' => 'Updated topic']),
            'expected_updated_at' => $item->updated_at->toIso8601String(),
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('Updated topic', $item->title);
        $this->assertSame('confirmed', $item->speaker_status->value);
        $this->assertSame('ready', $item->assets_status->value);
    }

    public function test_an_outdated_item_form_does_not_overwrite_newer_changes(): void
    {
        $item = AgendaItem::factory()->create(['title' => 'Original']);
        $openedAt = $item->updated_at->toIso8601String();

        Date::setTestNow(now()->addMinute());
        $item->update(['title' => 'Newer', 'updated_by' => 'Noha']);

        $this->put(route('items.update', [$item->conferenceSession->day->conference, $item]), [
            ...$this->itemPayload(['title' => 'Mine']),
            'expected_updated_at' => $openedAt,
        ])->assertSessionHasErrors('stale');

        $this->assertStringStartsWith('Noha changed this at', session('errors')->first('stale'));
        $this->assertSame('Newer', $item->fresh()->title);
    }

    public function test_an_item_can_be_deleted_with_its_notes(): void
    {
        $item = AgendaItem::factory()->create();
        $note = Note::factory()->create(['notable_id' => $item->id]);

        $this->delete(route('items.destroy', [$item->conferenceSession->day->conference, $item]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', 'Item deleted');

        $this->assertModelMissing($item);
        $this->assertModelMissing($note);
    }

    public function test_records_of_another_conference_cannot_be_edited_or_deleted(): void
    {
        $conference = Conference::factory()->create();
        $foreignItem = AgendaItem::factory()->create();
        $foreignSession = $foreignItem->conferenceSession;

        $this->put(route('items.update', [$conference, $foreignItem]), $this->itemPayload())->assertNotFound();
        $this->delete(route('items.destroy', [$conference, $foreignItem]))->assertNotFound();
        $this->post(route('items.store', [$conference, $foreignSession]), $this->itemPayload())->assertNotFound();
        $this->put(route('sessions.update', [$conference, $foreignSession]), $this->sessionPayload())->assertNotFound();
        $this->delete(route('sessions.destroy', [$conference, $foreignSession]))->assertNotFound();

        $this->assertModelExists($foreignItem);
        $this->assertModelExists($foreignSession);
    }
}
