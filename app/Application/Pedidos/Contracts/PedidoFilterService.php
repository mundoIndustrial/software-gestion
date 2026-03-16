<?php

namespace App\Application\Pedidos\Contracts;

/**
 * PedidoFilterService
 * 
 * Contrato para filtrar datos según roles y permisos
 */
interface PedidoFilterService
{
    /**
     * Aplicar filtros según rol del usuario
     * 
     * @param array $datos
     * @param string|null $rol
     * @return array
     */
    public function aplicarFiltrosPorRol(array $datos, ?string $rol = null): array;

    /**
     * Verificar si usuario puede ver este pedido
     */
    public function puedeVerPedido(int $pedidoId, string $rol): bool;

    /**
     * Verificar si usuario puede ver procesos COSTURA-BODEGA
     */
    public function tieneProcesoCosturaBodega(array $prendas): bool;
}
