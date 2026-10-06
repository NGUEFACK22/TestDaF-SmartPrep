<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChallengeUnlocked extends Notification
{
    use Queueable;

    public function __construct(
        public int $modellTestId,
        public string $modellTestTitle,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'challenge_unlocked',
            'modell_test_id' => $this->modellTestId,
            'message' => "🏆 Espace Élite débloqué : 2 scores parfaits d'affilée sur « {$this->modellTestTitle} » ! L'IA peut générer vos QCM sur mesure.",
            'url' => route('challenges.index'),
        ];
    }
}
