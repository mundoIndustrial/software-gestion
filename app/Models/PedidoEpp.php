<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent: PedidoEpp
 * 
 * Capa de persistencia - Encapsula acceso a tabla "pedido_epps"
 * Tabla pivot entre pedidos y epp
 */
class PedidoEpp extends Model
{
    protected $table = 'pedido_epp';

    protected $fillable = [
        'pedido_produccion_id',
        'epp_id',
        'cantidad',
        'tallas_medidas',
        'observaciones',
    ];

    protected $casts = [
        'tallas_medidas' => 'array',
        'cantidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a un Pedido de Producción
     */
    public function pedidoProduccion(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Pertenece a un EPP
     */
    public function epp(): BelongsTo
    {
        return $this->belongsTo(Epp::class, 'epp_id');
    }

    /**
     * Relación: Un PedidoEpp tiene muchas imágenes
     */
    public function imagenes()
    {
        return $this->hasMany(PedidoEppImagen::class);
    }

    /**
     * Obtener la imagen principal
     */
    public function imagenPrincipal()
    {
        return $this->hasOne(PedidoEppImagen::class)->where('principal', true);
    }
}
