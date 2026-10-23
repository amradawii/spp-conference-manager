<?php

namespace Tests\Feature;

use App\Enums\AgendaItemType;
use App\Enums\AssetsStatus;
use App\Enums\SessionKind;
use App\Enums\SpeakerStatus;
use App\Models\AgendaItem;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Day;
use App\Models\Note;
use Database\Seeders\Spp2026ConferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Spp2026ConferenceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_final_programme(): void
    {
        $this->seed(Spp2026ConferenceSeeder::class);
        $conference = Conference::sole();

        $this->assertSame(Spp2026ConferenceSeeder::NAME, $conference->name);
        $this->assertSame(['2026-10-24', '2026-10-25'], $conference->days->map(fn ($day) => $day->date->toDateString())->all());
        $this->assertSame('https://us06web.zoom.us/j/81647609918', $conference->days[0]->zoom_url);
        $this->assertStringContainsString('winter time', $conference->days[1]->note);

        $this->assertSame(range(1, 9), ConferenceSession::where('kind', SessionKind::Session)->orderBy('number')->pluck('number')->all());
        $this->assertSame(2, ConferenceSession::where('kind', SessionKind::Ceremony)->count());
        $this->assertSame(0, ConferenceSession::where('kind', SessionKind::Session)->whereNull('chair')->count());

        $this->assertSame(35, AgendaItem::where('type', AgendaItemType::Keynote)->count());
        $this->assertSame(3, AgendaItem::where('type', AgendaItemType::Address)->count());
        $this->assertSame(12, AgendaItem::where('type', AgendaItemType::Oral)->count());
        $this->assertSame(0, AgendaItem::whereNotNull('emails')->count());
        $this->assertSame(0, AgendaItem::where('speaker_status', '!=', SpeakerStatus::Pending)->count());
    }

    public function test_it_keeps_the_chairs_journals_and_oral_sections_of_the_pdf(): void
    {
        $this->seed(Spp2026ConferenceSeeder::class);

        $sessionSix = ConferenceSession::where('number', 6)->sole();
        $this->assertSame('Prof. Tarek Abdel-Fattah', $sessionSix->chair);
        $this->assertSame('Prof. Hesham Hamed', $sessionSix->co_chair);
        $this->assertSame('Advanced Carbon Journal (ACJ)', $sessionSix->journal);

        $sessionEight = ConferenceSession::where('number', 8)->sole();
        $this->assertTrue($sessionEight->items()->where('label', 'Oral Presentations')->where('starts_at', '12:45')->exists());
        $this->assertSame(4, $sessionEight->items()->where('type', AgendaItemType::Oral)->count());

        $this->assertSame(0, AgendaItem::where('affiliation', 'like', '%Borad%')->count());
        $this->assertSame('Session Chair & Keynote Speaker 1', AgendaItem::where('speaker_name', 'Prof. Nader Shahata')->sole()->label);
    }

    public function test_running_it_twice_does_not_duplicate_the_conference(): void
    {
        $this->seed(Spp2026ConferenceSeeder::class);
        $this->seed(Spp2026ConferenceSeeder::class);

        $this->assertSame(1, Conference::count());
    }

    public function test_replacing_the_draft_keeps_the_team_link_and_the_teams_progress(): void
    {
        $conference = Conference::factory()->create(['name' => Spp2026ConferenceSeeder::DRAFT_NAME]);
        $token = $conference->access_token;
        $session = ConferenceSession::factory()
            ->for(Day::factory()->for($conference)->state(['date' => '2026-10-24']))
            ->create(['title' => 'Carbon Capture and Carbon Footprint']);
        Note::factory()->create(['notable_type' => 'session', 'notable_id' => $session->id, 'author_name' => 'Sara', 'body' => 'Check the slides']);

        $attia = AgendaItem::factory()->for($session)->create([
            'speaker_name' => 'Prof. Nour F. Attia',
            'speaker_status' => SpeakerStatus::Confirmed,
            'assets_status' => AssetsStatus::Ready,
            'updated_by' => 'Noha',
        ]);
        Note::factory()->create(['notable_id' => $attia->id, 'author_name' => 'Noha', 'body' => 'Sent the Zoom link']);
        Note::factory()->create(['notable_id' => $attia->id, 'author_name' => 'PDF draft 8', 'body' => 'Old draft remark']);
        AgendaItem::factory()->for($session)->create(['speaker_name' => 'Prof. Ayman F. Abou Hdid', 'speaker_status' => SpeakerStatus::NotAttending]);
        AgendaItem::factory()->for($session)->create(['speaker_name' => 'Prof. Vikas Pathak']);

        (new Spp2026ConferenceSeeder)->replaceProgramme($conference);

        $conference->refresh();
        $this->assertSame(Spp2026ConferenceSeeder::NAME, $conference->name);
        $this->assertSame($token, $conference->access_token);
        $this->assertSame(1, Conference::count());
        $this->assertSame(2, $conference->days()->count());

        $newAttia = AgendaItem::where('speaker_name', 'Prof. Nour F. Attia')->sole();
        $this->assertSame(SpeakerStatus::Confirmed, $newAttia->speaker_status);
        $this->assertSame(AssetsStatus::Ready, $newAttia->assets_status);
        $this->assertSame('Noha', $newAttia->updated_by);
        $this->assertSame(['Sent the Zoom link'], $newAttia->notes->pluck('body')->all());

        $this->assertSame(SpeakerStatus::NotAttending, AgendaItem::where('speaker_name', 'Prof. Ayman F. Abou Hadid')->sole()->speaker_status);
        $this->assertFalse(AgendaItem::where('speaker_name', 'Prof. Vikas Pathak')->exists());
        $this->assertSame(['Check the slides'], ConferenceSession::where('number', 3)->sole()->notes->pluck('body')->all());
        $this->assertSame(2, Note::count());
        $this->assertSame(2, AgendaItem::where('speaker_status', '!=', SpeakerStatus::Pending)->count());
    }
}
