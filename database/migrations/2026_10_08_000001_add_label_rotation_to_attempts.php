<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rotation des LETTRES correctes d'un tour à l'autre : chaque tentative
     * mémorise le nombre de tentatives précédentes du candidat sur le même
     * test. La lettre correcte affichée tourne à chaque tour (plus jamais
     * la même deux tours de suite) — le scoring dé-rotate avant de corriger.
     */
    public function up(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->unsignedInteger('label_rotation')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn('label_rotation');
        });
    }
};
