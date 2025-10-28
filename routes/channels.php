<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('orders', function ($user) {
    return $user ? ['id' => $user->id] : false;
});

Broadcast::channel('orders-updates-public', function ($user) {
    return true; // Canal público - cualquier usuario autenticado puede escuchar
});
