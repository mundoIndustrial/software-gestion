<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para gestionar prendas asociadas a cotizaciones
 * Utiliza la tabla prendas_cotizaciones creada en la migración 2025_11_20
 */
class PrendaCotizacion extends Model
{
    protected $table = 'prendas_cotizaciones';

    protected $fillable = [
        'cotizacion_id',
        'nombre_producto',
        'tipo_cotizacion',
        'genero',
        'es_jean_pantalon',
        'tipo_jean_pantalon',
        'descripcion',
        'tallas',
        'fotos',
        'telas',
        'notas_tallas',
        'productos',
        'estado'
    ];

    protected $casts = [
        'genero' => 'array',
        'tallas' => 'array',
        'fotos' => 'array',
        'telas' => 'array',
        'productos' => 'array'
    ];

    /**
     * Relación con Cotizacion
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación con costos
     */
    public function costos(): HasMany
    {
        return $this->hasMany(CostoPrenda::class);
    }

    /**
     * Relación con fotos de prenda
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoCot::class, 'prenda_cotizacion_id', 'id');
    }

    /**
     * Relación con telas
     */
    public function telas(): HasMany
    {
        return $this->hasMany(PrendaTelaCot::class, 'prenda_cotizacion_id', 'id');
    }

    /**
     * Relación con fotos de telas
     */
    public function telaFotos(): HasMany
    {
        return $this->hasMany(PrendaTelaFotoCot::class, 'prenda_cotizacion_id', 'id');
    }

    /**
     * Relaciones para variantes y tallas
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(VariantePrendaCotizacion::class, 'prenda_cotizacion_id', 'id');
    }

    public function tallas(): HasMany
    {
        return $this->hasMany(TallaPrendaCotizacion::class, 'prenda_cotizacion_id', 'id');
    }

    /**
     * Obtener total de costos de la prenda
     */
    public function getTotalCosto()
    {
        return $this->costos()->sum('costo');
    }
}
