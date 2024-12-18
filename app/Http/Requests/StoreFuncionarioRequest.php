<?php

namespace App\Http\Requests;

use App\Rules\ValidaCpf;
use Illuminate\Foundation\Http\FormRequest;

class StoreFuncionarioRequest extends FormRequest
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
            'nome' => 'required|string|max:255',
            'data_nascimento' => 'required|date',
            'cpf' => ['required', 'string', 'max:14', 'unique:funcionarios,cpf', new ValidaCpf],
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'unidade_id' => 'required|exists:unidades,id',
            'cargo_id' => 'required|exists:cargos,id',
        ];
    }
}
