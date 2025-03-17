<?php

namespace App\Events;

use App\Models\Justificativa;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JustificativaStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $justificativa;
    public $status;
    public $unidadeId;

    /**
     * Create a new event instance.
     */
    public function __construct(Justificativa $justificativa, string $status)
    {
        $this->justificativa = $justificativa;
        $this->status = $status;
        $this->unidadeId = $justificativa->funcionario->unidade_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('unidade.' . $this->unidadeId),
        ];
    }
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'justificativa.status.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->justificativa->id,
            'status' => $this->status,
            'funcionario' => $this->justificativa->funcionario->nome,
            'motivo' => $this->justificativa->motivo,
            'motivo_recusa' => $this->justificativa->motivo_recusa,
            'data_inicio' => $this->justificativa->data_inicio,
            'data_fim' => $this->justificativa->data_fim,
            'atualizado' => $this->justificativa,
        ];
    }
}
