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
     * Prenda 1: CAMISA DRILL
     * Color: NARANJA | Tela: DRILL BORNEO REF:REF-DB-001 | Manga: LARGA
     * Descripción: LOGO BORDADO EN ESPALDA...
     * Tallas: S:50, M:50, L:50...
     */
    public function generarDescripcionDetallada()
    {
        $lineas = [];
        
        // Línea 1: Nombre de la prenda (Prenda X:)
        $lineas[] = "Prenda {$this->id}:";
        
        // Línea 2: Atributos (Color, Tela, Manga) separados por pipe
        $atributos = [];
        
        // Color
        if ($this->color_id) {
            $colorNombre = null;
            if ($this->relationLoaded('color') && $this->color) {
                $colorNombre = $this->color->nombre;
            } else {
                $colorNombre = \Cache::remember("color_{$this->color_id}", 3600, function() {
                    $color = \App\Models\Color::find($this->color_id);
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
                    $tela = \App\Models\Tela::find($this->tela_id);
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
        
        // Manga
        if ($this->tipo_manga_id) {
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
        
        // Línea 3: Descripción + Bolsillos + Reflectivo combinados
        $descripcionCompleta = [];
        
        if ($this->descripcion) {
            $descripcionCompleta[] = $this->descripcion;
        }
        
        if ($this->tiene_bolsillos) {
            $descripcionCompleta[] = "Bolsillos: SI";
        }
        
        if ($this->tiene_reflectivo) {
            $descripcionCompleta[] = "Reflectivo: SI";
        }
        
        if (!empty($descripcionCompleta)) {
            $lineas[] = "Descripción: " . implode(" ", $descripcionCompleta);
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
                $lineas[] = "Tallas: " . implode(", ", $tallas);
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
