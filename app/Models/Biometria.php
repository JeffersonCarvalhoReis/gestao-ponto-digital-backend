<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biometria extends Model
{
    use HasFactory;

    protected $fillable = [
        'template',
        'funcionario_id',
    ];
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
}
