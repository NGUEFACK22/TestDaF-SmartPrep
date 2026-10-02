<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('writing_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->foreignId('attempt_exercise_id')->constrained('attempt_exercises')->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('locked')->default(false);
            $table->timestamps();

            $table->unique(['attempt_id', 'attempt_exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writing_submissions');
    }
};
