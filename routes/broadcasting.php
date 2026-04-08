<?php

use Illuminate\Support\Facades\Broadcast;

// ========================================
// BROADCASTING AUTH ROUTES (WebSocket)
// ========================================

Broadcast::routes(['middleware' => ['auth']]);

// Canal de pedido - Validar acceso específico del usuario
Broadcast::channel('pedido.{pedidoId}', function ($user, $pedidoId) {
    // Solo usuarios con roles permitidos
    if (!$user->hasAnyRole(['asesor', 'despacho', 'supervisor_pedidos', 'admin'])) {
        return false;
    }
    
    // Si es admin, permitir todo
    if ($user->hasRole('admin')) {
        return true;
    }
    
    // Si es asesor, solo su propio pedido
    if ($user->hasRole('asesor')) {
        $pedido = \App\Models\PedidoProduccion::where('id', $pedidoId)
            ->where('asesor_id', $user->id)
            ->exists();
        return (bool) $pedido;
    }
    
    // Despacho, supervisor_pedidos: permitir
    return $user->hasAnyRole(['despacho', 'supervisor_pedidos']);
});

// Canal de observaciones despacho - Solo para rol despacho o admin
Broadcast::channel('despacho.observaciones', function ($user) {
    return $user->hasAnyRole(['despacho', 'admin']);
});

// Canal de observaciones asesores - Solo para rol asesor o admin
Broadcast::channel('asesores.observaciones', function ($user) {
    return $user->hasAnyRole(['asesor', 'admin']);
});
