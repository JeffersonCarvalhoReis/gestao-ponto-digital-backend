<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Admin channel - only accessible to admin users
Broadcast::channel('admin.notifications', function ($user) {
    return $user->hasAnyRole(['admin', 'super admin']);
});
Broadcast::channel('admin.registros-ponto', function ($user) {
    return $user->hasAnyRole(['admin', 'super admin']);
});

// Unit-specific channel - accessible to users in that unit
Broadcast::channel('unidade.{unidadeId}', function ($user, $unidadeId) {
    return (int) $user->unidade_id === (int) $unidadeId;
});

// User-specific channel for private notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
