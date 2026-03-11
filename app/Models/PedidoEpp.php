<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Eloquent: PedidoEpp
 * 
 * Capa de persistencia - Encapsula acceso a tabla "pedido_epps"
 * Tabla pivot entre pedidos y epp
 */
class PedidoEpp extends Model
{
    use SoftDeletes;
    protected $table = 'pedido_epp';

    protected $fillable = [
        'pedido_produccion_id',
        'epp_id',
        'cantidad',
        'tallas_medidas',
        'observaciones',
        'homologado_de',
    ];

    protected $casts = [
        'tallas_medidas' => 'array',
        'cantidad' => 'integer',
        'homologado_de' => 'integer',
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
     * Relación: El EPP anterior que fue homologado
     */
    public function homologadoDe(): BelongsTo
    {
        return $this->belongsTo(PedidoEpp::class, 'homologado_de');
    }

    /**
     * Relación: Los EPP que fueron homologados desde este
     */
    public function homologaciones()
    {
        return $this->hasMany(PedidoEpp::class, 'homologado_de');
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
