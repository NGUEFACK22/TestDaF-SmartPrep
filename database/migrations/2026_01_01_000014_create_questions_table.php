<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->string('type'); // multiple_choice, single_choice, true_false, matching, ...
            $table->unsignedInteger('position')->default(0);
            $table->text('prompt')->nullable();
            $table->decimal('points', 6, 2)->default(1);
            $table->json('data')->nullable();            // items, pairs, categories, gaps...
            $table->json('correct_answer')->nullable();  // structured correct answer
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->index(['exercise_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
