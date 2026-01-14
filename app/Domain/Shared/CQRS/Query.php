<?php

namespace App\Domain\Shared\CQRS;

/**
 * Query Interface
 * 
 * Contrato para todos los Query Objects
 * Representa una operación de LECTURA sin efectos secundarios
 * 
 * Patrón: Query Object
 * Responsabilidad: Definir una consulta con sus parámetros
 * 
 * Ejemplo:
 * $query = new ObtenerPedidoQuery(pedidoId: 123);
 * $resultado = $queryBus->execute($query);
 */
interface Query
{
    // Marker interface - todas las queries deben implementar esto
}
