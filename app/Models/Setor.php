<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setor extends Model
{
  protected $table = 'setores';
  protected $fillable = ['nome', 'bloquear_ponto_sem_biometria'];

  protected $casts = [
      'bloquear_ponto_sem_biometria' => 'boolean',
  ];

  public function users()
  {
      return $this->hasMany(User::class);
  }

  public function localidades()
  {
      return $this->hasMany(Localidade::class);
  }

  public function tiposTurno()
  {
      return $this->hasMany(TipoTurno::class);
  }

}
