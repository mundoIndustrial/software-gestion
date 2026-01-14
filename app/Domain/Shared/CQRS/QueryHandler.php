<?php

namespace App\Domain\Shared\CQRS;

/**
 * QueryHandler Interface
 * 
 * Contrato para manejadores de queries
 * Un handler por query
 * 
 * Patrón: Handler
 * Responsabilidad: Ejecutar la query y retornar el resultado
 * 
 * Ejemplo:
 * class ObtenerPedidoHandler implements QueryHandler {
 *     public function handle(ObtenerPedidoQuery $query) { ... }
 * }
 */
interface QueryHandler
{
    /**
     * Ejecutar la query
     * 
     * @param Query $query
     * @return mixed Resultado de la query
     */
    public function handle(Query $query): mixed;
}
