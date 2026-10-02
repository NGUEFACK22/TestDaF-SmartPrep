<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corrector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('evaluable');
            $table->decimal('points', 6, 2)->nullable();
            $table->decimal('max_points', 6, 2)->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('pending'); // pending | corrected
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_corrections');
    }
};
