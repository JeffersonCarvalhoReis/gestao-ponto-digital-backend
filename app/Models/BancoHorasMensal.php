<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BancoHorasMensal extends Model
{
    use HasFactory;

    protected $table = 'banco_horas_mensal';

    protected $fillable = [
        'funcionario_id',
        'mes_referencia',
        'minutos_previstos',
        'minutos_realizados',
        'minutos_extra',
        'saldo_minutos',
        'status',
        'fechado_em',
        'fechado_por',
        'observacao',
    ];

    protected $casts = [
        'mes_referencia' => 'date',
        'fechado_em' => 'datetime',
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function fechadoPor()
    {
        return $this->belongsTo(User::class, 'fechado_por');
    }

    public function getSaldoFormatadoAttribute(): string
    {
        $sinal = $this->saldo_minutos < 0 ? '-' : '';
        $minutosAbs = abs($this->saldo_minutos);

        return sprintf('%s%02d:%02d', $sinal, intdiv($minutosAbs, 60), $minutosAbs % 60);
    }
}
