<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DadosContratoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'vinculo' => $this->vinculo,
            'carga_horaria' => $this->carga_horaria,
            'data_admissao' => $this->data_admissao,
            'salario_base' => $this->salario_base,
        ];
    }
}
