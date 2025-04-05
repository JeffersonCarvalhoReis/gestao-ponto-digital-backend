<?php

namespace App\Events;

use App\Models\RegistroPonto;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovoRegistroPonto implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $registro;
    public $setorId;
    /**
     * Create a new event instance.
     */
    public function __construct(RegistroPonto $registro, string $setorId)
    {
        $this->registro = $registro;
        $this->setorId = $setorId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return  [new Channel('registro-ponto.' . $this->setorId)];
    }

    public function broadcastWith()
    {
        return [
            'id'  => $this->registro->id,
            'data' => $this->registro->data_local,
            'hora_entrada' => $this->registro->hora_entrada ? Carbon::parse($this->registro->hora_entrada)->format('H:i:s') : null,
            'hora_saida' => $this->registro->hora_saida ? Carbon::parse( $this->registro->hora_saida)->format('H:i:s') : null,
            'biometrico' => $this->registro->biometrico,
            'funcionario_id' => $this->registro->funcionario_id,
            'nome' => $this->registro->funcionario->nome,
            'unidade' => $this->registro->funcionario->unidade->nome,
        ];
    }
    public function broadcastAs()
    {
        return 'novo.registro.ponto';
    }

}
