<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Modèle IA qui a effectivement produit les QCM (traçabilité / bascule). */
    public function up(): void
    {
        Schema::table('ai_challenges', function (Blueprint $table) {
            $table->string('generated_with')->nullable()->after('generated_test_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_challenges', function (Blueprint $table) {
            $table->dropColumn('generated_with');
        });
    }
};
