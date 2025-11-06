<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDadosContratoRequest extends FormRequest
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
            'vinculo' => 'required|string|max:50',
            'carga_horaria' => 'nullable|integer|min:1',
            'data_admissao' => 'required|date',
            'salario_base' => 'nullable|numeric',
            'funcionario_id' => 'nullable|exists:funcionarios,id'
        ];
    }
}
