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
     * Generar descripción detallada con formato template especificado
     * Utiliza datos de variantes y telas para generar formato estructurado
     */
    public function generarDescripcionDetallada($index = 1)
    {
        try {
            $lineas = [];
            
            // Encabezado: PRENDA X: NOMBRE
            $lineas[] = "PRENDA {$index}: " . strtoupper($this->nombre_producto);
            
            // Extraer observaciones específicas PRIMERO
            $obsManga = null;
            $obsBolsillos = null;
            $obsBroche = null;
            $obsReflectivo = null;
            
            if ($this->variantes && $this->variantes->count() > 0) {
                $variante = $this->variantes->first();
                
                // Extraer observaciones de descripcion_adicional
                $obsArray = $variante->descripcion_adicional ? explode(' | ', $variante->descripcion_adicional) : [];
                
                foreach ($obsArray as $obs) {
                    if (strpos($obs, 'Manga:') === 0) {
                        $obsManga = trim(str_replace('Manga:', '', $obs));
                    } elseif (strpos($obs, 'Bolsillos:') === 0) {
                        $obsBolsillos = trim(str_replace('Bolsillos:', '', $obs));
                    } elseif (strpos($obs, 'Broche:') === 0) {
                        $obsBroche = trim(str_replace('Broche:', '', $obs));
                    } elseif (strpos($obs, 'Reflectivo:') === 0) {
                        $obsReflectivo = trim(str_replace('Reflectivo:', '', $obs));
                    }
                }
            }
            
            // Información de tela y variante
            if ($this->variantes && $this->variantes->count() > 0) {
                $variante = $this->variantes->first();
                $atributos = [];
                
                // Color (de la variante)
                if ($variante->color) {
                    $atributos[] = "Color: " . strtoupper($variante->color);
                }
                
                // Tela y referencia (de telas_multiples JSON en variante)
                if ($variante->telas_multiples && is_array($variante->telas_multiples) && !empty($variante->telas_multiples)) {
                    $primeraTela = $variante->telas_multiples[0] ?? [];
                    $tela = $primeraTela['tela'] ?? null;
                    $referencia = $primeraTela['referencia'] ?? null;
                    
                    if ($tela) {
                        $telaInfo = strtoupper($tela);
                        if ($referencia) {
                            $telaInfo .= " REF:" . strtoupper($referencia);
                        }
                        $atributos[] = "Tela: " . $telaInfo;
                    }
                }
                
                // Manga (SOLO si tipo_manga_id existe)
                if ($variante->tipo_manga_id) {
                    $nombreManga = $variante->manga->nombre ?? $variante->tipo_manga ?? null;
                    if ($nombreManga) {
                        $mangaInfo = "Manga: " . strtoupper($nombreManga);
                        // Agregar observación de manga si existe
                        if ($obsManga) {
                            $mangaInfo .= " ({$obsManga})";
                        }
                        $atributos[] = $mangaInfo;
                    }
                }
                
                if (!empty($atributos)) {
                    $lineas[] = implode(" | ", $atributos);
                }
            }
            
            // Descripción principal
            if ($this->descripcion) {
                $lineas[] = "\nDESCRIPCION: " . trim($this->descripcion);
            }
            
            // Agregar observaciones como viñetas (SIN manga, que ya está arriba)
            if ($this->variantes && $this->variantes->count() > 0) {
                $variante = $this->variantes->first();
                
                // Reflectivos con observación
                if ($obsReflectivo || ($variante->tiene_reflectivo && $variante->obs_reflectivo)) {
                    $texto = $obsReflectivo ?? $variante->obs_reflectivo;
                    $lineas[] = "   • Reflectivo: " . trim($texto);
                }
                
                // Bolsillos con observación
                if ($obsBolsillos || ($variante->tiene_bolsillos && $variante->obs_bolsillos)) {
                    $texto = $obsBolsillos ?? $variante->obs_bolsillos;
                    $lineas[] = "   • Bolsillos: " . trim($texto);
                }
                
                // Broche/Botón con observación (SOLO si tipo_broche_id existe)
                if ($variante->tipo_broche_id) {
                    $nombreBroche = 'Broche';
                    if ($variante->broche) {
                        $nombreBroche = $variante->broche->nombre ?? 'Broche';
                    }
                    
                    $texto = $obsBroche ?? ($variante->aplica_broche ? $variante->obs_broche : null);
                    if ($texto) {
                        $lineas[] = "   • {$nombreBroche}: " . trim($texto);
                    }
                }
            }
            
            // Tallas
            if ($this->tallas && $this->tallas->count() > 0) {
                $tallasList = [];
                foreach ($this->tallas as $talla) {
                    $tallasList[] = strtoupper($talla->talla) . ": {$talla->cantidad}";
                }
                if (!empty($tallasList)) {
                    $lineas[] = "Tallas: " . implode(", ", $tallasList);
                }
            }
            
            return implode("\n", $lineas);
        } catch (\Exception $e) {
            \Log::error('Error generando descripción para PrendaCot', [
                'prenda_cot_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback a descripción simple
            return "PRENDA {$index}: " . strtoupper($this->nombre_producto) . 
                   "\n" . ($this->descripcion ? $this->descripcion : 'Sin descripción');
        }
    }
}
