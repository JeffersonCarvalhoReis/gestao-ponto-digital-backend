<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recesso extends Model
{
    protected $fillable = [
        'data',
        'tipo',
        'descricao',
        'unidade_id'
    ];

    public function unidade()
    {
        return $this->belongsTo(Unidade::class);
    }
}
