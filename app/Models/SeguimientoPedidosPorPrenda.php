<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoPedidosPorPrenda extends Model
{
    use HasFactory;

    protected $table = 'seguimiento_pedidos_por_prenda';

    protected $fillable = [
        'pedido_produccion_id',
        'prenda_id',
        'proceso_prenda_id',
        'tipo_recibo',
        'area',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'encargado',
        'observaciones',
        'consecutivo_actual',
        'consecutivo_inicial',
        'activo',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'consecutivo_actual' => 'integer',
        'consecutivo_inicial' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    /**
     * Get the pedido that owns the seguimiento.
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Get the prenda that owns the seguimiento.
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_id');
    }

    /**
     * Get the proceso_prenda that owns the seguimiento.
     */
    public function procesoPrenda(): BelongsTo
    {
        return $this->belongsTo(ProcesoPrenda::class, 'proceso_prenda_id');
    }

    /**
     * Scope para obtener seguimientos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar por tipo de recibo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_recibo', $tipo);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por área
     */
    public function scopePorArea($query, string $area)
    {
        return $query->where('area', $area);
    }

    /**
     * Scope para obtener seguimientos en progreso
     */
    public function scopeEnProgreso($query)
    {
        return $query->where('estado', 'En Progreso');
    }

    /**
     * Scope para obtener seguimientos completados
     */
    public function scopeCompletados($query)
    {
        return $query->where('estado', 'Completado');
    }

    /**
     * Obtener siguiente consecutivo
     */
    public function getSiguienteConsecutivoAttribute(): int
    {
        return $this->consecutivo_actual + 1;
    }

    /**
     * Verificar si hay consecutivos disponibles
     */
    public function tieneConsecutivosDisponibles(): bool
    {
        return $this->consecutivo_actual < $this->consecutivo_inicial;
    }

    /**
     * Avanzar al siguiente consecutivo
     */
    public function avanzarConsecutivo(): bool
    {
        if (!$this->tieneConsecutivosDisponibles()) {
            return false;
        }

        $this->consecutivo_actual++;
        return $this->save();
    }

    /**
     * Iniciar proceso
     */
    public function iniciarProceso(string $encargado = null): bool
    {
        $this->estado = 'En Progreso';
        $this->fecha_inicio = now();
        if ($encargado) {
            $this->encargado = $encargado;
        }
        return $this->save();
    }

    /**
     * Completar proceso
     */
    public function completarProceso(): bool
    {
        $this->estado = 'Completado';
        $this->fecha_fin = now();
        return $this->save();
    }

    /**
     * Pausar proceso
     */
    public function pausarProceso(string $observacion = null): bool
    {
        $this->estado = 'Pausado';
        if ($observacion) {
            $this->observaciones = $observacion;
        }
        return $this->save();
    }

    /**
     * Obtener duración en días
     */
    public function getDuracionDiasAttribute(): ?int
    {
        if (!$this->fecha_inicio) {
            return null;
        }

        $fin = $this->fecha_fin ?: now();
        return $this->fecha_inicio->diffInDays($fin);
    }

    /**
     * Verificar si el proceso está activo (no completado ni pausado)
     */
    public function estaActivo(): bool
    {
        return in_array($this->estado, ['Pendiente', 'En Progreso']);
    }

    /**
     * Obtener icono del área basado en el mapeo de procesos
     */
    public function getIconoAreaAttribute(): string
    {
        $processoIconMap = [
            'Pedido Recibido' => 'description',
            'Creación Orden' => 'description',
            'Insumos' => 'inventory_2',
            'Insumos y Telas' => 'inventory_2',
            'Corte' => 'content_cut',
            'Bordado' => 'brush',
            'Estampado' => 'print',
            'Costura' => 'dry_cleaning',
            'Polos' => 'checkroom',
            'Taller' => 'construction',
            'Lavandería' => 'local_laundry_service',
            'Lavanderia' => 'local_laundry_service',
            'Arreglos' => 'handyman',
            'Control de Calidad' => 'verified',
            'Control-Calidad' => 'verified',
            'Entrega' => 'local_shipping',
            'Despacho' => 'directions_car',
            'Despachos' => 'directions_car',
            'Reflectivo' => 'highlight',
            'Marras' => 'search'
        ];

        return $processoIconMap[$this->area] ?? 'description';
    }
}
