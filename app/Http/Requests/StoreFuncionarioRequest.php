<?php
namespace App\Http\Requests;

use App\Models\Unidade;
use App\Rules\CpfUnicoNoSetor;
use App\Rules\ValidaCpf;
use Illuminate\Foundation\Http\FormRequest;

class StoreFuncionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // aqui só autoriza, não valida
    }

    public function rules(): array
    {
        // Descobre o setor da unidade enviada
        $setorId = null;

        if ($this->unidade_id) {
            $setorId = Unidade::where('id', $this->unidade_id)
                ->with('localidade')
                ->first()?->localidade?->setor_id;
        }

        return [
            'nome'            => 'required|string|max:255',
            'data_nascimento' => 'required|date',
            'cpf'             => [
                'required',
                'string',
                'max:14',
                new ValidaCpf,
                new CpfUnicoNoSetor($setorId),
            ],
            'foto'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'unidade_id'      => 'required|exists:unidades,id',
            'cargo_id'        => 'required|exists:cargos,id',
        ];
    }
}
