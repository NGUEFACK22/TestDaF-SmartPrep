<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SpeakingSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'attempt_id', 'attempt_exercise_id', 'media_id',
        'path', 'mime', 'size', 'duration_seconds', 'transcript',
        'submitted_at', 'locked',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'locked' => 'boolean',
        ];
    }

    public function attemptExercise(): BelongsTo
    {
        return $this->belongsTo(AttemptExercise::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function aiEvaluations(): MorphMany
    {
        return $this->morphMany(AiEvaluation::class, 'evaluable');
    }

    public function manualCorrections(): MorphMany
    {
        return $this->morphMany(ManualCorrection::class, 'evaluable');
    }
}
