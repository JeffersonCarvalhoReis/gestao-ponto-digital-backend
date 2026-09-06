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

        // IMPORTANTE: "data_local" representa o dia em que o turno começou
        // (dia da entrada) e NÃO pode ser sobrescrita ao registrar a saída.
        // Um plantão pode ser aberto num dia e encerrado depois da meia-noite;
        // se recalculássemos data_local no update, o registro "pularia" para
        // o dia seguinte e corromperia o banco de horas e os relatórios.
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
    public function relatorioPontos()
    {
        return $this->belongsTo(RelatorioPonto::class);
    }
}
