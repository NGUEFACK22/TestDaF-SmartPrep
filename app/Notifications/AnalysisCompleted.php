<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnalysisCompleted extends Notification
{
    use Queueable;

    public function __construct(
        public int $attemptId,
        public string $skill,
        public ?float $indicatorScore = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'analysis_completed',
            'attempt_id' => $this->attemptId,
            'skill' => $this->skill,
            'indicator_score' => $this->indicatorScore,
            'message' => 'Votre analyse IA ('.$this->skill.') est terminée.',
            'url' => route('results.show', $this->attemptId),
        ];
    }
}
