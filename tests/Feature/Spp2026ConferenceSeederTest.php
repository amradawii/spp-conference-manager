<?php

namespace Tests\Feature;

use App\Enums\AgendaItemType;
use App\Enums\SessionKind;
use App\Enums\SpeakerStatus;
use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use Database\Seeders\Spp2026ConferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Spp2026ConferenceSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(Spp2026ConferenceSeeder::class);
    }

    public function test_it_seeds_the_programme_from_the_pdf(): void
    {
        $conference = Conference::sole();

        $this->assertSame(['2026-10-24', '2026-10-25'], $conference->days->map(fn ($day) => $day->date->toDateString())->all());
        $this->assertSame(range(1, 8), ConferenceSession::where('kind', SessionKind::Session)->orderBy('number')->pluck('number')->all());
        $this->assertSame(3, ConferenceSession::where('kind', SessionKind::Ceremony)->count());

        $this->assertSame(32, AgendaItem::where('type', AgendaItemType::Keynote)->count());
        $this->assertSame(2, AgendaItem::where('type', AgendaItemType::Address)->count());
        $this->assertSame(4, AgendaItem::where('type', AgendaItemType::Oral)->count());
        $this->assertSame(0, AgendaItem::where('speaker_status', '!=', SpeakerStatus::Pending)->count());
    }

    public function test_it_applies_the_corrections_listed_in_the_design_doc(): void
    {
        $sessionSix = ConferenceSession::where('number', 6)->sole();
        $this->assertTrue($sessionSix->items()->where('starts_at', '10:45')->where('ends_at', '11:30')->exists());
        $this->assertStringContainsString('Maged Wasfy', $sessionSix->journal_moderator);

        $sessionTwo = ConferenceSession::where('number', 2)->sole();
        $this->assertTrue($sessionTwo->items()->where('starts_at', '12:00')->where('ends_at', '12:15')->exists());

        $this->assertNull(ConferenceSession::where('number', 4)->sole()->spp_moderator);
        $this->assertSame(0, AgendaItem::where('speaker_role', 'like', '%Borad%')->count());

        $elseman = AgendaItem::where('speaker_name', 'Dr. Ahmed Mourtada Elseman')->sole();
        $this->assertCount(2, $elseman->emails);

        $this->assertNull(AgendaItem::where('speaker_name', 'Prof. Veera Rajendran')->sole()->title);
    }

    public function test_running_it_twice_does_not_duplicate_the_conference(): void
    {
        $this->seed(Spp2026ConferenceSeeder::class);

        $this->assertSame(1, Conference::count());
    }
}
