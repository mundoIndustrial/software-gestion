<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcesoPrendaDetalle extends Model
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
        'ubicaciones' => 'json',
        'tallas_dama' => 'json',
        'tallas_caballero' => 'json',
        'datos_adicionales' => 'json',
        'fecha_aprobacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a una prenda de pedido
     */
    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Pertenece a un tipo de proceso
     */
    public function tipoProceso(): BelongsTo
    {
        return $this->belongsTo(TipoProceso::class, 'tipo_proceso_id');
    }

    /**
     * Relación: Aprobado por usuario
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Relación: Imágenes del proceso
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ProcesoPrendaImagen::class, 'proceso_prenda_detalle_id');
    }

    /**
     * Relación: Imagen principal
     */
    public function imagenPrincipal()
    {
        return $this->hasOne(ProcesoPrendaImagen::class, 'proceso_prenda_detalle_id')
            ->where('es_principal', true);
    }

    /**
     * Scope: Procesos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    /**
     * Scope: Procesos aprobados
     */
    public function scopeAprobados($query)
    {
        return $query->where('estado', 'APROBADO');
    }

    /**
     * Scope: Procesos por prenda
     */
    public function scopePorPrenda($query, $prendaId)
    {
        return $query->where('prenda_pedido_id', $prendaId);
    }

    /**
     * Scope: Procesos por tipo
     */
    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_proceso_id', $tipoId);
    }
}
