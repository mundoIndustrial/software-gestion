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
        'genero',
        'color_id',
        'tela_id',
        'tipo_manga_id',
        'tipo_broche_id',
        'tiene_bolsillos',
        'tiene_reflectivo',
        'manga_obs',
        'bolsillos_obs',
        'broche_obs',
        'reflectivo_obs',
        'de_bodega',
    ];

    protected $casts = [
        'cantidad_talla' => 'array',
        'genero' => 'array', // ✅ Almacena múltiples géneros como JSON
    ];

    protected $appends = [
        'formatted_description',
    ];

    /**
     * ACCESSOR: cantidad siempre devuelve la suma de cantidad_talla
     * Esto hace que sea DINÁMICO y siempre refleje la suma correcta
     * Maneja ambas estructuras: plana {talla: cantidad} y anidada {genero: {talla: cantidad}}
     */
    public function getCantidadAttribute()
    {
        // Usar el cast 'array' de Laravel en lugar de acceder a attributes directamente
        $cantidadesTalla = $this->cantidad_talla ?? [];
        
        \Log::debug('📊 [PrendaPedido.getCantidadAttribute] Accediendo a cantidad', [
            'prenda_id' => $this->id,
            'nombre' => $this->nombre_prenda,
            'cantidad_talla_raw' => $cantidadesTalla,
            'es_array' => is_array($cantidadesTalla),
            'es_string' => is_string($cantidadesTalla),
        ]);
        
        // Si aún es string (doble encoding), decodificar hasta obtener array
        while (is_string($cantidadesTalla)) {
            $decoded = json_decode($cantidadesTalla, true);
            if ($decoded === null) {
                break;
            }
            $cantidadesTalla = $decoded;
            \Log::debug('📊 [PrendaPedido.getCantidadAttribute] Decodificado de JSON', [
                'resultado' => $cantidadesTalla,
                'es_array' => is_array($cantidadesTalla),
            ]);
        }
        
        // Asegurar que sea array
        if (!is_array($cantidadesTalla)) {
            $cantidadesTalla = [];
        }
        
        // Calcular suma - manejar ambas estructuras
        $suma = 0;
        foreach ($cantidadesTalla as $clave => $valor) {
            if (is_array($valor)) {
                // Estructura anidada: {genero: {talla: cantidad}}
                $suma += array_sum($valor);
            } else {
                // Estructura plana: {talla: cantidad}
                $suma += (int)$valor;
            }
        }
        
        \Log::debug('📊 [PrendaPedido.getCantidadAttribute] Suma calculada', [
            'suma' => $suma,
            'tallas' => $cantidadesTalla,
        ]);
        
        return $suma;
    }

    /**
     * MUTADOR: Permite asignar cantidad, pero no se usa porque el accessor siempre devuelve la suma
     */
    public function setCantidadAttribute($value)
    {
        // Permitir asignar, pero será ignorado cuando se acceda (el accessor devuelve la suma)
        $this->attributes['cantidad'] = $value;
    }

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
     * Relación: Una prenda tiene muchas fotos
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda tiene muchas fotos de logo
     */
    public function fotosLogo(): HasMany
    {
        return $this->hasMany(PrendaFotoLogoPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda tiene muchas fotos de tela
     */
    public function fotosTela(): HasMany
    {
        return $this->hasMany(PrendaFotoTelaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda REFLECTIVO tiene un registro en tabla prendas_reflectivo
     * (Si es tipo reflectivo sin cotización)
     */
    public function reflectivo()
    {
        return $this->hasOne(PrendaReflectivo::class, 'prenda_pedido_id');
    }

    /**
     * Recalcular cantidad_total del pedido cuando se crea o actualiza una prenda
     * NOTA: cantidad es DINÁMICO y se calcula desde cantidad_talla via accessor
     */
    protected static function boot()
    {
        parent::boot();

        // Cuando se crea una prenda, recalcular cantidad_total del pedido
        static::created(function ($prenda) {
            if ($prenda->numero_pedido) {
                // Obtener todas las prendas del pedido
                $prendas = static::where('numero_pedido', $prenda->numero_pedido)->get();
                
                // Calcular suma total (cada prenda->cantidad usa el accessor dinámico)
                $cantidadTotal = $prendas->sum(function($p) {
                    return $p->cantidad; // Esto usa el accessor getCantidadAttribute()
                });
                
                PedidoProduccion::where('numero_pedido', $prenda->numero_pedido)
                    ->update(['cantidad_total' => $cantidadTotal]);
            }
        });

        // Cuando se actualiza una prenda, recalcular cantidad_total del pedido
        static::updated(function ($prenda) {
            if ($prenda->numero_pedido) {
                // Obtener todas las prendas del pedido
                $prendas = static::where('numero_pedido', $prenda->numero_pedido)->get();
                
                // Calcular suma total
                $cantidadTotal = $prendas->sum(function($p) {
                    return $p->cantidad; // Esto usa el accessor getCantidadAttribute()
                });
                
                PedidoProduccion::where('numero_pedido', $prenda->numero_pedido)
                    ->update(['cantidad_total' => $cantidadTotal]);
            }
        });

        // Cuando se elimina una prenda, recalcular cantidad_total del pedido
        static::deleted(function ($prenda) {
            if ($prenda->numero_pedido) {
                $cantidadTotal = static::where('numero_pedido', $prenda->numero_pedido)
                    ->sum('cantidad');
                
                PedidoProduccion::where('numero_pedido', $prenda->numero_pedido)
                    ->update(['cantidad_total' => $cantidadTotal]);
            }
        });
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
    public function generarDescripcionDetallada($index = 1, $totalPrendas = null)
    {
        // ✅ MANEJAR AMBOS CASOS:
        // 1. Si tiene color_id/tela_id poblados → construir dinámicamente desde relaciones
        // 2. Si todo es NULL → usar descripcion existente (datos migrados)
        
        $tieneRelacionesPobladas = $this->color_id || $this->tela_id || $this->tipo_manga_id || $this->tipo_broche_id;
        
        if ($tieneRelacionesPobladas) {
            // Caso 1: Construir dinámicamente desde relaciones
            $datos = DescripcionPrendaHelper::extraerDatosPrenda($this, $index, $totalPrendas);
            $descripcionGenerada = DescripcionPrendaHelper::generarDescripcion($datos, $totalPrendas);
            \Log::info('📝 [DESCRIPCION] Generada dinámicamente desde Helper:', [
                'prenda_id' => $this->id,
                'nombre' => $this->nombre_prenda,
                'descripcion' => substr($descripcionGenerada, 0, 100) . '...',
            ]);
            return $descripcionGenerada;
        } else {
            // Caso 2: Usar descripcion existente (datos migrados de la BD antigua)
            if ($this->descripcion) {
                // Formatear como: PRENDA X: NOMBRE\n + descripcion existente + \nTallas: ...
                $nombre = strtoupper($this->nombre_prenda ?? '');
                $desc = trim($this->descripcion);
                
                // Obtener tallas del campo cantidad_talla
                $tallas = $this->cantidad_talla ?? [];
                $tallasStr = '';
                if (!empty($tallas) && is_array($tallas)) {
                    $tallasFormato = [];
                    foreach ($tallas as $talla => $cantidad) {
                        if ($cantidad > 0) {
                            $tallasFormato[] = "{$talla}: {$cantidad}";
                        }
                    }
                    if (!empty($tallasFormato)) {
                        $tallasStr = "\nTallas: " . implode(', ', $tallasFormato);
                    }
                }
                
                // ✅ Si solo hay una prenda, no mostrar "PRENDA 1:"
                if ($totalPrendas === 1) {
                    $resultado = "{$nombre}\n{$desc}{$tallasStr}";
                } else {
                    $resultado = "PRENDA {$index}: {$nombre}\n{$desc}{$tallasStr}";
                }
                \Log::info('📝 [DESCRIPCION] Usando descripcion existente:', [
                    'prenda_id' => $this->id,
                    'nombre' => $nombre,
                    'descripcion' => substr($resultado, 0, 100) . '...',
                ]);
                return $resultado;
            }
            
            // Fallback si no hay descripcion tampoco
            // ✅ Si solo hay una prenda, no mostrar "PRENDA 1:"
            if ($totalPrendas === 1) {
                return strtoupper($this->nombre_prenda ?? '');
            } else {
                return "PRENDA {$index}: " . strtoupper($this->nombre_prenda ?? '');
            }
        }
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
