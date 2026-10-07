<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use App\Enums\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'modell_test_id', 'mode', 'status', 'score', 'max_score',
        'current_section_id', 'current_exercise_id',
        'started_at', 'completed_at', 'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modellTest(): BelongsTo
    {
        return $this->belongsTo(ModellTest::class);
    }

    public function attemptExercises(): HasMany
    {
        return $this->hasMany(AttemptExercise::class)->orderBy('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    /**
     * Tâche courante selon le serveur (source de vérité).
     *
     * Priorité à current_exercise_id, mais seulement si cette tâche n'est
     * PAS encore terminée (un pointeur obsolète ne doit pas masquer la
     * tâche suivante activée). Puis la première tâche non finalisée de
     * *cette tentative uniquement* (jamais d'une autre tentative).
     */
    public function currentExercise(): ?AttemptExercise
    {
        // Relation déjà chargée : aucun SQL supplémentaire.
        if ($this->relationLoaded('attemptExercises')) {
            if ($this->current_exercise_id) {
                $ae = $this->attemptExercises
                    ->firstWhere('exercise_id', $this->current_exercise_id);
                if ($ae && ! $ae->state()->isFinal()) {
                    return $ae;
                }
            }

            return $this->attemptExercises->first(fn ($ae) => ! $ae->state()->isFinal());
        }

        if ($this->current_exercise_id) {
            $ae = $this->attemptExercises()
                ->where('exercise_id', $this->current_exercise_id)
                ->first();

            if ($ae && ! $ae->state()->isFinal()) {
                return $ae;
            }
        }

        // Balaye par position croissante, stoppe à la première non finale
        // (index attempt_id+position existant, pas de full-scan applicatif).
        foreach ($this->attemptExercises()->orderBy('position')->cursor() as $ae) {
            if (! $ae->state()->isFinal()) {
                return $ae;
            }
        }

        return null;
    }

    public function percentage(): float
    {
        if (! $this->max_score || $this->max_score <= 0) {
            return 0.0;
        }

        return round(((float) $this->score / (float) $this->max_score) * 100, 2);
    }

    public function resultFor(Skill $skill): ?Result
    {
        return $this->results->firstWhere('skill', $skill->value);
    }
}
