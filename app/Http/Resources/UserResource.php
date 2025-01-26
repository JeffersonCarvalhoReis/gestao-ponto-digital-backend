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
            'id' => $this->id,
            'name' => $this->name,
            'funcao' => $this->roles[0]->name,
            'email' => $this->email,
            'unidade' => $this->unidade ? [
                'id' => $this->unidade->id,
               'nome' => $this->unidade->nome,
            ] : null,
        ];
    }
}
