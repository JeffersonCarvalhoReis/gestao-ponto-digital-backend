<?php

namespace App\Notifications;

use App\Models\Justificativa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class JustificativaNotification extends Notification
{
    use Queueable;

    protected $justificativa;
    protected $title;
    protected $message;
    protected $icon;
    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(Justificativa $justificativa, string $title, string $message, string $status, string $icon = 'mdi-bell')
    {
        $this->justificativa = $justificativa;
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'justificativa_id' => $this->justificativa->id,
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'justificativa_id' => $this->justificativa->id,
            'unidade' => $this->justificativa->funcionario->unidade->nome,
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'status' => $this->status,
        ];
    }
}
