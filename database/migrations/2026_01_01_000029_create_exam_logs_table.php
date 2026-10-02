<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attempt_id')->nullable()->constrained('attempts')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['attempt_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_logs');
    }
};
