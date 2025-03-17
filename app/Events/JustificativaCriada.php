<?php

namespace App\Events;

use App\Models\Justificativa;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JustificativaCriada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $justificativa;

    /**
     * Create a new event instance.
     */
    public function __construct(Justificativa $justificativa)
    {
        $this->justificativa = $justificativa;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return  [new Channel('admin.notifications')];
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
            'id' => $this->justificativa->id,
            'funcionario' => $this->justificativa->funcionario->nome,
            'unidade' => $this->justificativa->funcionario->unidade->nome,
            'motivo' => $this->justificativa->motivo,
            'data_inicio' => $this->justificativa->data_inicio,
            'data_fim' => $this->justificativa->data_fim,
            'data_registro' => $this->justificativa->created_at,
        ];
    }
}
