<?php

namespace App\Policies;

use App\Models\Funcionario;
use App\Models\User;

class FuncionarioPolicy
{
     /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Funcionario $funcionario): bool
    {
        return $this->hasPermission($user, $funcionario);

    }
    public function store(User $user, Funcionario $funcionario): bool
    {
        return $this->hasPermission($user, $funcionario);

    }
    private function hasPermission(User $user, Funcionario $funcionario): bool
    {
        if ($user->hasRole(['admin', 'super admin'])) {
            return true;
        }

        return $user->unidade_id === $funcionario->unidade_id;
    }
}
