<?php

namespace App\Domain\Bodega\EventSourcing;

/**
 * Interface para Event Store
 * Almacena y recupera eventos de dominio para Event Sourcing
 */
interface EventStoreInterface
{
    /**
     * Guardar un evento de dominio
     */
    public function saveEvent(string $aggregateId, object $event, int $version): void;

    /**
     * Obtener todos los eventos para un aggregate
     */
    public function getEvents(string $aggregateId, int $fromVersion = 0): array;

    /**
     * Obtener la última versión de un aggregate
     */
    public function getLastVersion(string $aggregateId): int;

    /**
     * Verificar si existe un aggregate
     */
    public function aggregateExists(string $aggregateId): bool;

    /**
     * Obtener eventos por tipo de evento
     */
    public function getEventsByType(string $eventType, ?\DateTime $fromDate = null): array;

    /**
     * Limpiar eventos antiguos (para mantenimiento)
     */
    public function cleanupOldEvents(\DateTime $beforeDate): int;
}
