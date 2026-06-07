<?php

namespace App\Notifications;

use App\Models\RetrainingRun;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PredictionModelUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private RetrainingRun $run,
        private array $activeModelKeys
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'prediction_model_updated',
            'title' => 'Model prediksi diperbarui',
            'message' => 'Sistem prediksi sudah memakai pembaruan hasil retraining terbaru.',
            'icon' => 'fa-brain',
            'url' => route('form'),
            'run_id' => $this->run->id,
            'active_models' => array_values($this->activeModelKeys),
            'finished_at' => optional($this->run->finished_at)->toDateTimeString(),
        ];
    }
}
