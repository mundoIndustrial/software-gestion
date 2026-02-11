<?php

namespace App\Infrastructure\Bodega\EventSourcing;

use App\Domain\Bodega\EventSourcing\EventStoreInterface;
use App\Models\PedidoEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Implementación Eloquent del Event Store
 * Persiste eventos de dominio en base de datos
 */
class EloquentEventStore implements EventStoreInterface
{
    /**
     * Guardar un evento de dominio
     */
    public function saveEvent(string $aggregateId, object $event, int $version): void
    {
        DB::transaction(function () use ($aggregateId, $event, $version) {
            PedidoEvent::create([
                'aggregate_id' => $aggregateId,
                'event_type' => get_class($event),
                'event_data' => json_encode($this->serializeEvent($event)),
                'version' => $version,
                'occurred_at' => now(),
                'metadata' => json_encode([
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
            ]);
        });
    }

    /**
     * Obtener todos los eventos para un aggregate
     */
    public function getEvents(string $aggregateId, int $fromVersion = 0): array
    {
        $eventRecords = PedidoEvent::where('aggregate_id', $aggregateId)
            ->where('version', '>', $fromVersion)
            ->orderBy('version', 'asc')
            ->get();

        return $eventRecords->map(function ($record) {
            return [
                'event_type' => $record->event_type,
                'event_data' => json_decode($record->event_data, true),
                'version' => $record->version,
                'occurred_at' => $record->occurred_at,
                'metadata' => json_decode($record->metadata, true)
            ];
        })->toArray();
    }

    /**
     * Obtener la última versión de un aggregate
     */
    public function getLastVersion(string $aggregateId): int
    {
        return PedidoEvent::where('aggregate_id', $aggregateId)
            ->max('version') ?? 0;
    }

    /**
     * Verificar si existe un aggregate
     */
    public function aggregateExists(string $aggregateId): bool
    {
        return PedidoEvent::where('aggregate_id', $aggregateId)->exists();
    }

    /**
     * Obtener eventos por tipo de evento
     */
    public function getEventsByType(string $eventType, ?\DateTime $fromDate = null): array
    {
        $query = PedidoEvent::where('event_type', $eventType);

        if ($fromDate) {
            $query->where('occurred_at', '>=', $fromDate);
        }

        $eventRecords = $query->orderBy('occurred_at', 'desc')
            ->limit(1000) // Limitar para no sobrecargar
            ->get();

        return $eventRecords->map(function ($record) {
            return [
                'aggregate_id' => $record->aggregate_id,
                'event_type' => $record->event_type,
                'event_data' => json_decode($record->event_data, true),
                'version' => $record->version,
                'occurred_at' => $record->occurred_at,
                'metadata' => json_decode($record->metadata, true)
            ];
        })->toArray();
    }

    /**
     * Limpiar eventos antiguos
     */
    public function cleanupOldEvents(\DateTime $beforeDate): int
    {
        return PedidoEvent::where('occurred_at', '<', $beforeDate)
            ->delete();
    }

    /**
     * Serializar evento para almacenamiento
     */
    private function serializeEvent(object $event): array
    {
        if (method_exists($event, 'toArray')) {
            return $event->toArray();
        }

        // Serialización genérica por reflexión
        $reflection = new \ReflectionClass($event);
        $properties = $reflection->getProperties();
        $data = [];

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($event);
            
            if ($value instanceof \DateTime) {
                $data[$property->getName()] = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $data[$property->getName()] = $value->toArray();
            } else {
                $data[$property->getName()] = $value;
            }
        }

        return $data;
    }
}
