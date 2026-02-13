<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * TipoPrenda Model
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $codigo
 * @property array $palabras_clave
 * @property string|null $descripcion
 * @property bool $activo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
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
            // Manejar diferentes formatos de palabras_clave
            $palabras = $tipo->palabras_clave;
            
            if (is_string($palabras)) {
                // Si es string, intentar parsear como JSON primero
                try {
                    $palabras = json_decode($palabras, true);
                    if (!is_array($palabras)) {
                        // Si no es JSON válido, separar por coma
                        $palabras = array_map('trim', explode(',', $tipo->palabras_clave));
                    }
                } catch (\Exception $e) {
                    // Si falla, separar por coma
                    $palabras = array_map('trim', explode(',', $tipo->palabras_clave));
                }
            }
            
            // Asegurar que es array
            if (!is_array($palabras)) {
                $palabras = [];
            }
            
            foreach ($palabras as $palabra) {
                if (!empty($palabra) && strpos($nombreUpper, strtoupper($palabra)) !== false) {
                    return $tipo;
                }
            }
        }
        
        return null;
    }
}
