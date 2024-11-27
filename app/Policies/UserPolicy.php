<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{

    /**
     * Determine whether the user can update the model.
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        if($user->id === $model->id) {
          return true;
        }
        if($model->hasRole('super admin')) {
            return false;
        }
        if($model->hasRole('admin') && $user->hasRole('super admin')) {
            return true;
        }elseif($model->hasRole('admin')) {
            return false;
        }

        return true;

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {

        if($model->hasRole('super admin')) {
            return false;
        }
        if($model->hasRole('admin') && $user->hasRole('super admin')) {
            return true;
        }elseif($model->hasRole('admin')) {
            return false;
        }

        return true;
    }


}
