<?php

namespace App\Domain\Bodega\EventSourcing;

use App\Domain\Bodega\EventSourcing\EventStoreInterface;
use Illuminate\Support\Facades\Log;

/**
 * Base class para Aggregate Roots con Event Sourcing
 * Maneja la persistencia basada en eventos
 */
abstract class AggregateRoot
{
    protected string $id;
    protected int $version = 0;
    protected array $uncommittedEvents = [];
    protected EventStoreInterface $eventStore;

    public function __construct(string $id, EventStoreInterface $eventStore)
    {
        $this->id = $id;
        $this->eventStore = $eventStore;
    }

    /**
     * Obtener el ID del aggregate
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Obtener la versión actual
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Aplicar un evento al aggregate
     */
    protected function apply(object $event): void
    {
        // Aplicar el evento al estado actual
        $this->when($event);
        
        // Agregar a eventos no confirmados
        $this->uncommittedEvents[] = $event;
        
        // Incrementar versión
        $this->version++;
    }

    /**
     * Confirmar eventos (guardar en Event Store)
     */
    public function commit(): void
    {
        foreach ($this->uncommittedEvents as $event) {
            $this->eventStore->saveEvent($this->id, $event, $this->version);
        }
        
        $this->uncommittedEvents = [];
    }

    /**
     * Cargar aggregate desde eventos
     */
    public static function load(string $id, EventStoreInterface $eventStore): static
    {
        $events = $eventStore->getEvents($id);
        
        if (empty($events)) {
            throw new \InvalidArgumentException("Aggregate no encontrado: {$id}");
        }
        
        $aggregate = new static($id, $eventStore);
        
        foreach ($events as $eventData) {
            $event = $aggregate->deserializeEvent($eventData['event_type'], $eventData['event_data']);
            $aggregate->when($event);
            $aggregate->version = $eventData['version'];
        }
        
        return $aggregate;
    }

    /**
     * Obtener eventos no confirmados
     */
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    /**
     * Verificar si hay cambios pendientes
     */
    public function hasUncommittedEvents(): bool
    {
        return !empty($this->uncommittedEvents);
    }

    /**
     * Método abstracto para aplicar eventos al estado
     */
    abstract protected function when(object $event): void;

    /**
     * Deserializar evento desde datos almacenados
     */
    protected function deserializeEvent(string $eventType, array $eventData): object
    {
        if (!class_exists($eventType)) {
            throw new \RuntimeException("Clase de evento no encontrada: {$eventType}");
        }

        $reflection = new \ReflectionClass($eventType);
        
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Evento no es instanciable: {$eventType}");
        }

        // Intentar crear desde datos en array
        if (method_exists($eventType, 'fromArray')) {
            return $eventType::fromArray($eventData);
        }

        // Creación genérica (requiere constructor sin parámetros)
        $event = new $eventType();
        
        foreach ($eventData as $property => $value) {
            $setter = 'set' . ucfirst($property);
            if (method_exists($event, $setter)) {
                $event->$setter($value);
            } elseif (property_exists($event, $property)) {
                $reflectionProperty = new \ReflectionProperty($event, $property);
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($event, $value);
            }
        }

        return $event;
    }

    /**
     * Validar estado del aggregate
     */
    protected function validateState(): void
    {
        // Implementación por defecto, puede ser sobreescrito
        if (empty($this->id)) {
            throw new \RuntimeException('El ID del aggregate no puede estar vacío');
        }
    }

    /**
     * Obtener snapshot del estado actual
     */
    public function getSnapshot(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'state' => $this->getState(),
        ];
    }

    /**
     * Obtener estado actual del aggregate
     */
    abstract protected function getState(): array;

    /**
     * Restaurar estado desde snapshot
     */
    public function restoreFromSnapshot(array $snapshot): void
    {
        $this->id = $snapshot['id'];
        $this->version = $snapshot['version'];
        $this->restoreState($snapshot['state']);
    }

    /**
     * Restaurar estado desde datos
     */
    abstract protected function restoreState(array $state): void;
}
