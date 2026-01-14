<?php

namespace App\Domain\Shared\CQRS;

/**
 * CommandHandler Interface
 * 
 * Contrato para manejadores de commands
 * Un handler por command
 * 
 * Patrón: Handler
 * Responsabilidad: Ejecutar el comando y retornar el resultado
 * 
 * Ejemplo:
 * class CrearPedidoHandler implements CommandHandler {
 *     public function handle(CrearPedidoCommand $command) { ... }
 * }
 */
interface CommandHandler
{
    /**
     * Ejecutar el comando
     * 
     * @param Command $command
     * @return mixed Resultado del comando (usualmente el agregado creado/actualizado)
     */
    public function handle(Command $command): mixed;
}
