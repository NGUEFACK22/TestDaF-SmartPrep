<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modell_test_id')->constrained('modell_tests')->cascadeOnDelete();
            $table->string('skill'); // lesen | hoeren | schreiben | sprechen
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();

            $table->unique(['modell_test_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
