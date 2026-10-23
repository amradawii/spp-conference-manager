<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('days', function (Blueprint $table) {
            $table->string('zoom_url')->nullable()->after('title');
            $table->text('note')->nullable()->after('zoom_url');
        });

        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->renameColumn('journal_moderator', 'chair');
        });

        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->string('co_chair')->nullable()->after('chair');
            $table->dropColumn('spp_moderator');
        });
    }

    public function down(): void
    {
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->string('spp_moderator')->nullable()->after('ends_at');
            $table->dropColumn('co_chair');
        });

        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->renameColumn('chair', 'journal_moderator');
        });

        Schema::table('days', function (Blueprint $table) {
            $table->dropColumn(['zoom_url', 'note']);
        });
    }
};
