<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CalculadorDiasService;

class ProcesoPrenda extends Model
{
    use SoftDeletes;

    protected $table = 'procesos_prenda';

    protected $fillable = [
        'numero_pedido',
        'prenda_pedido_id',
        'proceso',
        'fecha_inicio',
        'fecha_fin',
        'dias_duracion',
        'encargado',
        'estado_proceso',
        'observaciones',
        'novedades',
        'codigo_referencia',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    /**
     * Relación: Un proceso pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Un proceso pertenece a un pedido (via prenda)
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Calcular automáticamente los días cuando se guarda
     */
    protected static function booted()
    {
        static::saving(function ($modelo) {
            // Si tiene fecha_inicio y fecha_fin, calcular los días
            if ($modelo->fecha_inicio && $modelo->fecha_fin) {
                $dias = CalculadorDiasService::calcularDiasHabiles(
                    $modelo->fecha_inicio,
                    $modelo->fecha_fin
                );
                
                $modelo->dias_duracion = CalculadorDiasService::formatearDias($dias);
            }
        });
    }

    /**
     * Obtener los días como número (sin el formato "X días")
     */
    public function getDiasNumero()
    {
        if (!$this->dias_duracion) {
            return 0;
        }

        // Extraer el número de la cadena "X días" o "X día"
        preg_match('/(\d+)/', $this->dias_duracion, $matches);
        
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    /**
     * Calcular días desde inicio hasta hoy (para procesos en curso)
     */
    public function getDiasHastaHoy()
    {
        if (!$this->fecha_inicio) {
            return null;
        }

        $dias = CalculadorDiasService::calcularDiasHastahoy($this->fecha_inicio);
        return CalculadorDiasService::formatearDias($dias);
    }

    /**
     * Verificar si el proceso está completado
     */
    public function estáCompleto()
    {
        return $this->estado_proceso === 'Completado' && $this->fecha_fin;
    }

    /**
     * Verificar si el proceso está en progreso
     */
    public function estáEnProgreso()
    {
        return $this->estado_proceso === 'En Progreso' && !$this->fecha_fin;
    }
}
