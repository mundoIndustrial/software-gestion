<?php

namespace App\Application\Pedidos\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PedidoAuthorizationService
 * Servicio que maneja las validaciones de autorización y permisos
 * para acceso a pedidos según el rol del usuario.
 * Responsabilidades:
 * - Validar si el usuario puede ver un pedido
 * - Validar filtros específicos por rol
 * - Generar auditoría de accesos bloqueados
 */
class PedidoAuthorizationService
{
    /**
     * Valida si un bodeguero puede acceder a este pedido
     * Los bodegueros NO pueden ver pedidos en estado:
     * - pendiente_cartera
     * - rechazado_cartera
     */
    public function validarAccesoBodeguero(object $pedido): ?string
    {
        if (!Auth::check() || !Auth::user()->hasRole('bodeguero')) {
            return null;
        }

        $estado = strtolower($pedido->estado ?? '');

        if ($estado === 'pendiente_cartera' || $estado === 'rechazado_cartera') {
            Log::warning('[PedidoAuthorizationService] 🔐 Bodeguero bloqueado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'usuario_id' => Auth::id(),
                'estado' => $pedido->estado
            ]);

            return 'No puedes ver recibos de pedidos en estado ' . $pedido->estado;
        }

        return null;
    }

    /**
     * Retorna true si el usuario actual es bodeguero
     */
    public function esBodeguero(): bool
    {
        return Auth::check() && Auth::user()->hasRole('bodeguero');
    }

    /**
     * Retorna true si el usuario actual es insumos
     */
    public function esInsumos(): bool
    {
        return Auth::check() && Auth::user()->hasRole('insumos');
    }

    /**
     * Valida si debe aplicar filtro de insumos
     * (no aplica si viene de /registros o /insumos/materiales)
     */
    public function debeAplicarFiltroInsumos(): bool
    {
        if (!$this->esInsumos()) {
            return false;
        }

        try {
            $referer = request()->headers->get('referer', '');
            $vieneDeRegistros = str_contains($referer, '/registros/');
            $vieneDeInsumos = str_contains($referer, '/insumos/materiales');

            return !$vieneDeRegistros && !$vieneDeInsumos;
        } catch (\Exception $e) {
            return true; // Por defecto aplicar filtro
        }
    }
}
