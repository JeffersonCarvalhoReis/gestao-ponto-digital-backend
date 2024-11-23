<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroPonto extends Model
{
    use HasFactory;

    protected $fillable = [
        'hora_entrada',
        'hora_saida',
        'funcionario_id',
    ];
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
}
