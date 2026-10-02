<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->string('skill');
            $table->decimal('points', 8, 2)->default(0);
            $table->decimal('max_points', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
