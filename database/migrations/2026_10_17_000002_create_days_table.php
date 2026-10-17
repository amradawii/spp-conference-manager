<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('title');
            $table->timestamps();

            $table->unique(['conference_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('days');
    }
};
