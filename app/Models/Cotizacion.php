<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    use SoftDeletes;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'user_id',
        'numero_cotizacion',
        'fecha',
        'cliente',
        'asesora',
        'productos',
        'especificaciones',
        'imagenes',
        'tecnicas',
        'observaciones_tecnicas',
        'ubicaciones',
        'observaciones_generales',
        'es_borrador',
        'estado',
        'cotizar_segun_indicaciones'
    ];

    protected $casts = [
        'productos' => 'array',
        'especificaciones' => 'array',
        'imagenes' => 'array',
        'tecnicas' => 'array',
        'ubicaciones' => 'array',
        'observaciones_generales' => 'array',
        'es_borrador' => 'boolean',
        'fecha' => 'date'
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

    /**
     * Relación con prendas de la cotización
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(PrendaCotizacion::class);
    }

    /**
     * Relación con formatos de cotización
     */
    public function formatos(): HasMany
    {
        return $this->hasMany(FormatoCotizacion::class);
    }
}
