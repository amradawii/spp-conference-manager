<?php

namespace App\Console\Commands;

use App\Models\Conference;
use Illuminate\Console\Command;

class ConferenceLink extends Command
{
    protected $signature = 'conference:link
        {conference? : Conference ID (defaults to the most recent conference)}
        {--rotate : Generate a new secret link. The old link stops working immediately}';

    protected $description = 'Print the secret team link and the read-only programme link';

    public function handle(): int
    {
        $id = $this->argument('conference');

        $conference = $id !== null
            ? Conference::find($id)
            : Conference::latest('id')->first();

        if ($conference === null) {
            $this->error('Conference not found. Seed one first: php artisan db:seed');

            return self::FAILURE;
        }

        if ($this->option('rotate')) {
            $conference->rotateAccessToken();
            $this->warn('A new link was generated. Share it with the team; the old link no longer works.');
        }

        $this->line($conference->name);
        $this->line('Team link (private, for editing):');
        $this->info($conference->shareUrl());
        $this->line('Read-only programme (safe to share with speakers and attendees; shows the newest conference):');
        $this->info(route('schedule.public'));

        return self::SUCCESS;
    }
}
