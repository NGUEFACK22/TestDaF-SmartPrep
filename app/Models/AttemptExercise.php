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
        'answers_locked', 'score', 'max_score', 'question_form',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExerciseState::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'answers_locked' => 'boolean',
            'question_form' => 'array',
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

    /**
     * IDs des questions actives pour cette tentative.
     * null = pas de forme : toutes les questions de l'exercice (légacy).
     */
    public function formIds(): ?array
    {
        $form = $this->question_form;

        if (empty($form)) {
            return null;
        }

        return array_values(array_map('intval', (array) $form));
    }

    /** Questions actives pour cette tentative, dans l'ordre du pool. */
    public function formQuestions(): \Illuminate\Support\Collection
    {
        $ids = $this->formIds();
        $pool = $this->exercise->questions()->orderBy('position')->get();

        if ($ids === null) {
            return $pool;
        }

        return $pool
            ->filter(fn ($q) => in_array((int) $q->getKey(), $ids, true))
            ->values();
    }
}
