<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcesosPrenda extends Model
{
    use SoftDeletes;

    protected $table = 'procesos_prenda';

    protected $fillable = [
        'numero_pedido',
        'proceso',
        'fecha_inicio',
        'fecha_fin',
        'dias_duracion',
        'encargado',
        'estado_proceso',
        'observaciones',
        'codigo_referencia',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    /**
     * Relación: Un proceso pertenece a un pedido de producción
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Obtener el nombre del proceso formateado
     */
    public function getProceso()
    {
        return ucfirst(str_replace('_', ' ', $this->proceso));
    }

    /**
     * Calcular días entre fecha_inicio y fecha_fin
     */
    public function calcularDias()
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        return $this->fecha_inicio->diffInDays($this->fecha_fin);
    }

    /**
     * Verificar si el proceso está completado
     */
    public function estaCompletado()
    {
        return $this->estado_proceso === 'Completado';
    }

    /**
     * Verificar si el proceso está en progreso
     */
    public function estaEnProgreso()
    {
        return $this->estado_proceso === 'En Progreso';
    }

    /**
     * Verificar si el proceso está pendiente
     */
    public function estaPendiente()
    {
        return $this->estado_proceso === 'Pendiente';
    }
}
