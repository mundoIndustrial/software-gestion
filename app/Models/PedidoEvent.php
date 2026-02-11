<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para almacenar eventos de dominio (Event Sourcing)
 */
class PedidoEvent extends Model
{
    use HasFactory;

    protected $table = 'pedido_events';

    protected $fillable = [
        'aggregate_id',
        'event_type',
        'event_data',
        'version',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'event_data' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected $dates = [
        'occurred_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Scope para eventos de un tipo específico
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope para eventos de un aggregate específico
     */
    public function scopeAggregate($query, string $aggregateId)
    {
        return $query->where('aggregate_id', $aggregateId);
    }

    /**
     * Scope para eventos desde una fecha
     */
    public function scopeFrom($query, \DateTime $date)
    {
        return $query->where('occurred_at', '>=', $date);
    }

    /**
     * Scope para eventos hasta una fecha
     */
    public function scopeTo($query, \DateTime $date)
    {
        return $query->where('occurred_at', '<=', $date);
    }

    /**
     * Scope para eventos en un rango de versiones
     */
    public function scopeVersionRange($query, int $fromVersion, int $toVersion)
    {
        return $query->whereBetween('version', [$fromVersion, $toVersion]);
    }

    /**
     * Obtener el nombre corto del tipo de evento
     */
    public function getEventShortNameAttribute(): string
    {
        $classParts = explode('\\', $this->event_type);
        return end($classParts);
    }

    /**
     * Verificar si es un evento importante
     */
    public function esEventoImportante(): bool
    {
        $eventosImportantes = [
            'PedidoEntregado',
            'PedidoAnulado',
            'PedidoCreado',
        ];

        return in_array($this->getEventShortNameAttribute(), $eventosImportantes);
    }

    /**
     * Obtener datos del usuario que generó el evento
     */
    public function getUsuarioAttribute(): ?array
    {
        return $this->metadata['user_id'] ?? null;
    }

    /**
     * Obtener IP del evento
     */
    public function getIpAttribute(): ?string
    {
        return $this->metadata['ip_address'] ?? null;
    }
}
