<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FeriasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'data_inicio'    => Carbon::parse($this->data_inicio)->format('d/m/Y'),
            'data_fim'       => Carbon::parse($this->data_fim)->format('d/m/Y'),
            'descricao'      => $this->descricao,
            'funcionario_id' => $this->funcionario_id,
            'funcionario'    => $this->funcionario,
            'unidade'        => $this->unidade,
            'total_dias'     => $this->total_dias,
        ];
    }
}
