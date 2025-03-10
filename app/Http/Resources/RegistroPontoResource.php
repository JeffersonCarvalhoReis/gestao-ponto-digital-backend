<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistroPontoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->data_local,
            'hora_entrada' => $this->hora_entrada,
            'hora_saida' => $this->hora_saida,
            'biometrico' => $this->biometrico,
            'funcionario_id' => $this->funcionario_id,
            'nome' => $this->funcionario->nome,
            'unidade' => $this->funcionario->unidade->nome,
        ];
    }
}
