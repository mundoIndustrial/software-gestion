<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BodegaDetalleTalla extends Model
{
    use SoftDeletes;

    protected $table = 'bodega_detalles_talla';
    
    protected $fillable = [
        'pedido_produccion_id',
        'recibo_prenda_id',
        'numero_pedido',
        'talla',
        'prenda_nombre',
        'asesor',
        'empresa',
        'cantidad',
        'pendientes',
        'observaciones_bodega',
        'fecha_pedido',
        'fecha_entrega',
        'estado_bodega',
        'area',
        'usuario_bodega_id',
        'usuario_bodega_nombre',
    ];

    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_entrega' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'fecha_pedido',
        'fecha_entrega',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Estados disponibles
    const ESTADO_PENDIENTE = 'Pendiente';
    const ESTADO_ENTREGADO = 'Entregado';
    const ESTADO_ANULADO = 'Anulado';

    // Áreas disponibles
    const AREA_COSTURA = 'Costura';
    const AREA_EPP = 'EPP';
    const AREA_OTRO = 'Otro';

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado_bodega', $estado);
    }

    /**
     * Scope para filtrar por área
     */
    public function scopePorArea($query, $area)
    {
        return $query->where('area', $area);
    }

    /**
     * Scope para filtrar por número de pedido
     */
    public function scopePorNumeroPedido($query, $numeroPedido)
    {
        return $query->where('numero_pedido', 'LIKE', "%{$numeroPedido}%");
    }

    /**
     * Scope para filtrar por cliente/empresa
     */
    public function scopePorEmpresa($query, $empresa)
    {
        return $query->where('empresa', 'LIKE', "%{$empresa}%");
    }

    /**
     * Scope para filtrar por asesor
     */
    public function scopePorAsesor($query, $asesor)
    {
        return $query->where('asesor', 'LIKE', "%{$asesor}%");
    }

    /**
     * Scope para filtrar por prenda
     */
    public function scopePorPrenda($query, $prenda)
    {
        return $query->where('prenda_nombre', 'LIKE', "%{$prenda}%");
    }

    /**
     * Scope para filtrar por talla
     */
    public function scopePorTalla($query, $talla)
    {
        return $query->where('talla', 'LIKE', "%{$talla}%");
    }

    /**
     * Scope para filtrar por fecha de pedido
     */
    public function scopePorFechaPedido($query, $fechaDesde = null, $fechaHasta = null)
    {
        if ($fechaDesde) {
            $query->whereDate('fecha_pedido', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha_pedido', '<=', $fechaHasta);
        }
        return $query;
    }

    /**
     * Scope para filtrar por fecha de entrega
     */
    public function scopePorFechaEntrega($query, $fechaDesde = null, $fechaHasta = null)
    {
        if ($fechaDesde) {
            $query->whereDate('fecha_entrega', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha_entrega', '<=', $fechaHasta);
        }
        return $query;
    }

    /**
     * Scope para obtener pedidos retrasados
     */
    public function scopeRetrasados($query)
    {
        return $query->whereDate('fecha_entrega', '<', now())
                    ->where('estado_bodega', self::ESTADO_PENDIENTE);
    }

    /**
     * Obtener estadísticas de costura
     */
    public static function obtenerEstadisticasCostura()
    {
        return [
            'total' => self::porArea(self::AREA_COSTURA)->count(),
            'pendientes' => self::porArea(self::AREA_COSTURA)->porEstado(self::ESTADO_PENDIENTE)->count(),
            'entregados' => self::porArea(self::AREA_COSTURA)->porEstado(self::ESTADO_ENTREGADO)->count(),
            'anulados' => self::porArea(self::AREA_COSTURA)->porEstado(self::ESTADO_ANULADO)->count(),
            'retrasados' => self::porArea(self::AREA_COSTURA)->retrasados()->count(),
        ];
    }

    /**
     * Obtener todos los estados disponibles
     */
    public static function obtenerEstadosDisponibles()
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_ENTREGADO => 'Entregado',
            self::ESTADO_ANULADO => 'Anulado',
        ];
    }

    /**
     * Obtener todas las áreas disponibles
     */
    public static function obtenerAreasDisponibles()
    {
        return [
            self::AREA_COSTURA => 'Costura',
            self::AREA_EPP => 'EPP',
            self::AREA_OTRO => 'Otro',
        ];
    }
}
