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

/**
 * Canal para nuevos pedidos creados (acceso a cartera/supervisores/asesores)
 */
Broadcast::channel('pedidos.creados', function ($user) {
    // Permitir acceso a contadores, admins, supervisores y asesores
    return $user && (
        $user->hasRole('contador') || 
        $user->hasRole('admin') || 
        $user->hasRole('supervisor') || 
        $user->hasRole('asesor')
    );
});

/**
 * Canal para pedidos específicos del asesor
 */
Broadcast::channel('pedidos.asesor.{asesorId}', function ($user, $asesorId) {
    return (int) $user->id === (int) $asesorId || $user->hasRole('admin');
});

/**
 * Canales de Bodega - Detalles (privados)
 * Permite que usuarios autenticados en bodega se suscriban a actualizaciones de detalles
 */
Broadcast::channel('bodega-detalles-{numero_pedido}-{talla}', function ($user, $numero_pedido, $talla) {
    // Permitir acceso si el usuario tiene permiso en bodega
    return $user && ($user->hasRole(['Bodeguero', 'EPP-Bodega', 'Costura-Bodega', 'Admin']) || $user->role === 'admin');
});

/**
 * Canales de Bodega - Notas (privados)
 * Permite que usuarios autenticados accedan a notas
 */
Broadcast::channel('bodega-notas-{numero_pedido}-{talla}', function ($user, $numero_pedido, $talla) {
    return $user && ($user->hasRole(['Bodeguero', 'EPP-Bodega', 'Costura-Bodega', 'Admin']) || $user->role === 'admin');
});
/**
 * Canal Público: Supervisor de Pedidos
 * Permite que supervisores reciban actualizaciones de órdenes en tiempo real
 */
Broadcast::channel('supervisor-pedidos', function ($user) {
    return $user->hasRole(['supervisor_pedidos', 'admin']) || $user->hasRole('asesor');
});

/**
 * Canales para Observaciones Despacho (Tiempo Real)
 * Canal público para observaciones de un pedido específico
 */
Broadcast::channel('pedido.{pedidoId}', function ($user, $pedidoId) {
    // Cualquier usuario autenticado puede escuchar observaciones de pedidos
    return true; // Canal público para simplificar
});

/**
 * Canal público para despacho - observaciones
 */
Broadcast::channel('despacho.observaciones', function ($user) {
    return true; // Canal público
});

/**
 * Canal público para asesores - observaciones
 */
Broadcast::channel('asesores.observaciones', function ($user) {
    return true; // Canal público
});

/**
 * Canal público para ordenes - nuevos pedidos aprobados
 */
Broadcast::channel('ordenes', function ($user) {
    return true; // Canal público
});