<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTurno extends Model
{
    use HasFactory;

    protected $table = 'tipos_turno';

    protected $fillable = [
        'codigo',
        'nome',
        'setor_id',
        'hora_inicio',
        'hora_fim',
        'duracao_minutos',
        'conta_como_trabalho',
        'e_hora_extra',
        'cor',
        'ativo',
    ];

    protected $casts = [
        'conta_como_trabalho' => 'boolean',
        'e_hora_extra' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function setor()
    {
        return $this->belongsTo(Setor::class);
    }

    public function escalas()
    {
        return $this->hasMany(Escala::class);
    }

    /**
     * Resolve o tipo de turno pelo código considerando que um código pode ter
     * um significado específico para um setor (ex: "SD") e um significado
     * genérico quando não há registro específico daquele setor.
     */
    public static function resolverPorCodigo(string $codigo, ?int $setorId)
    {
        return static::where('codigo', $codigo)
            ->where(function ($query) use ($setorId) {
                $query->where('setor_id', $setorId)->orWhereNull('setor_id');
            })
            ->orderByRaw('setor_id IS NULL') // prioriza o específico do setor
            ->first();
    }
}
