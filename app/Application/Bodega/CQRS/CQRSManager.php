<?php

namespace App\Application\Bodega\CQRS;

use App\Application\Bodega\CQRS\Commands\CommandBus;
use App\Application\Bodega\CQRS\Commands\CommandInterface;
use App\Application\Bodega\CQRS\Queries\QueryBus;
use App\Application\Bodega\CQRS\Queries\QueryInterface;

/**
 * CQRS Manager - Punto central de entrada para el patrón CQRS
 * Facilita el uso de Command Bus y Query Bus
 */
class CQRSManager
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct(CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
    }

    /**
     * Ejecutar un Command (operación de escritura)
     */
    public function execute(CommandInterface $command): mixed
    {
        return $this->commandBus->dispatch($command);
    }

    /**
     * Ejecutar una Query (operación de lectura)
     */
    public function ask(QueryInterface $query): mixed
    {
        return $this->queryBus->ask($query);
    }

    /**
     * Obtener el Command Bus
     */
    public function getCommandBus(): CommandBus
    {
        return $this->commandBus;
    }

    /**
     * Obtener el Query Bus
     */
    public function getQueryBus(): QueryBus
    {
        return $this->queryBus;
    }

    /**
     * Limpiar todo el cache de queries
     */
    public function clearQueryCache(): void
    {
        $this->queryBus->clearCache();
    }

    /**
     * Limpiar cache específico
     */
    public function clearQueryCacheFor(string $queryId): void
    {
        $this->queryBus->clearCacheFor($queryId);
    }

    /**
     * Obtener estadísticas del sistema CQRS
     */
    public function getStats(): array
    {
        return [
            'commands' => [
                'handlers_registrados' => count($this->commandBus->getHandlers()),
                'handlers' => array_keys($this->commandBus->getHandlers())
            ],
            'queries' => [
                'handlers_registrados' => count($this->queryBus->getHandlers()),
                'handlers' => array_keys($this->queryBus->getHandlers()),
                'cache_size' => count($this->queryBus->getHandlers()) // Simplificado
            ]
        ];
    }
}
