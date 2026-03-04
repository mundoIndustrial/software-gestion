<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: PedidosProcesosPrendaTalla
 * 
 * Talla relacional para un proceso de prenda.
 * Soporta DAMA, CABALLERO, UNISEX como géneros.
 * Cada registro = genero + talla + cantidad en un proceso específico.
 */
class PedidosProcesosPrendaTalla extends Model
{
    protected $table = 'pedidos_procesos_prenda_tallas';

    protected $fillable = [
        'proceso_prenda_detalle_id',
        'genero',
        'talla',
        'cantidad',
        'es_sobremedida',
        'ubicaciones',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'ubicaciones' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    /**
     * Proceso de prenda al cual pertenece esta talla
     */
    public function procesoPrendaDetalle(): BelongsTo
    {
        return $this->belongsTo(PedidosProcesosPrendaDetalle::class, 'proceso_prenda_detalle_id');
    }

    /**
     * Colores asignados a esta talla en el proceso
     */
    public function coloresAsignados(): HasMany
    {
        return $this->hasMany(PedidosProcesosPrendaTallaColor::class, 'pedidos_procesos_prenda_talla_id');
    }

    /**
     * Imágenes asociadas a esta talla específica (modo por_tallas)
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(PedidosProcessImagenes::class, 'proceso_prenda_talla_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Filtrar por género
     */
    public function scopeGenero($query, $genero)
    {
        return $query->where('genero', $genero);
    }

    /**
     * Filtrar por talla
     */
    public function scopeTalla($query, $talla)
    {
        return $query->where('talla', $talla);
    }

    /**
     * Filtrar por proceso
     */
    public function scopeProceso($query, $procesoPrendaDetalleId)
    {
        return $query->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId);
    }

    /**
     * Filtrar solo registros con cantidad > 0
     */
    public function scopeConCantidad($query)
    {
        return $query->where('cantidad', '>', 0);
    }

    // ============================================================
    // MÉTODOS
    // ============================================================

    /**
     * Obtener la cantidad total considerando si hay colores desglosados
     * 
     * Si la talla tiene colores asignados (pedidos_procesos_prenda_talla_colores),
     * suma las cantidades de cada color. Si no, retorna la cantidad directa.
     * 
     * @return int
     */
    public function obtenerCantidadTotal(): int
    {
        // Verificar si hay colores desglosados para esta talla
        $coloresCount = $this->coloresAsignados()->count();
        
        if ($coloresCount > 0) {
            // Sumar cantidades desde tabla de colores
            return (int) $this->coloresAsignados()->sum('cantidad');
        }
        
        // Usar cantidad directa de la talla
        return (int) ($this->cantidad ?? 0);
    }
}
