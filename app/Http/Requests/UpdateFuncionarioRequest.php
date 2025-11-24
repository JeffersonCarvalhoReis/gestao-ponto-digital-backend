<?php
namespace App\Http\Requests;

use App\Models\Unidade;
use App\Rules\CpfUnicoNoSetor;
use App\Rules\ValidaCpf;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFuncionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obter setor da unidade enviada
        $setorId = null;

        if ($this->unidade_id) {
            $setorId = Unidade::where('id', $this->unidade_id)
                ->with('localidade')
                ->first()?->localidade?->setor_id;
        }

        return [
            'nome'            => 'sometimes|string|max:255',
            'data_nascimento' => 'sometimes|date',

            'cpf'             => [
                'sometimes',
                'string',
                'max:14',
                new ValidaCpf,
                new CpfUnicoNoSetor($setorId, $this->route('funcionario')),
            ],

            'foto'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'unidade_id'      => 'sometimes|exists:unidades,id',
            'cargo_id'        => 'sometimes|exists:cargos,id',
            'status'          => 'sometimes|boolean',
        ];
    }
}
