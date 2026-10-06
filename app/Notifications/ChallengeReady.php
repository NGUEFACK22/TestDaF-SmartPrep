<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChallengeReady extends Notification
{
    use Queueable;

    public function __construct(
        public int $challengeId,
        public int $questionCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'challenge_ready',
            'challenge_id' => $this->challengeId,
            'message' => "Vos {$this->questionCount} QCM sur mesure sont prêts. Bonne chance !",
            'url' => route('challenges.index'),
        ];
    }
}
