<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModellTestAvailable extends Notification
{
    use Queueable;

    public function __construct(
        public int $modellTestId,
        public int $number,
        public string $title,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'modelltest_available',
            'modell_test_id' => $this->modellTestId,
            'message' => 'Nouveau contenu : '.$this->title.'.',
            'url' => route('modelltests.index'),
        ];
    }
}
