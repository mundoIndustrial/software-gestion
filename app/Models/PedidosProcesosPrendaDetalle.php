<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: PedidosProcesosPrendaDetalle
 * 
 * Representa procesos productivos aplicados a una prenda
 * (bordado, estampado, DTF, sublimado, etc.)
 * 
 * @property int $id
 * @property int $prenda_pedido_id
 * @property int|null $tipo_proceso_id
 * @property array $ubicaciones
 * @property string|null $observaciones
 * @property array|null $tallas_dama
 * @property array|null $tallas_caballero
 * @property string|null $estado
 * @property string|null $notas_rechazo
 * @property \Carbon\Carbon|null $fecha_aprobacion
 * @property int|null $aprobado_por
 * @property array|null $datos_adicionales
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PedidosProcesosPrendaDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_procesos_prenda_detalles';

    protected $fillable = [
        'prenda_pedido_id',
        'tipo_proceso_id',
        'ubicaciones',
        'observaciones',
        'tallas_dama',
        'tallas_caballero',
        'estado',
        'notas_rechazo',
        'fecha_aprobacion',
        'aprobado_por',
        'datos_adicionales',
    ];

    protected $casts = [
        'ubicaciones' => 'array',
        'tallas_dama' => 'array',
        'tallas_caballero' => 'array',
        'datos_adicionales' => 'array',
        'fecha_aprobacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    public function tipoProceso(): BelongsTo
    {
        return $this->belongsTo(TipoProceso::class, 'tipo_proceso_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(PedidosProcessImagenes::class, 'proceso_prenda_detalle_id');
    }

    /**
     * Tallas relacional del proceso (DAMA, CABALLERO, UNISEX)
     * 
     * @deprecated tallas_dama y tallas_caballero (legacy JSON)
     * @see PedidosProcesosPrendaTalla para la fuente canónica de tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(PedidosProcesosPrendaTalla::class, 'proceso_prenda_detalle_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePendientes($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'APROBADO');
    }

    public function scopeEnProduccion($query)
    {
        return $query->where('estado', 'EN_PRODUCCION');
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'COMPLETADO');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', 'RECHAZADO');
    }
}
