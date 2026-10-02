<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->nullable()->constrained('exercises')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->decimal('points', 6, 2)->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solutions');
    }
};
