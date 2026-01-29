<?php

namespace App\Domain\Pedidos\Despacho\Repositories;

use App\Domain\Pedidos\Despacho\Entities\DesparChoParcial;
use Illuminate\Support\Collection;

/**
 * DesparChoParcialesRepository (Interfaz Domain)
 * 
 * Contrato para la persistencia de entidades DesparChoParcial
 * en el dominio del negocio.
 * 
 * La implementación debe estar en Infrastructure.
 */
interface DesparChoParcialesRepository
{
    /**
     * Guardar un despacho parcial
     */
    public function guardar(DesparChoParcial $despacho): void;

    /**
     * Guardar múltiples despachos parciales (transacción)
     * 
     * @param DesparChoParcial[] $despachos
     */
    public function guardarMultiples(array $despachos): void;

    /**
     * Obtener por ID
     */
    public function obtenerPorId(int $id): ?DesparChoParcial;

    /**
     * Obtener todos los despachos de un pedido
     */
    public function obtenerPorPedidoId(int $pedidoId): Collection;

    /**
     * Obtener despachos de un ítem específico
     */
    public function obtenerPorItem(string $tipoItem, int $itemId): Collection;

    /**
     * Obtener despachos de un pedido filtrados por tipo de ítem
     */
    public function obtenerPorPedidoYTipo(int $pedidoId, string $tipoItem): Collection;

    /**
     * Actualizar un despacho parcial
     */
    public function actualizar(DesparChoParcial $despacho): void;

    /**
     * Eliminar un despacho parcial
     */
    public function eliminar(int $id): void;

    /**
     * Verificar si existe un despacho para un ítem
     */
    public function existeParaItem(string $tipoItem, int $itemId): bool;
}
