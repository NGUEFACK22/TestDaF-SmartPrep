<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index de performance + RGPD : accélère dashboard, résultats,
     * correction et purge des médias sur Neon/MySQL sans changer le modèle.
     */
    public function up(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->index(['user_id', 'is_correct'], 'user_answers_user_correct_idx');
            $table->index(['attempt_exercise_id'], 'user_answers_ae_idx');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->index(['skill', 'percentage'], 'results_skill_pct_idx');
        });

        Schema::table('ai_evaluations', function (Blueprint $table) {
            $table->index(['attempt_id', 'status'], 'ai_eval_attempt_status_idx');
            $table->index(['user_id', 'status'], 'ai_eval_user_status_idx');
        });

        Schema::table('exam_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'exam_logs_user_created_idx');
            $table->index(['event', 'created_at'], 'exam_logs_event_created_idx');
        });

        Schema::table('attempts', function (Blueprint $table) {
            $table->index(['modell_test_id', 'status'], 'attempts_test_status_idx');
            $table->index(['completed_at'], 'attempts_completed_idx');
        });

        Schema::table('attempt_exercises', function (Blueprint $table) {
            $table->index(['exercise_id', 'status'], 'ae_exercise_status_idx');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index(['type', 'difficulty'], 'questions_type_diff_idx');
        });

        Schema::table('writing_submissions', function (Blueprint $table) {
            $table->index(['user_id', 'locked'], 'writing_user_locked_idx');
        });

        Schema::table('speaking_submissions', function (Blueprint $table) {
            $table->index(['user_id', 'locked'], 'speaking_user_locked_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropIndex('user_answers_user_correct_idx');
            $table->dropIndex('user_answers_ae_idx');
        });
        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex('results_skill_pct_idx');
        });
        Schema::table('ai_evaluations', function (Blueprint $table) {
            $table->dropIndex('ai_eval_attempt_status_idx');
            $table->dropIndex('ai_eval_user_status_idx');
        });
        Schema::table('exam_logs', function (Blueprint $table) {
            $table->dropIndex('exam_logs_user_created_idx');
            $table->dropIndex('exam_logs_event_created_idx');
        });
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropIndex('attempts_test_status_idx');
            $table->dropIndex('attempts_completed_idx');
        });
        Schema::table('attempt_exercises', function (Blueprint $table) {
            $table->dropIndex('ae_exercise_status_idx');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_type_diff_idx');
        });
        Schema::table('writing_submissions', function (Blueprint $table) {
            $table->dropIndex('writing_user_locked_idx');
        });
        Schema::table('speaking_submissions', function (Blueprint $table) {
            $table->dropIndex('speaking_user_locked_idx');
        });
    }
};
