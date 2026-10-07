<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'user',
        'password',
        'unidade_id',
        'setor_id',
        'pode_corrigir_pendencias',
    ];

    public function unidade()
    {
        return $this->belongsTo(Unidade::class);
    }
    public function setor()
    {
        return $this->belongsTo(Setor::class);
    }

    /**
     * Admin e super admin sempre podem resolver pendências de correção de
     * ponto. Gestor só pode se um administrador liberou explicitamente
     * (padrão: não). Demais funções nunca.
     */
    public function podeResolverPendencias(): bool
    {
        if ($this->hasAnyRole(['admin', 'super admin'])) {
            return true;
        }

        return $this->hasRole('gestor') && (bool) $this->pode_corrigir_pendencias;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'pode_corrigir_pendencias' => 'boolean',
        ];
    }
}
