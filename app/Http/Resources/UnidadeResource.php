<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnidadeResource extends JsonResource
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
            'nome' => $this->nome,
            'localidade' => $this->localidade->nome,
            'localidade_id' => $this->localidade_id,
            'cnes' => $this->cnes,
            'deletavel' => $this->users()->count() === 0 && $this->funcionarios()->count() === 0
        ];
    }
}
