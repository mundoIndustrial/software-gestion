<?php

namespace App\Domain\Shared;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

/**
 * Clase base para todos los eventos de dominio
 * 
 * Define el contrato que todos los eventos deben cumplir:
 * - Tienen un ID de agregado que los originó
 * - Saben cuándo ocurrieron
 * - Saben su propio nombre para serialización
 * 
 * Principios:
 * - Immutable: no cambian después de ocurrir
 * - Self-describing: contienen todos sus datos
 * - Versionable: pueden evolucionar
 */
abstract class DomainEvent
{
    /**
     * Identificador único del evento
     */
    private UuidInterface $eventId;

    /**
     * Identificador del agregado que originó el evento
     */
    private UuidInterface|string|int $aggregateId;

    /**
     * Fecha y hora exacta del evento
     */
    private DateTimeImmutable $occurredAt;

    /**
     * Versión del evento (para evolución futura)
     */
    private int $version;

    public function __construct(
        UuidInterface|string|int $aggregateId,
        ?DateTimeImmutable $occurredAt = null,
        int $version = 1
    ) {
        $this->aggregateId = $aggregateId;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
        $this->version = $version;
        
        // Generar UUID para evento si es necesario
        if (class_exists('\Ramsey\Uuid\Uuid')) {
            $this->eventId = \Ramsey\Uuid\Uuid::uuid4();
        }
    }

    /**
     * Obtener ID del evento
     */
    public function getEventId(): UuidInterface|string
    {
        return $this->eventId ?? bin2hex(random_bytes(16));
    }

    /**
     * Obtener ID del agregado que originó el evento
     */
    public function getAggregateId(): UuidInterface|string|int
    {
        return $this->aggregateId;
    }

    /**
     * Obtener fecha de ocurrencia del evento
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Obtener versión del evento
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Obtener nombre del evento (para routing y logs)
     * 
     * @return string Nombre del evento (ej: "pedido.produccion.creado")
     */
    public function getEventName(): string
    {
        $class = class_basename(static::class);
        // Convertir PedidoProduccionCreado → pedido.produccion.creado
        return strtolower(preg_replace('/([A-Z])/', '.$1', lcfirst($class)));
    }

    /**
     * Convertir evento a array para serialización
     */
    public function toArray(): array
    {
        return [
            'event_id' => (string)$this->getEventId(),
            'event_name' => $this->getEventName(),
            'aggregate_id' => $this->getAggregateId(),
            'occurred_at' => $this->getOccurredAt()->toIso8601String(),
            'version' => $this->getVersion(),
            'data' => $this->extractEventData(),
        ];
    }

    /**
     * Extraer datos específicos del evento (a implementar en subclases)
     * 
     * @return array Datos relevantes del evento
     */
    protected function extractEventData(): array
    {
        return [];
    }

    /**
     * Para reconocimiento del tipo de evento
     */
    public function __toString(): string
    {
        return $this->getEventName();
    }
}
