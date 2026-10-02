<?php

namespace App\Models;

use App\Enums\ExerciseState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttemptExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id', 'exercise_id', 'section_id', 'position', 'status',
        'started_at', 'expires_at', 'completed_at', 'time_spent',
        'answers_locked', 'score', 'max_score',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExerciseState::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'answers_locked' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function state(): ExerciseState
    {
        return $this->status instanceof ExerciseState
            ? $this->status
            : ExerciseState::from($this->status);
    }

    /** Temps restant en secondes selon l'heure serveur. */
    public function remainingSeconds(): int
    {
        if (! $this->expires_at) {
            return (int) $this->exercise->duration_seconds;
        }

        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    /** Le délai serveur est-il dépassé ? */
    public function hasExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThanOrEqualTo($this->expires_at);
    }

    /** Réponses encore autorisées (état + délai serveur) ? */
    public function acceptsAnswers(): bool
    {
        return $this->state()->acceptsAnswers()
            && ! $this->answers_locked
            && ! $this->hasExpired();
    }
}
