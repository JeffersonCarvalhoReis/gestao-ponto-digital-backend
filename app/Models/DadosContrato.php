<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DadosContrato extends Model
{
    use HasFactory;
    protected $fillable = [
        'vinculo',
        'carga_horaria',
        'data_admissao',
        'salario_base',
        'funcionario_id',
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
}
