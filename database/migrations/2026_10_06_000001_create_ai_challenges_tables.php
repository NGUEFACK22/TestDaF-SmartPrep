<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Espace Élite (Défi IA) : débloqué après 2 scores parfaits consécutifs
     * sur un même Modelltest, la IA génère des QCM inédits calibrés sur les
     * compétences faibles du candidat (min 20 questions, chrono par question).
     */
    public function up(): void
    {
        Schema::create('challenge_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('modell_test_id')->constrained('modell_tests')->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'modell_test_id']);
            $table->index(['user_id']);
        });

        Schema::create('ai_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('modell_test_id')->nullable()->constrained('modell_tests')->nullOnDelete();
            $table->string('skill')->default('lesen');
            $table->string('status')->default('generating'); // generating|ready|failed
            $table->string('level', 4)->default('C1');
            $table->json('weak_snapshot')->nullable(); // faiblesses analysées au moment de la demande
            $table->foreignId('generated_test_id')->nullable()->constrained('modell_tests')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_challenges');
        Schema::dropIfExists('challenge_unlocks');
    }
};
