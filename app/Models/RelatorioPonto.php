<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelatorioPonto extends Model
{
    protected $fillable = [
        'horas_trabalhadas',
        'dias_trabalhados',
        'dias_faltas',
        'dias_justificados'
    ];

    public function registroPontos()
    {
        return $this->hasMany( RegistroPonto::class);
    }
}
