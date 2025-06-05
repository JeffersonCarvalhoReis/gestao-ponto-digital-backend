<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FuncionarioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data_admissao = null;

        if ($this->DadosContrato && $this->DadosContrato->data_admissao) {
            $data_admissao = Carbon::parse($this->DadosContrato->data_admissao)->format('d/m/Y');
        }
        return [
            'id' => $this->id,
            'nome' => Str::title($this->nome),
            'data_nascimento' => Carbon::parse($this->data_nascimento)->format('d/m/Y'),
            'cpf' => $this->cpf,
            'vinculo' => $this->DadosContrato->vinculo ?? null,
            'carga_horaria' => $this->DadosContrato->carga_horaria ?? null,
            'data_admissao' => $data_admissao,
            'salario_base' => $this->DadosContrato->salario_base ?? null,
            'dados_contrato_id' => $this->DadosContrato->id ?? null,
            'foto' => $this->foto ? asset('storage/' . $this->foto) : null,
            'unidade' => $this->unidade->nome,
            'cargo' => $this->cargo?->nome,
            'unidade_id' => $this->unidade_id,
            'cargo_id' => $this->cargo_id,
            'biometria' => $this->biometria->id ?? null,
            'status' => $this->status ?'Ativo' : 'Inativo',
        ];
    }
}
