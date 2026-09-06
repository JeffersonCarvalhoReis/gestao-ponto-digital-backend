<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoTurnoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"                  => $this->id,
            "codigo"              => $this->codigo,
            "nome"                => $this->nome,
            "hora_inicio"         => $this->hora_inicio,
            "hora_fim"            => $this->hora_fim,
            "duracao_minutos"     => $this->duracao_minutos,
            "conta_como_trabalho" => $this->conta_como_trabalho,
            "e_hora_extra"        => $this->e_hora_extra,
            "cor"                 => $this->cor,
            "ativo"               => $this->ativo,

        ];
    }
}
