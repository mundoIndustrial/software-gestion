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
        'tipo_cotizacion',
        'tipo_cotizacion_id',
        'fecha_inicio',
        'fecha_envio',
        'cliente',
        'asesora',
        'es_borrador',
        'estado',
        'productos',
        'especificaciones',
        'imagenes',
        'tecnicas',
        'observaciones_tecnicas',
        'ubicaciones',
        'observaciones_generales'
    ];

    protected $casts = [
        'es_borrador' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_envio' => 'datetime',
        'productos' => 'array',
        'especificaciones' => 'array',
        'imagenes' => 'array',
        'tecnicas' => 'array',
        'ubicaciones' => 'array',
        'observaciones_generales' => 'array',
        'estado' => 'string',
        'tipo_cotizacion' => 'string'
    ];

    /**
     * Relación: Una cotización pertenece a un usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Una cotización pertenece a un tipo de cotización
     */
    public function tipoCotizacion()
    {
        return $this->belongsTo(TipoCotizacion::class, 'tipo_cotizacion_id');
    }

    /**
     * Relación: Una cotización puede tener múltiples pedidos de producción
     */
    public function pedidosProduccion(): HasMany
    {
        return $this->hasMany(PedidoProduccion::class);
    }

    /**
     * Relación con prendas de cotización (friendly)
     */
    public function prendasCotizaciones(): HasMany
    {
        return $this->hasMany(PrendaCotizacionFriendly::class);
    }

    /**
     * Relación con logo/bordado/estampado de cotización
     */
    public function logoCotizacion()
    {
        return $this->hasOne(LogoCotizacion::class);
    }

    /**
     * Relación con historial de cambios
     */
    public function historial()
    {
        return $this->hasMany(HistorialCotizacion::class);
    }
}
