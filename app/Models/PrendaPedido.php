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
        'pedido_produccion_id',
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
     * Relación: Una prenda pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
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
        return $this->hasMany(EntregaPrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Event: Antes de guardar, generar descripcion_armada automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->descripcion_armada = $model->generarDescripcionArmada();
        });
    }

    /**
     * Generar la descripción armada combinando todos los campos
     * Formato:
     * CAMISA DRILL | Color: NARANJA | Tela: DRILL BORNEO REF:REF-DB-001 | Manga: LARGA | 
     * LOGO BORDADO EN ESPALDA... | Tallas: S:50, M:50, L:50...
     */
    public function generarDescripcionArmada(): string
    {
        $partes = [];

        // 1. Nombre de la prenda
        if ($this->nombre_prenda) {
            $partes[] = trim($this->nombre_prenda);
        }

        // 2. Descripción de variaciones (color, tela, manga, etc)
        if ($this->descripcion_variaciones) {
            $desc_var = $this->descripcion_variaciones;
            // Si es JSON, parsear
            if (is_string($desc_var) && json_decode($desc_var) !== null) {
                $vars = json_decode($desc_var, true);
                $varParts = [];
                
                if (isset($vars['color'])) {
                    $varParts[] = "Color: " . $vars['color'];
                }
                if (isset($vars['tela'])) {
                    $tela = $vars['tela'];
                    if (isset($vars['tela_referencia'])) {
                        $tela .= " REF:" . $vars['tela_referencia'];
                    }
                    $varParts[] = "Tela: " . $tela;
                }
                if (isset($vars['manga_nombre'])) {
                    $varParts[] = "Manga: " . $vars['manga_nombre'];
                }
                if (isset($vars['genero'])) {
                    $varParts[] = "Género: " . $vars['genero'];
                }
                
                if (!empty($varParts)) {
                    $partes[] = implode(" | ", $varParts);
                }
            } else {
                // Si no es JSON, agregar tal cual
                if (!empty($desc_var)) {
                    $partes[] = trim($desc_var);
                }
            }
        }

        // 3. Descripción de la prenda
        if ($this->descripcion) {
            $partes[] = trim($this->descripcion);
        }

        // 4. Tallas
        if ($this->cantidad_talla) {
            $tallas_str = '';
            if (is_string($this->cantidad_talla)) {
                $tallas_arr = json_decode($this->cantidad_talla, true);
            } else {
                $tallas_arr = $this->cantidad_talla;
            }

            if (is_array($tallas_arr)) {
                $tallas_list = [];
                foreach ($tallas_arr as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        $tallas_list[] = "{$talla}:{$cantidad}";
                    }
                }
                if (!empty($tallas_list)) {
                    $tallas_str = "Tallas: " . implode(", ", $tallas_list);
                    $partes[] = $tallas_str;
                }
            }
        }

        // Retornar descripción armada completa
        return implode(" | ", array_filter($partes));
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
