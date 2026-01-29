<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('cotizaciones', function ($user) {
    return true;
});

Broadcast::channel('cotizaciones.asesor.{asesorId}', function ($user, $asesorId) {
    return (int) $user->id === (int) $asesorId;
});

Broadcast::channel('cotizaciones.contador', function ($user) {
    return $user->hasRole('contador') || $user->role === 'contador';
});

Broadcast::channel('pedidos.{asesorId}', function ($user, $asesorId) {
    return $user->id == $asesorId || 
           $user->hasRole('supervisor') || 
           $user->hasRole('admin');
});
