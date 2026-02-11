<?php

namespace App\Application\Bodega\CQRS\Commands;

/**
 * Interface base para todos los Commands
 * Los Commands representan intenciones de cambiar el estado del sistema
 */
interface CommandInterface
{
    /**
     * Obtener el ID del comando para tracking
     */
    public function getCommandId(): string;

    /**
     * Obtener datos del comando como array
     */
    public function toArray(): array;

    /**
     * Validar que el comando sea válido
     */
    public function validate(): void;
}
