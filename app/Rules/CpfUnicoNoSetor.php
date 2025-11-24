<?php
namespace App\Rules;

use App\Models\Funcionario;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfUnicoNoSetor implements ValidationRule
{
    protected $setorId;
    protected $ignoreId;

    public function __construct($setorId, $ignoreId = null)
    {
        $this->setorId  = $setorId;
        $this->ignoreId = $ignoreId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->setorId) {
            return;
        }

        $query = Funcionario::where('cpf', $value)
            ->whereHas('unidade.localidade', function ($q) {
                $q->where('setor_id', $this->setorId);
            });

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('CPF já cadastrado.');
        }
    }
}
