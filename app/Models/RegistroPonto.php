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
        'data_saida',
        'funcionario_id',
        'relatorio_ponto_id',
        'data_local',
        'arquivado_em',
        'arquivado_por_id',
        'motivo_arquivamento',
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

    public function edicoes()
    {
        return $this->hasMany(RegistroPontoEdicao::class)->latest();
    }

    public function arquivadoPor()
    {
        return $this->belongsTo(User::class, 'arquivado_por_id');
    }

    /**
     * Datetime completo (data + hora) da entrada.
     */
    public function entradaCompleta(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->data_local . ' ' . $this->hora_entrada);
    }

    /**
     * Datetime completo (data + hora) da saída, ou null se ainda em aberto.
     *
     * Quando "data_saida" está preenchida (registros criados/corrigidos
     * depois da introdução dessa coluna), ela é usada diretamente — sem
     * nenhuma adivinhação. Para registros antigos, sem "data_saida", cai
     * no comportamento histórico: se a hora da saída for menor ou igual à
     * hora da entrada, assume que a saída foi no dia seguinte.
     */
    public function saidaCompleta(): ?\Carbon\Carbon
    {
        if (! $this->hora_saida) {
            return null;
        }

        if ($this->data_saida) {
            return \Carbon\Carbon::parse($this->data_saida . ' ' . $this->hora_saida);
        }

        $horaEntrada = \Carbon\Carbon::parse($this->hora_entrada);
        $horaSaida   = \Carbon\Carbon::parse($this->hora_saida);
        $diasSomar   = $horaSaida->lessThanOrEqualTo($horaEntrada) ? 1 : 0;

        return \Carbon\Carbon::parse($this->data_local . ' ' . $this->hora_saida)->addDays($diasSomar);
    }

    /**
     * Minutos trabalhados no registro, ou null se ainda em aberto.
     */
    public function duracaoEmMinutos(): ?int
    {
        $saida = $this->saidaCompleta();

        if (! $saida) {
            return null;
        }

        return $this->entradaCompleta()->diffInMinutes($saida);
    }
}
