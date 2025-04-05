<?php

namespace App\Policies;

use App\Models\Unidade;
use App\Models\User;

class UnidadePolicy
{
    /**
     * Create a new policy instance.
     */
    public function update(User $user, Unidade $unidade)
    {
        return $this->hasPermission($user, $unidade);

    }
    public function delete(User $user, Unidade $unidade)
    {
        return $this->hasPermission($user, $unidade);

    }
    public function show(User $user, Unidade $unidade)
    {
        return $this->hasPermission($user, $unidade);

    }

    private function hasPermission(User $user, Unidade $unidade ): bool
    {
        if($user->hasRole('super admin')) {
            return true;
        }
        return $user->setor_id == $unidade->localidade->setor_id;
    }
}
