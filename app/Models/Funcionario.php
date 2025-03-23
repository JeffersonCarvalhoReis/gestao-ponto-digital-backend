<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    /** @use HasFactory<\Database\Factories\FuncionarioFactory> */
    use HasFactory;

    protected $fillable = [
        'nome',
        'data_nascimento',
        'cpf',
        'foto',
        'cargo_id',
        'unidade_id',
        'status'
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function unidade()
    {
        return $this->belongsTo(Unidade::class);
    }
    public function registroPontos()
    {
        return $this->hasMany(RegistroPonto::class);
    }
    public function biometria()
    {
        return $this->hasOne(Biometria::class);
    }

    public function dadosContrato()
    {
        return $this->hasOne(DadosContrato::class);
    }
    public function justificativas()
    {
        return $this->hasMany(Justificativa::class);
    }

    public function diasNaoUteis()
    {
        return $this->hasMany(DiaNaoUtil::class);
    }
    public function ferias()
    {
        return $this->hasMany(Feria::class);
    }
}
