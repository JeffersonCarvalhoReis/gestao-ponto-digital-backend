<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JustificativaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'motivo' => $this->motivo,
            'anexo' => $this->anexo ? asset('storage/' . $this->anexo) : null,
            'data_inicio' => Carbon::parse($this->data_inicio)->format('d/m/Y'),
            'data_fim' => Carbon::parse($this->data_fim)->format('d/m/Y'),
            'status' => $this->status,
            'funcionario_id' => $this->funcionario_id,
            'funcionario' => $this->funcionario->nome,
            'cpf' => $this->funcionario->cpf,
            'cargo' => $this->funcionario->cargo->nome,
            'foto' =>$this->funcionario->foto ? asset('storage/' . $this->funcionario->foto) : null,
            'unidade' => $this->funcionario->unidade->nome,
        ];
    }

}
