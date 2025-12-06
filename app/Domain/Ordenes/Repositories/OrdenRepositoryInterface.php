<?php

namespace App\Domain\Ordenes\Repositories;

use App\Domain\Ordenes\Entities\Orden;
use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use Illuminate\Support\Collection;

/**
 * Repository Interface: OrdenRepositoryInterface
 * 
 * Define el contrato para persistencia de órdenes.
 * Implementación: App\Repositories\EloquentOrdenRepository
 */
interface OrdenRepositoryInterface
{
    /**
     * Guardar una orden (crear o actualizar)
     */
    public function save(Orden $orden): void;

    /**
     * Obtener orden por número
     */
    public function findByNumero(NumeroOrden $numero): ?Orden;

    /**
     * Obtener todas las órdenes
     */
    public function findAll(): Collection;

    /**
     * Obtener órdenes por cliente
     */
    public function findByCliente(string $cliente): Collection;

    /**
     * Obtener órdenes por estado
     */
    public function findByEstado(string $estado): Collection;

    /**
     * Eliminar orden
     */
    public function delete(NumeroOrden $numero): void;

    /**
     * Obtener total de órdenes
     */
    public function count(): int;
}
