<?php

namespace App\Http\Requests;

use App\Models\Funcionario;
use App\Models\Unidade;
use App\Rules\ValidaCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFuncionarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        $setorId = null;
        if ($this->unidade_id) {
            $setorId = Unidade::where('id', $this->unidade_id)
                ->with('localidade')
                ->first()?->localidade?->setor_id;
        }

        if ($setorId) {
            $cpf = $this->cpf;
            $funcionarioExistente = Funcionario::where('cpf', $cpf)
                ->whereHas('unidade.localidade', function ($query) use ($setorId) {
                    $query->where('setor_id', $setorId);
                })
                ->exists();

            if ($funcionarioExistente) {
                return response()->json([
                    'message' => 'CPF já cadastrado neste setor.',
                ], 400);
            }
        }

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
            'cpf' => ['required', 'string', 'max:14', new ValidaCpf],
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'unidade_id' => 'required|exists:unidades,id',
            'cargo_id' => 'required|exists:cargos,id',
        ];
    }

}
