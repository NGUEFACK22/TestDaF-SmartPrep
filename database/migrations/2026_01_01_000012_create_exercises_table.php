<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('skill');                 // lesen | hoeren | schreiben | sprechen
            $table->string('type');                  // e.g. multiple_choice, lueckentext...
            $table->string('title');
            $table->string('level', 4)->default('B2');       // A2 | B1 | B2 | C1
            $table->string('difficulty', 4)->default('B2');
            $table->text('instruction')->nullable();         // consigne (Aufgabenstellung)
            $table->unsignedInteger('duration_seconds')->default(180);
            $table->unsignedInteger('preparation_seconds')->default(0);
            $table->unsignedInteger('recording_seconds')->default(0);
            $table->decimal('points', 6, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->json('content')->nullable();             // texte, source_text, graphique, etc.
            $table->text('solution_text')->nullable();        // Lösung (texte modèle)
            $table->text('explanation')->nullable();          // explication pédagogique
            $table->string('status')->default('draft');       // draft | published
            $table->timestamps();

            $table->index(['skill', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
