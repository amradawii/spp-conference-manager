<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('session');
            $table->unsignedTinyInteger('number')->nullable();
            $table->string('journal')->nullable();
            $table->string('title')->nullable();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('spp_moderator')->nullable();
            $table->string('journal_moderator')->nullable();
            $table->string('updated_by', 60)->nullable();
            $table->timestamps();

            $table->index(['day_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_sessions');
    }
};
