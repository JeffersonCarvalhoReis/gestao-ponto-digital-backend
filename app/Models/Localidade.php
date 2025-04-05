<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Localidade extends Model
{
    use HasFactory;
    protected $fillable = [
        'nome',
        'setor_id'
    ];
    public function unidades()
    {
        return $this->hasMany(Unidade::class);
    }
    public function setor()
    {
        return $this->belongsTo(Setor::class);
    }
}
