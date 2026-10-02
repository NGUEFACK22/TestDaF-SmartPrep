<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default('locked'); // locked | available | started | completed | expired
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_spent')->default(0);
            $table->boolean('answers_locked')->default(false);
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'exercise_id']);
            $table->index(['attempt_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_exercises');
    }
};
