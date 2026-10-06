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

    /** Mémoïsation du pool de questions (évite N requêtes par page d'examen). */
    protected ?\Illuminate\Support\Collection $memoFormQuestions = null;

    protected $fillable = [
        'attempt_id', 'exercise_id', 'section_id', 'position', 'status',
        'started_at', 'expires_at', 'completed_at', 'time_spent',
        'answers_locked', 'current_question_index', 'current_question_expires_at',
        'score', 'max_score', 'question_form',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExerciseState::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'current_question_expires_at' => 'datetime',
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
     * La question donnée accepte-t-elle encore une réponse ?
     * Bloque les questions passées ou futures, les réponses hors délai
     * et toute écriture sur une question expirée (anti-contournement).
     *
     * Mode par question uniquement : les tâches réceptives non chronométrées
     * (pools legacy sans time_limit_seconds) gardent le formulaire complet.
     */
    public function acceptsAnswerFor(\App\Models\Question $question): bool
    {
        if (! $this->acceptsAnswers()) {
            return false;
        }

        if ($this->isProductiveExercise()) {
            return true;
        }

        $current = $this->currentQuestion();

        $timed = $current !== null
            && $current->time_limit_seconds !== null
            && (int) $current->time_limit_seconds > 0;

        if (! $timed) {
            return true;
        }

        if (! $current || (int) $current->id !== (int) $question->id) {
            return false;
        }

        return ! $this->currentQuestionExpired();
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

    /** Questions actives pour cette tentative, dans l'ordre du pool (mémoïsé). */
    public function formQuestions(): \Illuminate\Support\Collection
    {
        if ($this->memoFormQuestions !== null) {
            return $this->memoFormQuestions;
        }

        $ids = $this->formIds();

        // Réutilise la relation déjà chargée si possible (évite 5-10 requêtes).
        if ($this->relationLoaded('exercise') && $this->exercise->relationLoaded('questions')) {
            $pool = $this->exercise->questions->sortBy('position')->values();
        } else {
            $pool = $this->exercise->questions()->orderBy('position')->get();
        }

        if ($ids === null) {
            return $this->memoFormQuestions = $pool->values();
        }

        $idSet = array_flip($ids);

        return $this->memoFormQuestions = $pool
            ->filter(fn ($q) => isset($idSet[(int) $q->getKey()]))
            ->values();
    }

    /** Invalide le cache mémoire (après changement de forme). */
    public function flushFormCache(): void
    {
        $this->memoFormQuestions = null;
    }

    // ------------------------------------------------- timer par question

    /** La question est-elle une tâche productive (enregistrement / rédaction) ? */
    public function isProductiveExercise(): bool
    {
        return in_array($this->exercise->skill->value, ['schreiben', 'sprechen'], true);
    }

    /** Index courant, borné à la forme active. */
    public function questionIndex(): int
    {
        $count = max(1, $this->formQuestions()->count());

        return (int) min(max(0, (int) $this->current_question_index), $count - 1);
    }

    /** Question courante (forme active, index courant). */
    public function currentQuestion(): ?\App\Models\Question
    {
        $questions = $this->formQuestions();

        return $questions->get($this->questionIndex());
    }

    /** Instant limite serveur de la question courante (plafonné par la tâche). */
    public function currentQuestionExpiresAt(): ?\Carbon\CarbonInterface
    {
        $question = $this->currentQuestion();

        if (! $question || $question->time_limit_seconds === null) {
            return $this->expires_at;
        }

        $started = $this->current_question_expires_at?->copy()
            ->subSeconds((int) $question->time_limit_seconds)
            ?? $this->started_at;

        $computed = ($started ?? now())->copy()->addSeconds((int) $question->time_limit_seconds);

        if ($this->expires_at && $computed->greaterThan($this->expires_at)) {
            return $this->expires_at;
        }

        return $computed;
    }

    /** Temps restant de la question courante (secondes, autorité serveur). */
    public function currentQuestionRemainingSeconds(): int
    {
        if ($this->isProductiveExercise()) {
            return $this->remainingSeconds();
        }

        $expires = $this->current_question_expires_at;

        if ($expires === null) {
            return $this->remainingSeconds();
        }

        return max(0, now()->diffInSeconds($expires, false));
    }

    /** La question courante est-elle expirée côté serveur ? */
    public function currentQuestionExpired(): bool
    {
        if ($this->isProductiveExercise()) {
            return false;
        }

        return $this->current_question_expires_at !== null
            && now()->greaterThanOrEqualTo($this->current_question_expires_at);
    }
}
