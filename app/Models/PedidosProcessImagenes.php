<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: PedidosProcessImagenes
 * 
 * Imágenes asociadas a procesos productivos
 */
class PedidosProcessImagenes extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_procesos_imagenes';

    protected $fillable = [
        'proceso_prenda_detalle_id',
        'ruta_original',
        'ruta_webp',
        'orden',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(PedidosProcesosPrendaDetalle::class, 'proceso_prenda_detalle_id');
    }
}
