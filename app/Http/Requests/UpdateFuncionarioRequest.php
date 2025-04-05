<?php

namespace App\Http\Requests;

use App\Models\Unidade;
use App\Rules\ValidaCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $user = auth()->user();

        $setorId = null;

        if ($this->unidade_id) {
            $setorId = Unidade::where('id', $this->unidade_id)
                ->with('localidade')
                ->first()?->localidade?->setor_id;
        }

        return [
            'nome' => 'sometimes|string|max:255',
            'data_nascimento' => 'sometimes|date',
            'cpf' => ['sometimes', 'string', 'max:14', new ValidaCpf,
             Rule::unique('funcionarios', 'cpf')
             ->ignore($this->route('funcionario'))
             ->where(function ($query) use ($setorId, $user) {
                if ($setorId) {
                    return $user->setor_id == $setorId;
                }
            }),
        ],
            'foto' => 'nullable|max:10240',
            'unidade_id' => 'sometimes|exists:unidades,id',
            'cargo_id' => 'sometimes|exists:cargos,id',
            'status' => 'sometimes|boolean'
        ];
    }
}
