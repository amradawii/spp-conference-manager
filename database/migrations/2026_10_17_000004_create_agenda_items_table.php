<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_session_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('label')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('speaker_name')->nullable();
            $table->string('speaker_role')->nullable();
            $table->text('affiliation')->nullable();
            $table->json('emails')->nullable();
            $table->text('title')->nullable();
            $table->string('speaker_status', 20)->default('pending');
            $table->string('assets_status', 20)->default('pending');
            $table->string('updated_by', 60)->nullable();
            $table->timestamps();

            $table->index(['conference_session_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};
