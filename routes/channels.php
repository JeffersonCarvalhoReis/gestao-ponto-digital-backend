<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/
// Broadcast::routes(['middleware' => ['web', 'auth:sanctum']]);


// Admin channel - only accessible to admin users
// Broadcast::channel('notifications.{userId}', function ($user) {
//     return $user->hasAnyRole(['admin', 'super admin']);
// });


// Broadcast::channel('admin.registros-ponto', function ($user) {
//     if ($user->hasRole('super admin')) {
//         return true;
//     }
//     return $user->setor_id === request()->setor_id;
// });

// Unit-specific channel - accessible to users in that unit
// Broadcast::channel('unidade.{userId}', function ($user) {
//     return $user->hasAnyRole(['user', 'gestor']);
// });

