<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setor extends Model
{
  protected $table = 'setores';
  protected $fillable = ['nome'];

  public function users()
  {
      return $this->hasMany(User::class);
  }

  public function localidades()
  {
      return $this->hasMany(Localidade::class);
  }

}
