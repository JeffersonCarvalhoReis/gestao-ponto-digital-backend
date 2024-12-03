<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feria extends Model
{
    protected $fillable = [
        'data',
        'funcionario_id'
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }
}
