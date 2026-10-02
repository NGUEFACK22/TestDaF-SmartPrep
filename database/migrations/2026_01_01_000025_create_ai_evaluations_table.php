<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('evaluable'); // writing/speaking submission, attempt...
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attempt_id')->nullable()->constrained('attempts')->nullOnDelete();
            $table->string('skill')->nullable();
            $table->string('provider')->default('gemini');
            $table->string('model')->nullable();
            $table->string('status')->default('pending'); // pending | processing | completed | failed
            $table->json('result')->nullable();
            $table->longText('feedback')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_evaluations');
    }
};
