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
            'data' => $this->data,
            'hora_entrada' => $this->hora_entrada,
            'hora_saida' => $this->hora_saida,
            'funcionario_id' => $this->funcionario_id,
        ];
    }
}
