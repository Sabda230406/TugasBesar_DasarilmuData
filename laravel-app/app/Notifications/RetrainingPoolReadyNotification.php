<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RetrainingPoolReadyNotification extends Notification
{
    use Queueable;

    public function __construct(private array $pool)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'retraining_pool_ready',
            'title' => 'Data retraining sudah siap',
            'message' => 'Pool data sudah memenuhi batas minimal. Admin bisa mulai retraining model sekarang.',
            'icon' => 'fa-rotate',
            'url' => route('admin.retraining'),
            'total_rows' => $this->pool['total_rows'] ?? 0,
            'stroke_0' => $this->pool['stroke_0'] ?? 0,
            'stroke_1' => $this->pool['stroke_1'] ?? 0,
            'min_total_rows' => $this->pool['min_total_rows'] ?? 0,
            'min_class_rows' => $this->pool['min_class_rows'] ?? 0,
        ];
    }
}
