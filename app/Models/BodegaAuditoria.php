<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodegaAuditoria extends Model
{
    protected $table = 'bodega_auditoria';

    protected $fillable = [
        'bodega_detalles_talla_id',
        'numero_pedido',
        'talla',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
        'usuario_id',
        'usuario_nombre',
        'ip_address',
        'accion',
        'descripcion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con BodegaDetallesTalla
     */
    public function detalle(): BelongsTo
    {
        return $this->belongsTo(BodegaDetallesTalla::class, 'bodega_detalles_talla_id');
    }
}
