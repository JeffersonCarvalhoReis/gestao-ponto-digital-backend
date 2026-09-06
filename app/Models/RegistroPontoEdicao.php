<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroPontoEdicao extends Model
{
    use HasFactory;
    protected $table = 'registro_ponto_edicoes';

    protected $fillable = [
        'registro_ponto_id',
        'tipo',
        'editado_por_id',
        'data_local_anterior',
        'hora_entrada_anterior',
        'hora_saida_anterior',
        'data_local_nova',
        'hora_entrada_nova',
        'hora_saida_nova',
        'motivo',
    ];

    public function registroPonto()
    {
        return $this->belongsTo(RegistroPonto::class);
    }

    public function editadoPor()
    {
        return $this->belongsTo(User::class, 'editado_por_id');
    }
}
