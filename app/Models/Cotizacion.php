<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cotizacion extends Model
{
    use SoftDeletes;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'user_id',
        'cliente',
        'productos',
        'especificaciones',
        'imagenes',
        'tecnicas',
        'observaciones_tecnicas',
        'ubicaciones',
        'observaciones_generales',
        'es_borrador',
        'estado',
    ];

    protected $casts = [
        'productos' => 'array',
        'especificaciones' => 'array',
        'imagenes' => 'array',
        'tecnicas' => 'array',
        'ubicaciones' => 'array',
        'observaciones_generales' => 'array',
        'es_borrador' => 'boolean',
    ];

    /**
     * Relación: Una cotización pertenece a un usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Una cotización puede tener un pedido de producción
     */
    public function pedidoProduccion()
    {
        return $this->hasOne(PedidoProduccion::class);
    }
}
