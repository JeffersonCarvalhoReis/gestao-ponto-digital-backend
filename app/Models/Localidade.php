<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Localidade extends Model
{
    use HasFactory;
    protected $fillable = [
        'nome',
    ];
    public function unidades()
    {
        return $this->hasMany(Unidade::class);
    }
}
