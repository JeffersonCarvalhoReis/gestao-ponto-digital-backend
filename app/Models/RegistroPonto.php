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
        'relatorio_ponto_id',
        'data_local',
    ];

    protected static function booted()
    {
        static::creating(function ($registro) {
            $registro->data_local = now()->timezone('America/Sao_Paulo')->format('Y-m-d');
        });

        static::updating(function ($registro) {
            $registro->data_local = now()->timezone('America/Sao_Paulo')->format('Y-m-d');
        });
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
    public function relatorioPotnos()
    {
        return $this->belongsTo(RelatorioPonto::class);
    }
}
