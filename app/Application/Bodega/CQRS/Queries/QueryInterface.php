<?php

namespace App\Application\Bodega\CQRS\Queries;

/**
 * Interface base para todas las Queries
 * Las Queries representan solicitudes de lectura de datos
 */
interface QueryInterface
{
    /**
     * Obtener el ID de la query para caching/tracking
     */
    public function getQueryId(): string;

    /**
     * Obtener parámetros de la query
     */
    public function getParameters(): array;

    /**
     * Validar que la query sea válida
     */
    public function validate(): void;
}
