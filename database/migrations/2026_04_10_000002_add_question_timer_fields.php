<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timer par question : chaque question possède son temps propre (en base),
 * stocké dans questions.time_limit_seconds (null = suit le temps de la tâche).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedInteger('time_limit_seconds')->nullable()->after('points');
        });

        Schema::table('attempt_exercises', function (Blueprint $table) {
            // Index de la question courante (navigation intra-tâche séquentielle).
            $table->unsignedInteger('current_question_index')->default(0)->after('answers_locked');
            // Instant limite serveur de la question courante.
            $table->timestamp('current_question_expires_at')->nullable()->after('current_question_index');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('time_limit_seconds');
        });

        Schema::table('attempt_exercises', function (Blueprint $table) {
            $table->dropColumn(['current_question_index', 'current_question_expires_at']);
        });
    }
};
