<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaVarianteCot extends Model
{
    protected $table = 'prenda_variantes_cot';

    protected $fillable = [
        'prenda_cot_id',
        'tipo_prenda',
        'es_jean_pantalon',
        'tipo_jean_pantalon',
        'genero_id',
        'color',
        'tipo_manga_id',
        'tiene_bolsillos',
        'obs_bolsillos',
        'aplica_manga',
        'tipo_manga',
        'obs_manga',
        'aplica_broche',
        'tipo_broche_id',
        'obs_broche',
        'tiene_reflectivo',
        'obs_reflectivo',
        'descripcion_adicional',
        'telas_multiples',
        'prenda_bodega',
    ];

    protected $casts = [
        'es_jean_pantalon' => 'boolean',
        'tiene_bolsillos' => 'boolean',
        'aplica_manga' => 'boolean',
        'aplica_broche' => 'boolean',
        'tiene_reflectivo' => 'boolean',
        'prenda_bodega' => 'boolean',
        'telas_multiples' => 'json',
    ];

    /**
     * Accessor: Decodificar telas_multiples si es string
     */
    public function getTelasMultiplesAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Mutator: Codificar telas_multiples como JSON si es array
     */
    public function setTelasMultiplesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['telas_multiples'] = json_encode($value);
        } else {
            $this->attributes['telas_multiples'] = $value;
        }
    }

    /**
     * Relación: Una variante pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una variante tiene un género
     */
    public function genero(): BelongsTo
    {
        return $this->belongsTo(GeneroPrenda::class, 'genero_id');
    }

    /**
     * Relación: Una variante tiene un tipo de broche
     */
    public function broche(): BelongsTo
    {
        return $this->belongsTo(TipoBroche::class, 'tipo_broche_id');
    }

    /**
     * Relación: Una variante tiene un tipo de manga
     */
    public function manga(): BelongsTo
    {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }

    /**
     * Accessor: Retorna el nombre del género
     * Si genero_id es NULL, retorna "Dama y Caballero"
     */
    public function getGeneroNombreAttribute()
    {
        if ($this->genero_id === null) {
            return 'Dama y Caballero';
        }

        // Si existe relación cargada, usar el nombre del género
        if ($this->relationLoaded('genero') && $this->genero) {
            return $this->genero->nombre ?? 'Desconocido';
        }

        // Fallback: mapeo directo de IDs
        $generos = [
            1 => 'Dama',
            2 => 'Caballero',
            3 => 'Unisex',
        ];

        return $generos[$this->genero_id] ?? 'Desconocido';
    }
}
