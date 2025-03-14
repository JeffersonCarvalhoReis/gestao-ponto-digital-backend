<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Justificativa extends Model
{
    use HasFactory;
    protected $fillable = [
        'motivo',
        'motivo_recusa',
        'data_inicio',
        'data_fim',
        'data',
        'anexo',
        'status',
        'funcionario_id',
    ];
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
}
