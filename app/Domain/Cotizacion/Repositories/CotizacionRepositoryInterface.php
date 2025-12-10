<?php

namespace App\Domain\Cotizacion\Repositories;

use App\Domain\Cotizacion\Entities\Cotizacion;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Shared\ValueObjects\UserId;

/**
 * CotizacionRepositoryInterface - Interfaz del repositorio de cotizaciones
 *
 * Define los métodos para persistencia de cotizaciones
 */
interface CotizacionRepositoryInterface
{
    /**
     * Guardar una cotización
     */
    public function save(Cotizacion $cotizacion): void;

    /**
     * Obtener cotización por ID
     */
    public function findById(CotizacionId $id): ?Cotizacion;

    /**
     * Obtener todas las cotizaciones del usuario
     */
    public function findByUserId(UserId $usuarioId): array;

    /**
     * Obtener solo borradores del usuario
     */
    public function findBorradoresByUserId(UserId $usuarioId): array;

    /**
     * Obtener solo enviadas del usuario
     */
    public function findEnviadasByUserId(UserId $usuarioId): array;

    /**
     * Eliminar una cotización
     */
    public function delete(CotizacionId $id): void;

    /**
     * Contar cotizaciones del usuario
     */
    public function countByUserId(UserId $usuarioId): int;

    /**
     * Contar borradores del usuario
     */
    public function countBorradoresByUserId(UserId $usuarioId): int;
}
