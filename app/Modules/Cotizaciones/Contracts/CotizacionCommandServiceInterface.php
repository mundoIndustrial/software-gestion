<?php

namespace App\Modules\Cotizaciones\Contracts;

use App\Models\Cotizacion;

/**
 * Interface CotizacionCommandServiceInterface
 * 
 * Servicio de comandos de cotizaciones (escritura)
 * Principio: Single Responsibility (SRP)
 */
interface CotizacionCommandServiceInterface
{
    /**
     * Crear nueva cotización
     */
    public function create(array $data): Cotizacion;

    /**
     * Actualizar cotización
     */
    public function update(int $id, array $data): Cotizacion;

    /**
     * Eliminar cotización
     */
    public function delete(int $id): bool;

    /**
     * Cambiar estado de cotización
     */
    public function changeState(int $id, string $newState): Cotizacion;

    /**
     * Convertir borrador a cotización
     */
    public function publishDraft(int $id): Cotizacion;
}
