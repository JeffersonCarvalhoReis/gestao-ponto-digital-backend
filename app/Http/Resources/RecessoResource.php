<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecessoResource extends JsonResource
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
            'descricao' => $this->descricao,
            'data' => $this->data,
            'tipo' => $this->tipo,
            'unidade_id' => $this->unidade_id,
            'unidade' => $this->unidade->nome ?? 'Todas as Unidades'
        ];
    }
}
