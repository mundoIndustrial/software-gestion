<?php

namespace App\Domain\Epp\Repositories;

use App\Domain\Epp\Aggregates\EppAggregate;
use Illuminate\Support\Collection;

/**
 * Repositorio para acceso a datos de EPP
 * Encapsula consultas a la base de datos
 * 
 * Responsabilidad: Convertir entre agregados de dominio y persistencia
 */
interface EppRepositoryInterface
{
    /**
     * Obtener un EPP por ID
     */
    public function obtenerPorId(int $id): ?EppAggregate;

    /**
     * Obtener un EPP por código
     */
    public function obtenerPorCodigo(string $codigo): ?EppAggregate;

    /**
     * Obtener todos los EPP activos
     *
     * @return Collection<EppAggregate>
     */
    public function obtenerActivos(): Collection;

    /**
     * Obtener EPP por categoría
     *
     * @return Collection<EppAggregate>
     */
    public function obtenerPorCategoria(string $categoria): Collection;

    /**
     * Buscar EPP por término (código o nombre)
     *
     * @return Collection<EppAggregate>
     */
    public function buscar(string $termino): Collection;

    /**
     * Guardar un EPP (crear o actualizar)
     */
    public function guardar(EppAggregate $epp): void;

    /**
     * Eliminar un EPP (soft delete)
     */
    public function eliminar(EppAggregate $epp): void;

    /**
     * Obtener todas las categorías disponibles
     *
     * @return Collection<string>
     */
    public function obtenerCategorias(): Collection;
}
