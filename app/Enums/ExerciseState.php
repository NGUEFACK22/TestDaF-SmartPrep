<?php

namespace App\Enums;

/**
 * Machine d'état d'une tâche (AttemptExercise).
 *
 * verrouillé (locked) -> disponible (available) -> démarré (started)
 *   -> terminé (completed) | expiré (expired)
 */
enum ExerciseState: string
{
    case Locked = 'locked';
    case Available = 'available';
    case Started = 'started';
    case Completed = 'completed';
    case Expired = 'expired';

    /** Transitions autorisées. */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Locked => [self::Available],
            self::Available => [self::Started],
            self::Started => [self::Completed, self::Expired],
            self::Completed, self::Expired => [],
        };
    }

    /** Une tâche terminée ou expirée est définitivement verrouillée. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Expired], true);
    }

    /** La tâche peut-elle encore recevoir des réponses ? */
    public function acceptsAnswers(): bool
    {
        return $this === self::Available || $this === self::Started;
    }

    public function label(): string
    {
        return match ($this) {
            self::Locked => 'Verrouillée',
            self::Available => 'Disponible',
            self::Started => 'En cours',
            self::Completed => 'Terminée',
            self::Expired => 'Expirée',
        };
    }
}
