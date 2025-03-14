<?php

namespace App\Policies;

use App\Models\Funcionario;
use App\Models\Justificativa;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class JustificativaPolicy
{
    /**
     * Create a new policy instance.
     */
    public function show(User $user, Justificativa $justificativa): bool
    {
        return $this->hasPermission($user, $justificativa);
    }

    public function delete(User $user, Justificativa $justificativa): bool
    {
        return $this->hasPermission($user, $justificativa);
    }


    private function hasPermission(User $user, Justificativa $justificativa): bool
    {
        if ($user->hasRole(['admin', 'super admin'])) {
            return true;
        }

        return $user->unidade_id === $justificativa->funcionario->unidade_id;
    }
}
