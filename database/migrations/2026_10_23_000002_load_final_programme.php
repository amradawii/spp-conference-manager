<?php

use App\Models\Conference;
use Database\Seeders\Spp2026ConferenceSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Replaces the draft 8 programme with the final one on databases that already
 * have it. Fresh databases get the final programme from the seeder instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        $seeder = new Spp2026ConferenceSeeder;

        foreach (Conference::where('name', Spp2026ConferenceSeeder::DRAFT_NAME)->get() as $conference) {
            $seeder->replaceProgramme($conference);
        }
    }

    public function down(): void
    {
        //
    }
};
