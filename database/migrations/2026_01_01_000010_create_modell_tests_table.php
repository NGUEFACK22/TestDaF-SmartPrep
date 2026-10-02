<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modell_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('theme')->nullable();
            $table->string('difficulty', 4)->default('B2');
            $table->string('status')->default('draft'); // draft | published
            $table->unsignedInteger('total_duration_seconds')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modell_tests');
    }
};
