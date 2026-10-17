<?php

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Note;
use App\Support\Editor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_can_be_added_to_items_and_sessions(): void
    {
        $item = AgendaItem::factory()->create();
        $session = $item->conferenceSession;
        $conference = $session->day->conference;

        $this->withCookie(Editor::COOKIE, 'Rania')
            ->post(route('notes.store', $conference), ['notable_type' => 'item', 'notable_id' => $item->id, 'body' => ' Slides promised for Thursday '])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', 'Note added');

        $this->withCookie(Editor::COOKIE, 'Rania')
            ->post(route('notes.store', $conference), ['notable_type' => 'session', 'notable_id' => $session->id, 'body' => 'Moderator swap'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Slides promised for Thursday', $item->notes()->sole()->body);
        $this->assertSame('Rania', $item->notes()->sole()->author_name);
        $this->assertSame('Moderator swap', $session->notes()->sole()->body);
    }

    public function test_a_note_needs_text_and_a_known_target(): void
    {
        $item = AgendaItem::factory()->create();
        $conference = $item->conferenceSession->day->conference;

        $this->withCookie(Editor::COOKIE, 'Rania')
            ->post(route('notes.store', $conference), ['notable_type' => 'user', 'notable_id' => $item->id, 'body' => ''])
            ->assertSessionHasErrors(['notable_type', 'body']);
    }

    public function test_notes_cannot_reach_another_conference(): void
    {
        $mine = AgendaItem::factory()->create();
        $other = AgendaItem::factory()->create();
        $otherNote = Note::factory()->create(['notable_id' => $other->id]);
        $conference = $mine->conferenceSession->day->conference;

        $this->withCookie(Editor::COOKIE, 'Rania')
            ->post(route('notes.store', $conference), ['notable_type' => 'item', 'notable_id' => $other->id, 'body' => 'Sneaky'])
            ->assertNotFound();

        $this->withCookie(Editor::COOKIE, 'Rania')
            ->delete(route('notes.destroy', [$conference, $otherNote]))
            ->assertNotFound();

        $this->assertModelExists($otherNote);
        $this->assertSame(0, $other->notes()->where('body', 'Sneaky')->count());
    }

    public function test_a_note_can_be_deleted(): void
    {
        $note = Note::factory()->create();
        $conference = $note->notable->conferenceSession->day->conference;

        $this->withCookie(Editor::COOKIE, 'Rania')
            ->delete(route('notes.destroy', [$conference, $note]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', 'Note deleted');

        $this->assertModelMissing($note);
    }
}
