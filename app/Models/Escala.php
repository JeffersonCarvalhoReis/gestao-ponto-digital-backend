<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escala extends Model
{
    use HasFactory;

    protected $fillable = [
        'funcionario_id',
        'data',
        'tipo_turno_id',
        'observacao',
        'criado_por',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function tipoTurno()
    {
        return $this->belongsTo(TipoTurno::class);
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
