<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidade extends Model
{
    use HasFactory;
    protected $fillable = [
        'nome',
        'localidade_id',
        'cnes',
        'permite_saida_dia_diferente',
    ];

    protected $casts = [
        'permite_saida_dia_diferente' => 'boolean',
    ];

    public function funcionarios()
    {
        return $this->hasMany(Funcionario::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function localidade()
    {
        return $this->belongsTo(Localidade::class);
    }

    public function recessos()
    {
        return $this->hasMany(Recesso::class);
    }

}
