<?php

namespace App\Domain\Shared\CQRS;

/**
 * Command Interface
 * 
 * Contrato para todos los Command Objects
 * Representa una operación de ESCRITURA con efectos secundarios
 * 
 * Patrón: Command Object
 * Responsabilidad: Definir un comando con sus parámetros
 * 
 * Ejemplo:
 * $command = new CrearPedidoCommand(cliente: $cliente, items: $items);
 * $resultado = $commandBus->execute($command);
 */
interface Command
{
    // Marker interface - todos los commands deben implementar esto
}
