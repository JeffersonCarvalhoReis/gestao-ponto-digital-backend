<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'sometimes|string|max:10',
            'nome' => 'sometimes|string|max:100',
            'setor_id' => 'nullable|exists:setores,id',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fim' => 'nullable|date_format:H:i',
            'duracao_minutos' => 'nullable|integer|min:0',
            'conta_como_trabalho' => 'boolean',
            'e_hora_extra' => 'boolean',
            'cor' => 'nullable|string|max:7',
            'ativo' => 'boolean',
        ];
    }
}
