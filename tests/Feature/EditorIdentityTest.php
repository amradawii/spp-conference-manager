<?php

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Conference;
use App\Support\Editor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditorIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_name_remembers_it_on_this_device(): void
    {
        $conference = Conference::factory()->create();
        $board = route('schedule.show', $conference);

        $this->from($board)
            ->post(route('editor.store', $conference), ['name' => '  Sara Ayman '])
            ->assertRedirect($board)
            ->assertCookie(Editor::COOKIE, 'Sara Ayman');

        $this->withCookie(Editor::COOKIE, 'Sara Ayman')
            ->get($board)
            ->assertInertia(fn (Assert $page) => $page->where('editor.name', 'Sara Ayman'));
    }

    public function test_a_name_is_required(): void
    {
        $conference = Conference::factory()->create();

        $this->post(route('editor.store', $conference), ['name' => ' '])
            ->assertSessionHasErrors('name')
            ->assertCookieMissing(Editor::COOKIE);
    }

    public function test_changes_are_refused_until_the_editor_gives_a_name(): void
    {
        $item = AgendaItem::factory()->create();
        $conference = $item->conferenceSession->day->conference;

        $this->from(route('schedule.show', $conference))
            ->patch(route('items.status', [$conference, $item]), ['speaker_status' => 'confirmed'])
            ->assertRedirect(route('schedule.show', $conference))
            ->assertSessionHasErrors('editor_name');

        $this->assertSame('pending', $item->fresh()->speaker_status->value);
    }

    public function test_board_shares_no_name_for_new_visitors(): void
    {
        $conference = Conference::factory()->create();

        $this->get(route('schedule.show', $conference))
            ->assertInertia(fn (Assert $page) => $page->where('editor.name', null));
    }
}
