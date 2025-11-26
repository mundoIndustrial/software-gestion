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
        'cantidad_talla',
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
     * Generar descripción formateada dinámicamente desde los datos
     * Formato:
     * Prenda 1: CAMISA DRIL GABARDINA PRAGA NARANJA MANG ALARGA CABALLERO
     * Descripción: CON REFLECTIVO GRIS 2"25 CICLOS (MODELO FOTO)REF TELA 2725 BOLSILLOS PARA LOGO POR SEPARADA Y ESPALDAS PARA LOGO
     * Tallas: M:15, L:8, XL:2
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
