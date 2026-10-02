<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('modell_test_id')->constrained('modell_tests')->cascadeOnDelete();
            $table->string('mode')->default('exam');   // exam | training
            $table->string('status')->default('in_progress'); // in_progress | completed | abandoned
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->foreignId('current_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('current_exercise_id')->nullable()->constrained('exercises')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
