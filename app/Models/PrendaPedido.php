<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\DescripcionPrendaHelper;
use App\Traits\HasLegibleAtributosPrenda;

class PrendaPedido extends Model
{
    use SoftDeletes, HasLegibleAtributosPrenda;

    protected $table = 'prendas_pedido';

    protected $fillable = [
        'numero_pedido',
        'nombre_prenda',
        'cantidad',
        'descripcion',
        'descripcion_variaciones',
        'cantidad_talla',
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
     * Relación: Una prenda tiene muchas fotos de tela
     */
    public function fotosTela(): HasMany
    {
        return $this->hasMany(PrendaFotoTelaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Generar descripción detallada con formato template especificado
     * Utiliza DescripcionPrendaHelper para generar formato estructurado
     * 
     * Formato:
     * PRENDA 1: CAMISA DRILL
     * Color: NARANJA | Tela: DRILL BORNEO REF:REF-DB-001 | Manga: LARGA
     * 
     * DESCRIPCIÓN:
     * - Logo: Logo bordado en espalda
     * 
     * Bolsillos:
     * • Pecho
     * • Espalda
     * 
     * Reflectivo:
     * • Mangas
     * 
     * TALLAS:
     * - S: 50
     * - M: 50
     * - L: 50
     */
    public function generarDescripcionDetallada($index = 1)
    {
        // Extraer datos estructurados de la prenda
        $datos = DescripcionPrendaHelper::extraerDatosPrenda($this, $index);
        
        // Generar descripción usando el template
        return DescripcionPrendaHelper::generarDescripcion($datos);
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
