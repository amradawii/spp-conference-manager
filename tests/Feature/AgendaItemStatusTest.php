<?php

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Support\Editor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaItemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_speaker_status_change_is_saved_with_the_editor_name(): void
    {
        $item = AgendaItem::factory()->create();
        $conference = $item->conferenceSession->day->conference;

        $this->withCookie(Editor::COOKIE, 'Noha')
            ->from(route('schedule.show', $conference))
            ->patch(route('items.status', [$conference, $item]), ['speaker_status' => 'not_attending'])
            ->assertRedirect(route('schedule.show', $conference))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', 'Status saved');

        $item->refresh();
        $this->assertSame('not_attending', $item->speaker_status->value);
        $this->assertSame('pending', $item->assets_status->value);
        $this->assertSame('Noha', $item->updated_by);
    }

    public function test_assets_status_change_leaves_the_speaker_status_alone(): void
    {
        $item = AgendaItem::factory()->create(['speaker_status' => 'confirmed']);
        $conference = $item->conferenceSession->day->conference;

        $this->withCookie(Editor::COOKIE, 'Noha')
            ->patch(route('items.status', [$conference, $item]), ['assets_status' => 'ready'])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('ready', $item->assets_status->value);
        $this->assertSame('confirmed', $item->speaker_status->value);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $item = AgendaItem::factory()->create();
        $conference = $item->conferenceSession->day->conference;

        $this->withCookie(Editor::COOKIE, 'Noha')
            ->patch(route('items.status', [$conference, $item]), ['speaker_status' => 'maybe', 'assets_status' => 'not_needed'])
            ->assertSessionHasErrors(['speaker_status', 'assets_status']);

        $this->withCookie(Editor::COOKIE, 'Noha')
            ->patch(route('items.status', [$conference, $item]), [])
            ->assertSessionHasErrors(['speaker_status', 'assets_status']);
    }

    public function test_an_item_of_another_conference_cannot_be_changed(): void
    {
        $mine = AgendaItem::factory()->create();
        $other = AgendaItem::factory()->create();

        $this->withCookie(Editor::COOKIE, 'Noha')
            ->patch(route('items.status', [$mine->conferenceSession->day->conference, $other]), ['speaker_status' => 'confirmed'])
            ->assertNotFound();

        $this->assertSame('pending', $other->fresh()->speaker_status->value);
    }
}
