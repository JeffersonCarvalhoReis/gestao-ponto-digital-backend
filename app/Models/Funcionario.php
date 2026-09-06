<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Model
{
    /** @use HasFactory<\Database\Factories\FuncionarioFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'data_nascimento',
        'cpf',
        'foto',
        'cargo_id',
        'unidade_id',
        'status',
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

    public function escalas()
    {
        return $this->hasMany(Escala::class);
    }

    public function bancoHorasMensal()
    {
        return $this->hasMany(BancoHorasMensal::class);
    }

    /**
     * O funcionário não possui setor_id direto: o setor é derivado da cadeia
     * funcionario -> unidade -> localidade -> setor. Use com eager loading
     * (::with('unidade.localidade.setor')) para evitar N+1.
     */
    public function getSetorAttribute()
    {
        return $this->unidade?->localidade?->setor;
    }

    /**
     * Scope para filtrar funcionários por setor, navegando pela cadeia
     * unidade -> localidade -> setor.
     */
    public function scopeDoSetor($query, int $setorId)
    {
        return $query->whereHas('unidade.localidade', function ($q) use ($setorId) {
            $q->where('setor_id', $setorId);
        });
    }
}
