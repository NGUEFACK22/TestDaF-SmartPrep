<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->foreignId('attempt_exercise_id')->constrained('attempt_exercises')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('answer')->nullable();             // JSON ou texte brut
            $table->boolean('is_correct')->nullable();
            $table->decimal('points', 6, 2)->nullable();
            $table->string('correction_status')->default('pending'); // pending | corrected | manual
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_answers');
    }
};
