<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ManualCorrectionReady extends Notification
{
    use Queueable;

    public function __construct(
        public int $attemptId,
        public string $skill,
        public float $points,
        public float $maxPoints,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'manual_correction_ready',
            'attempt_id' => $this->attemptId,
            'skill' => $this->skill,
            'points' => $this->points,
            'max_points' => $this->maxPoints,
            'message' => 'Votre correction ('.$this->skill.') est disponible.',
            'url' => route('results.show', $this->attemptId),
        ];
    }
}
