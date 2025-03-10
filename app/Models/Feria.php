<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feria extends Model
{
    protected $fillable = [
        'data',
        'tipo',
        'descricao',
        'funcionario_id',
        'data_inicio',
        'data_fim'

    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
}
