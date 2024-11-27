<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuncionarioResource extends JsonResource
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
            'data_nascimento' => $this->data_nascimento,
            'cpf' => $this->cpf,
            'vinculo' => $this->DadosContrato->vinculo ?? null,
            'carga_horaria' => $this->DadosContrato->carga_horaria ?? null,
            'data_admissao' => $this->DadosContrato->data_admissao ?? null,
            'salario_base' => $this->DadosContrato->salario_base ?? null,
            'foto' => $this->foto ? asset('storage/' . $this->foto) : null,
            'unidade' => $this->unidade->nome,
            'cargo' => $this->cargo->nome,
            'unidade_id' => $this->unidade_id,
            'cargo_id' => $this->cargo_id,
        ];
    }
}
