<?php

namespace App\Domain\ProcesoSeguimiento\Repositories;

use App\Models\ProcesoPrenda;

/**
 * Repository Interface: ProcesoPrendaSeguimientoRepository
 *
 * Contrato del dominio para persistencia de procesos de seguimiento.
 * Los Use Cases dependen de esta interfaz, nunca de la implementación Eloquent.
 */
interface ProcesoPrendaSeguimientoRepository
{
    /**
     * Buscar un proceso activo (no Completado) por pedido, prenda y área.
     * Usado para el upsert: reutilizar en lugar de duplicar la misma área.
     */
    public function encontrarActivoPorArea(int $numeroPedido, int $prendaId, string $area): ?ProcesoPrenda;

    /**
     * Buscar el proceso más reciente de una prenda en un pedido.
     * Usado al eliminar procesos para revertir el consecutivo al área anterior.
     */
    public function encontrarMasReciente(int $prendaId, int $numeroPedido): ?ProcesoPrenda;

    /**
     * Persistir (crear o actualizar) un proceso.
     */
    public function guardar(ProcesoPrenda $proceso): ProcesoPrenda;

    /**
     * Eliminar definitivamente un proceso (sin soft delete).
     */
    public function eliminar(int $procesoId): void;

    /**
     * Obtener todos los procesos de una prenda ordenados cronológicamente.
     */
    public function obtenerPorPrenda(int $prendaId): \Illuminate\Database\Eloquent\Collection;
}
