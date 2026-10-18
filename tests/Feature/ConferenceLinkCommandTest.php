<?php

namespace Tests\Feature;

use App\Models\Conference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConferenceLinkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prints_the_team_link_and_the_read_only_link(): void
    {
        $conference = Conference::factory()->create();

        $this->artisan('conference:link')
            ->expectsOutputToContain('/s/'.$conference->access_token)
            ->expectsOutput(url('/'))
            ->assertSuccessful();
    }

    public function test_rotating_replaces_the_token_and_the_old_link_stops_working(): void
    {
        $conference = Conference::factory()->create();
        $oldToken = $conference->access_token;

        $this->artisan('conference:link', ['conference' => $conference->id, '--rotate' => true])->assertSuccessful();

        $newToken = $conference->fresh()->access_token;
        $this->assertNotSame($oldToken, $newToken);
        $this->assertSame(40, strlen($newToken));

        $this->get('/s/'.$oldToken)->assertNotFound();
        $this->get('/s/'.$newToken)->assertOk();
    }

    public function test_it_fails_when_there_is_no_conference(): void
    {
        $this->artisan('conference:link')->assertFailed();
    }
}
