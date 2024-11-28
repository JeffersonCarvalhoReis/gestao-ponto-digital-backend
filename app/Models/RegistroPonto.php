<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroPonto extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometrico',
        'hora_entrada',
        'hora_saida',
        'funcionario_id',
        'relatorio_ponto_id'
    ];
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
    public function relatorioPotnos()
    {
        return $this->belongsTo(RelatorioPonto::class);
    }
}
