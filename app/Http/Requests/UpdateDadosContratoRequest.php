<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDadosContratoRequest extends FormRequest
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
        return [
            'vinculo' => 'sometimes|string|max:50',
            'carga_horaria' => 'sometimes|integer|min:1',
            'data_admissao' => 'sometimes|date',
            'salario_base' => 'sometimes|numeric|min:0',
        ];
    }
}
