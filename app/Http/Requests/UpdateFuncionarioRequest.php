<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFuncionarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $funcionario = $this->route('funcionario');

        return [
            'nome' => 'sometimes|string|max:255',
            'data_nascimento' => 'sometimes|date',
            'cpf' => 'sometimes|string|max:14|unique:funcionarios,cpf,' . $funcionario,
            'foto' => 'nullable|max:10240',
            'unidade_id' => 'sometimes|exists:unidades,id',
            'cargo_id' => 'sometimes|exists:cargos,id',
            'status' => 'sometimes|boolean'
        ];
    }
}
