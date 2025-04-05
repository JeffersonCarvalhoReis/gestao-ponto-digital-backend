<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaNaoUtil extends Model
{
    protected $table = 'dias_nao_uteis';

    protected $fillable = [
        'data',
        'tipo',
        'descricao',
        'setor_id'
,    ];

    public function funcionario()
    {
        return $this->belongsTo( Funcionario::class);
    }
}
