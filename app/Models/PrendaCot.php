<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\DescripcionPrendaHelper;

class PrendaCot extends Model
{
    protected $table = 'prendas_cot';

    protected $fillable = [
        'cotizacion_id',
        'nombre_producto',
        'descripcion',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    /**
     * Relación: Una prenda pertenece a una cotización
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples fotos
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples telas
     */
    public function telas(): HasMany
    {
        return $this->hasMany(PrendaTelaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples fotos de telas
     */
    public function telaFotos(): HasMany
    {
        return $this->hasMany(PrendaTelaFoto::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(PrendaTallaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples variantes
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(PrendaVarianteCot::class, 'prenda_cot_id');
    }

    /**
     * ACCESSOR: Determinar el género basado en las tallas seleccionadas
     * - Tallas 6,8,10,12,14,16,18,20,22,24,26 = DAMA
     * - Tallas 28,30,32,34,36,38,40,42,44,46,48,50 = CABALLERO
     * - Si hay de ambas = AMBOS
     * - Si hay letras (XS,S,M,L,XL,XXL,XXXL,XXXXL) = el tipo depende del contexto
     */
    public function getGeneroAttribute()
    {
        $tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
        $tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
        
        // Obtener todas las tallas de esta prenda
        // Usar la relación ya cargada si está disponible, sino cargar fresh
        $tallas = [];
        if ($this->relationLoaded('tallas')) {
            // Si tallas ya fue eager-loaded, usarlas
            $tallas = $this->tallas->pluck('talla')->toArray();
        } else {
            // Si no, cargar manualmente (lazy loading, pero necesario)
            $tallas = $this->tallas()->pluck('talla')->toArray();
        }
        
        if (empty($tallas)) {
            return null;
        }
        
        // Verificar cuáles tallas tiene
        $tieneDama = false;
        $tieneCaballero = false;
        
        foreach ($tallas as $talla) {
            if (in_array($talla, $tallasDama)) {
                $tieneDama = true;
            }
            if (in_array($talla, $tallasCaballero)) {
                $tieneCaballero = true;
            }
        }
        
        // Determinar y retornar el género
        if ($tieneDama && $tieneCaballero) {
            return 'Ambos (Dama y Caballero)';
        } elseif ($tieneDama) {
            return 'Dama';
        } elseif ($tieneCaballero) {
            return 'Caballero';
        }
        
        // Si son letras o desconocidas, retornar null
        return null;
    }

    /**
     * Generar descripción detallada con formato template especificado
     * Utiliza datos de variantes y telas para generar formato estructurado
     */
    public function generarDescripcionDetallada($index = 1)
    {
        try {
            $lineas = [];
            
            // Extraer observaciones específicas PRIMERO
            $obsBolsillos = null;
            $obsBroche = null;
            $obsReflectivo = null;
            
            if ($this->variantes && $this->variantes->count() > 0) {
                $variante = $this->variantes->first();
                
                // Extraer observaciones de descripcion_adicional
                $obsArray = $variante->descripcion_adicional ? explode(' | ', $variante->descripcion_adicional) : [];
                
                foreach ($obsArray as $obs) {
                    if (strpos($obs, 'Bolsillos:') === 0) {
                        $obsBolsillos = trim(str_replace('Bolsillos:', '', $obs));
                    } elseif (strpos($obs, 'Broche:') === 0) {
                        $obsBroche = trim(str_replace('Broche:', '', $obs));
                    } elseif (strpos($obs, 'Reflectivo:') === 0) {
                        $obsReflectivo = trim(str_replace('Reflectivo:', '', $obs));
                    }
                }
            }
            
            // Descripción principal
            if ($this->descripcion) {
                $lineas[] = trim($this->descripcion);
            }
            
            // Agregar observaciones con saltos de línea
            if ($this->variantes && $this->variantes->count() > 0) {
                $variante = $this->variantes->first();
                
                // Reflectivos con observación
                if ($obsReflectivo || ($variante->tiene_reflectivo && $variante->obs_reflectivo)) {
                    $texto = $obsReflectivo ?? $variante->obs_reflectivo;
                    $lineas[] = "<br><strong>Reflectivo:</strong> " . trim($texto);
                }
                
                // Bolsillos con observación
                if ($obsBolsillos || ($variante->tiene_bolsillos && $variante->obs_bolsillos)) {
                    $texto = $obsBolsillos ?? $variante->obs_bolsillos;
                    $lineas[] = "<br><strong>Bolsillos:</strong> " . trim($texto);
                }
                
                // Broche/Botón con observación (SOLO si tipo_broche_id existe)
                if ($variante->tipo_broche_id) {
                    $nombreBroche = 'Botón';
                    if ($variante->broche) {
                        $nombreBroche = $variante->broche->nombre ?? 'Botón';
                    }
                    
                    $texto = $obsBroche ?? ($variante->aplica_broche ? $variante->obs_broche : null);
                    if ($texto) {
                        $lineas[] = "<br><strong>{$nombreBroche}:</strong> " . trim($texto);
                    }
                }
            }
            
            return implode("", $lineas);
        } catch (\Exception $e) {
            \Log::error('Error generando descripción para PrendaCot', [
                'prenda_cot_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback a descripción simple
            return "DESCRIPCION: " . ($this->descripcion ? trim($this->descripcion) : 'Sin descripción');
        }
    }
}
