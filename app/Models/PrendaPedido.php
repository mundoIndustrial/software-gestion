<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrendaPedido extends Model
{
    use SoftDeletes;

    protected $table = 'prendas_pedido';

    protected $fillable = [
        'numero_pedido',
        'nombre_prenda',
        'cantidad',
        'descripcion',
        'descripcion_variaciones',
        'cantidad_talla',
        'descripcion_armada',
        'color_id',
        'tela_id',
        'tipo_manga_id',
        'tipo_broche_id',
        'tiene_bolsillos',
        'tiene_reflectivo',
    ];

    protected $casts = [
        'cantidad_talla' => 'array',
    ];

    protected $appends = [
        'formatted_description',
    ];

    /**
     * Relación: Una prenda pertenece a un pedido (via numero_pedido)
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Relación: Una prenda pertenece a un color
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(ColorPrenda::class, 'color_id');
    }

    /**
     * Relación: Una prenda pertenece a una tela
     */
    public function tela(): BelongsTo
    {
        return $this->belongsTo(TelaPrenda::class, 'tela_id');
    }

    /**
     * Relación: Una prenda pertenece a un tipo de manga
     */
    public function tipoManga(): BelongsTo
    {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }

    /**
     * Relación: Una prenda pertenece a un tipo de broche
     */
    public function tipoBroche(): BelongsTo
    {
        return $this->belongsTo(TipoBroche::class, 'tipo_broche_id');
    }

    /**
     * Relación: Una prenda tiene muchos procesos
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(ProcesoPrenda::class);
    }

    /**
     * Relación: Una prenda tiene muchas entregas (por talla)
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(EntregaPrendaPedido::class, 'numero_pedido', 'numero_pedido')
            ->where('nombre_prenda', $this->nombre_prenda);
    }

    /**
     * Generar descripción detallada con formato especificado
     * Formato:
     * PRENDA 1: CAMISA DRILL
     * Color: NARANJA | Tela: DRILL BORNEO REF:REF-DB-001 | Manga: LARGA
     */
    public function generarDescripcionDetallada($index = 1)
    {
        $lineas = [];
        
        // Línea 1: Nombre de la prenda (PRENDA X: NOMBRE)
        $lineas[] = "PRENDA " . $index . ": " . strtoupper($this->nombre_prenda);
        
        // Línea 2: Atributos (Color, Tela, Manga) separados por pipe
        $atributos = [];
        
        // Color
        if ($this->color_id) {
            $colorNombre = null;
            if ($this->relationLoaded('color') && $this->color) {
                $colorNombre = $this->color->nombre;
            } else {
                $colorNombre = \Cache::remember("color_{$this->color_id}", 3600, function() {
                    $color = \App\Models\ColorPrenda::find($this->color_id);
                    return $color ? $color->nombre : null;
                });
            }
            if ($colorNombre) {
                $atributos[] = "Color: {$colorNombre}";
            }
        }
        
        // Tela
        if ($this->tela_id) {
            $telaNombre = null;
            $telaReferencia = null;
            if ($this->relationLoaded('tela') && $this->tela) {
                $telaNombre = $this->tela->nombre;
                $telaReferencia = $this->tela->referencia;
            } else {
                $telaData = \Cache::remember("tela_{$this->tela_id}", 3600, function() {
                    $tela = \App\Models\TelaPrenda::find($this->tela_id);
                    return $tela ? ['nombre' => $tela->nombre, 'referencia' => $tela->referencia] : null;
                });
                if ($telaData) {
                    $telaNombre = $telaData['nombre'];
                    $telaReferencia = $telaData['referencia'];
                }
            }
            if ($telaNombre) {
                $atributos[] = "Tela: {$telaNombre}" . ($telaReferencia ? " REF:{$telaReferencia}" : "");
            }
        }
        
        // Manga - extraer de descripcion_variaciones
        if ($this->descripcion_variaciones) {
            // Buscar patrón "Manga: VALOR"
            if (preg_match('/Manga:\s*([^|]+?)(?:\||$)/i', $this->descripcion_variaciones, $matches)) {
                $mangaNombre = trim($matches[1]);
                if ($mangaNombre) {
                    $atributos[] = "Manga: {$mangaNombre}";
                }
            }
        } elseif ($this->tipo_manga_id) {
            // Fallback a tipo_manga_id si no está en descripcion_variaciones
            $mangaNombre = null;
            if ($this->relationLoaded('tipoManga') && $this->tipoManga) {
                $mangaNombre = $this->tipoManga->nombre;
            } else {
                $mangaNombre = \Cache::remember("tipo_manga_{$this->tipo_manga_id}", 3600, function() {
                    $manga = \App\Models\TipoManga::find($this->tipo_manga_id);
                    return $manga ? $manga->nombre : null;
                });
            }
            if ($mangaNombre) {
                $atributos[] = "Manga: {$mangaNombre}";
            }
        }
        
        if (!empty($atributos)) {
            $lineas[] = implode(" | ", $atributos);
        }
        
        // Línea 3: Descripción + Bolsillos - extraer de columnas descripcion y descripcion_variaciones
        $lineaDescripcion = "";
        
        if ($this->descripcion) {
            // Buscar patrón "Descripción: VALOR"
            if (preg_match('/Descripción:\s*([^T]+?)(?:Tela:|Color:|Bolsillos:|Reflectivo:|Tallas:|$)/i', $this->descripcion, $matches)) {
                $descTexto = trim($matches[1]);
                if ($descTexto) {
                    $lineaDescripcion = "DESCRIPCION: {$descTexto}";
                }
            }
        }
        
        // Agregar Bolsillos si existe en descripcion_variaciones
        if ($this->descripcion_variaciones) {
            if (preg_match('/Bolsillos:\s*([^|]+?)(?:\||$)/i', $this->descripcion_variaciones, $matches)) {
                $bolsillosTexto = trim($matches[1]);
                if ($bolsillosTexto) {
                    if ($lineaDescripcion) {
                        $lineaDescripcion .= " | Bolsillos: {$bolsillosTexto}";
                    } else {
                        $lineaDescripcion = "Bolsillos: {$bolsillosTexto}";
                    }
                }
            }
        }
        
        // Agregar Reflectivo si existe en descripcion_variaciones
        if ($this->descripcion_variaciones) {
            if (preg_match('/Reflectivo:\s*([^|]+?)(?:\||$)/i', $this->descripcion_variaciones, $matches)) {
                $reflectivoTexto = trim($matches[1]);
                if ($reflectivoTexto) {
                    $lineaDescripcion .= " | Reflectivo: {$reflectivoTexto}";
                }
            }
        }
        
        if ($lineaDescripcion) {
            $lineas[] = $lineaDescripcion;
        }
        
        // Línea 4: Tallas y cantidades
        if ($this->cantidad_talla && is_array($this->cantidad_talla)) {
            $tallas = [];
            foreach ($this->cantidad_talla as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $tallas[] = "{$talla}:{$cantidad}";
                }
            }
            if (!empty($tallas)) {
                $lineas[] = "TALLAS: " . implode(", ", $tallas);
            }
        }
        
        return implode("\n", $lineas);
    }


    /**
     * Generar descripción formateada dinámicamente desde los datos
     */
    public function getFormattedDescriptionAttribute()
    {
        if (!$this->descripcion) {
            return '';
        }

        // Dividir la descripción original en líneas
        $lineas = explode("\n", $this->descripcion);
        
        // Buscar la línea de Tallas existente para extraer el mapeo correcto
        $tallasMapeadas = $this->extraerTallasDelDescripcion($lineas);

        // Si tenemos un mapeo correcto, regenerar la descripción con él
        if (!empty($tallasMapeadas) && is_array($tallasMapeadas)) {
            $resultado = [];
            
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;
                
                // Si es la línea de Tallas, regenerarla con el mapeo correcto
                if (strpos($linea, 'Tallas:') === 0) {
                    $tallas = [];
                    foreach ($tallasMapeadas as $talla => $cantidad) {
                        if ($cantidad > 0) {
                            $tallas[] = "{$talla}:{$cantidad}";
                        }
                    }
                    if (!empty($tallas)) {
                        $resultado[] = "Tallas: " . implode(', ', $tallas);
                    }
                } else {
                    // Mantener otras líneas tal cual
                    $resultado[] = $linea;
                }
            }
            
            return implode("\n", $resultado);
        }

        // Fallback: retornar descripción tal cual si no hay formato de tallas
        return implode("\n", array_filter(array_map('trim', $lineas)));
    }

    /**
     * Extraer el mapeo de tallas:cantidad desde la descripción
     * Busca líneas como "Tallas: M:15, L:8, XL:2"
     */
    private function extraerTallasDelDescripcion(array $lineas): array
    {
        $tallasMapeadas = [];
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (strpos($linea, 'Tallas:') === 0) {
                // Extraer la parte después de "Tallas: "
                $tallasPart = substr($linea, strlen('Tallas:'));
                $pares = explode(',', $tallasPart);
                
                foreach ($pares as $par) {
                    $par = trim($par);
                    if (strpos($par, ':') !== false) {
                        [$talla, $cantidad] = explode(':', $par, 2);
                        $talla = trim($talla);
                        $cantidad = (int) trim($cantidad);
                        
                        // Ignorar índices numéricos (0, 1, 2...) que no son tallas reales
                        if (!is_numeric($talla) || strlen($talla) > 2) {
                            $tallasMapeadas[$talla] = $cantidad;
                        }
                    }
                }
            }
        }
        
        return $tallasMapeadas;
    }
}
