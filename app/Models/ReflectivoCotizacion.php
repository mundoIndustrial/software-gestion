<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReflectivoCotizacion extends Model
{
    use SoftDeletes;

    protected $table = 'reflectivo_cotizacion';

    protected $fillable = [
        'cotizacion_id',
        'tipo_prenda',
        'descripcion',
        'tipo_venta',
        'ubicacion',
        'imagenes',
        'observaciones_generales',
    ];

    protected $casts = [
        'ubicacion' => 'array',
        'imagenes' => 'array',
        'observaciones_generales' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación: Un reflectivo pertenece a una cotización
     */
    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    /**
     * Relación: Un reflectivo tiene muchas fotos
     */
    public function fotos()
    {
        return $this->hasMany(ReflectivoCotizacionFoto::class, 'reflectivo_cotizacion_id')->orderBy('orden');
    }
}
