<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TipoBroche Model
 * 
 * ACTUALIZACIÓN [16/01/2026]: Tabla renombrada de 'tipos_broche' a 'tipos_broche_boton'
 * Razón: Unificar broche y botón bajo un mismo catálogo. El nombre del modelo
 * se mantiene como TipoBroche por compatibilidad, pero ahora referencía la tabla
 * tipos_broche_boton que contiene ambos tipos: broches y botones.
 * 
 * Campo FK actualizado: tipo_broche_boton_id (antes tipo_broche_id)
 */
class TipoBroche extends Model
{
    // CAMBIO: Tabla renombrada de 'tipos_broche' a 'tipos_broche_boton'
    protected $table = 'tipos_broche_boton';
    
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
