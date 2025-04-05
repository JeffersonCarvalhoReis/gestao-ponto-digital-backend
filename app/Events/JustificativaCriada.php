<?php

namespace App\Events;

use App\Models\Justificativa;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\SerializesModels;

class JustificativaCriada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $justificativa;
    public $notificacaoId;
    public $admin;

    /**
     * Create a new event instance.
     */
    public function __construct(Justificativa $justificativa, $notificacaoId, $admin)
    {
        $this->justificativa   = $justificativa;
        $this->notificacaoId  = $notificacaoId;
        $this->admin           = $admin;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('notifications.' . $this->admin->id)];
    }
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'justificativa.criada';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificacaoId,
            'justificativa_id' => $this->justificativa->id,
            'funcionario' => $this->justificativa->funcionario->nome,
            'unidade' => $this->justificativa->funcionario->unidade->nome,
            'motivo' => $this->justificativa->motivo,
            'data_inicio' => $this->justificativa->data_inicio,
            'data_fim' => $this->justificativa->data_fim,
            'data_registro' => $this->justificativa->created_at,
        ];
    }
}
