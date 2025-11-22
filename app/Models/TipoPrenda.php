<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TipoPrenda extends Model
{
    protected $table = 'tipos_prenda';

    protected $fillable = [
        'nombre',
        'codigo',
        'palabras_clave',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'palabras_clave' => 'array',
        'activo' => 'boolean'
    ];

    /**
     * Relación: Un tipo de prenda tiene una configuración de variaciones
     */
    public function variacionesDisponibles(): HasOne
    {
        return $this->hasOne(PrendaVariacionesDisponibles::class);
    }

    /**
     * Buscar tipo de prenda por palabras clave
     */
    public static function reconocerPorNombre($nombre)
    {
        $nombreUpper = strtoupper($nombre);
        
        $tipos = self::where('activo', true)->get();
        
        foreach ($tipos as $tipo) {
            foreach ($tipo->palabras_clave as $palabra) {
                if (strpos($nombreUpper, strtoupper($palabra)) !== false) {
                    return $tipo;
                }
            }
        }
        
        return null;
    }
}
