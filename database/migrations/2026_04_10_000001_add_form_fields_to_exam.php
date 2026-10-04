<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs pour le moteur de formes dynamiques (FormService) :
 *  - questions.difficulty : étiquette de difficulté (B2 / C1 / C1+),
 *    utilisée pour biaiser la sélection vers un niveau légèrement
 *    supérieur au TestDaF officiel ;
 *  - attempt_exercises.question_form : IDs des questions sélectionnées
 *    pour cette tentative (null = toutes les questions de l'exercice).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('difficulty', 10)->nullable()->after('type');
        });

        Schema::table('attempt_exercises', function (Blueprint $table) {
            $table->json('question_form')->nullable()->after('max_score');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });

        Schema::table('attempt_exercises', function (Blueprint $table) {
            $table->dropColumn('question_form');
        });
    }
};