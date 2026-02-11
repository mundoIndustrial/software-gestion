<?php

namespace App\Application\Bodega\CQRS\Commands;

/**
 * Command Bus - Despacha commands a sus handlers correspondientes
 * Implementa el patrón Mediator para desacopolar emisor y receptor
 */
class CommandBus
{
    private array $handlers = [];

    /**
     * Registrar un handler para un tipo de command
     */
    public function register(string $commandClass, callable $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    /**
     * Despachar un command a su handler
     */
    public function dispatch(CommandInterface $command): mixed
    {
        $commandClass = get_class($command);

        if (!isset($this->handlers[$commandClass])) {
            throw new \InvalidArgumentException("No handler registered for command: {$commandClass}");
        }

        // Validar el command antes de despachar
        $command->validate();

        // Ejecutar el handler
        return ($this->handlers[$commandClass])($command);
    }

    /**
     * Verificar si hay un handler registrado
     */
    public function hasHandler(string $commandClass): bool
    {
        return isset($this->handlers[$commandClass]);
    }

    /**
     * Obtener todos los handlers registrados
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
