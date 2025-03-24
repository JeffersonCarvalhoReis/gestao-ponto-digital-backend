<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id'           => $this->id,
            'user'         => $this->user,
            'funcao'       => $this->roles[0]->name,
            'unidade_nome' => $this->unidade->nome,
            'unidade'      => $this->unidade ? [
                'id'       => $this->unidade->id,
               'nome'      => $this->unidade->nome,
            ] : null,
            'deletavel'    => true
        ];
    }
}
