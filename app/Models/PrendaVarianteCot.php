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
    ];

    protected $casts = [
        'es_jean_pantalon' => 'boolean',
        'tiene_bolsillos' => 'boolean',
        'aplica_manga' => 'boolean',
        'aplica_broche' => 'boolean',
        'tiene_reflectivo' => 'boolean',
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
     * Nota: Esta relación solo funciona si genero_id es un ID simple
     * Para múltiples géneros, usar el accessor getGeneroNombreAttribute
     */
    public function genero(): BelongsTo
    {
        // Si genero_id es un array, no podemos establecer la relación
        if (is_array($this->genero_id)) {
            return new class {
                public function first() { return null; }
            };
        }
        
        return $this->belongsTo(GeneroPrenda::class, 'genero_id');
    }

    /**
     * Relación: Una variante tiene un tipo de broche
     */
    public function broche(): BelongsTo
    {
        return $this->belongsTo(TipoBrocheBoton::class, 'tipo_broche_id');
    }

    /**
     * Relación: Una variante tiene un tipo de manga
     */
    public function manga(): BelongsTo
    {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }

    /**
     * Accessor: Retorna el nombre del género o géneros
     * Maneja múltiples géneros en formato JSON
     */
    public function getGeneroNombreAttribute()
    {
        $generosIds = $this->attributes['genero_id'] ?? null;
        
        // Si es null o vacío, retornar valor por defecto
        if (!$generosIds) {
            return 'No especificado';
        }
        
        // Si es un string JSON, decodificarlo primero
        if (is_string($generosIds)) {
            $generosIds = json_decode($generosIds, true) ?? $generosIds;
        }
        
        // Si es un array (JSON decodificado)
        if (is_array($generosIds)) {
            if (count($generosIds) === 0) {
                return 'No especificado';
            }
            
            // Mapeo de IDs a nombres
            $mapeoGeneros = [
                1 => 'Caballero',
                2 => 'Dama',
                3 => 'Unisex',
            ];
            
            $nombres = [];
            foreach ($generosIds as $id) {
                if (isset($mapeoGeneros[$id])) {
                    $nombres[] = $mapeoGeneros[$id];
                }
            }
            
            return count($nombres) > 0 ? implode(' y ', $nombres) : 'No especificado';
        }
        
        // Si es un solo valor (compatibilidad con datos antiguos)
        $mapeoGeneros = [
            1 => 'Caballero',
            2 => 'Dama',
            3 => 'Unisex',
        ];
        
        return $mapeoGeneros[$generosIds] ?? 'Desconocido';
    }
}
