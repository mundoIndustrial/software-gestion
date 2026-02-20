<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaPedidoTallaColor;

/**
 * PrendaPedido Model
 * 
 * Modelo normalizado para gestionar prendas en pedidos de producción.
 * 
 * Cambios importantes:
 * - Usa `pedido_produccion_id` como FK (NO numero_pedido)
 * - Almacena solo datos básicos de la prenda
 * - Variantes (color, tela, manga, broche, bolsillos) están en tabla hija prenda_variantes
 * - NO maneja reflectivo
 * - Escalable para ERP de producción textil
 * 
 * @property int $id
 * @property int $pedido_produccion_id
 * @property string $nombre_prenda
 * @property string|null $descripcion
 * @property bool $de_bodega
 * @property int|null $prenda_id
 * @property string|null $observaciones
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string $color
 * @property string $tela
 * @property string $tipo_manga
 * @property string $tipo_broche
 */
class PrendaPedido extends Model
{
    use SoftDeletes;

    protected $table = 'prendas_pedido';

    protected $fillable = [
        'pedido_produccion_id', //  REQUIRED: Foreign Key a pedidos_produccion
        'nombre_prenda',
        'descripcion',
        'de_bodega',
        'prenda_id',
        'observaciones',
        //  REMOVIDOS: color_id, tela_id, tipo_manga_id, tipo_broche_boton_id
        //  Estos van en prenda_pedido_variantes, no en prendas_pedido
        // 'numero_pedido', //  COMENTADO [16/01/2026]: Se usa pedido_produccion_id en su lugar
        // 'cantidad_talla' - ELIMINADO: Las tallas se guardan en prenda_pedido_tallas (relacional)
        // 'genero' - ELIMINADO: Se obtiene desde prenda_pedido_tallas
    ];

    protected $casts = [
        'de_bodega' => 'boolean',
        'tiene_bolsillos' => 'boolean',
        'tiene_reflectivo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'color',
        'tela',
        'tipo_manga',
        'tipo_broche',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    /**
     * Relación: Una prenda pertenece a un pedido de producción
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pedidoProduccion(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Alias para pedidoProduccion()
     * Para compatibilidad con código que usa ->pedido()
     */
    public function pedido(): BelongsTo
    {
        return $this->pedidoProduccion();
    }

    /**
     * Relación: Una prenda tiene muchas variantes
     * 
     * Una variante es una combinación específica de:
     * - Talla
     * - Color
     * - Tela
     * - Tipo de manga
     * - Tipo de broche/botón
     * - Bolsillos
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(PrendaVariantePed::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda tiene muchas fotos (de referencia)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda tiene muchas combinaciones de color-tela
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function coloresTelas(): HasMany
    {
        return $this->hasMany(PrendaPedidoColorTela::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda tiene muchas fotos de telas (a través de coloresTelas)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function fotosTelas(): HasManyThrough
    {
        return $this->hasManyThrough(
            PrendaFotoTelaPedido::class,
            PrendaPedidoColorTela::class,
            'prenda_pedido_id',
            'prenda_pedido_colores_telas_id'
        );
    }

    /**
     * Relación: Una prenda tiene muchos procesos especiales
     * 
     * (bordado, estampado, DTF, sublimado, etc.)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(PedidosProcesosPrendaDetalle::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda tiene muchos procesos de producción (costura, reflectivo, etc.)
     * 
     * Accede a procesos_prenda a través de pedidos_produccion
     * PrendaPedido → PedidoProduccion → ProcesoPrenda
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function procesosPrenda(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProcesoPrenda::class,           // Modelo destino
            PedidoProduccion::class,        // Modelo intermedio
            'id',                            // FK en PedidoProduccion (local key)
            'numero_pedido',                 // FK en ProcesoPrenda
            'pedido_produccion_id',          // Local key en PrendaPedido
            'numero_pedido'                  // Local key en PedidoProduccion
        );
    }

    /**
     * Relación: Una prenda tiene muchas tallas (nueva tabla relacional)
     * 
     * Reemplaza el JSON cantidad_talla con datos normalizados
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(PrendaPedidoTalla::class, 'prenda_pedido_id');
    }

    /**
     * Alias para compatibilidad con getFilasDespacho()
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prendaPedidoTallas(): HasMany
    {
        return $this->tallas();
    }

    /**
     * Relación: Una prenda tiene un registro de entrega
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entrega()
    {
        return $this->hasOne(PrendaEntrega::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una prenda tiene muchas novedades de recibo
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function novedadesRecibo()
    {
        return $this->hasMany(PrendaPedidoNovedadRecibo::class, 'prenda_pedido_id');
    }

    /**
     * Obtener el primer color de esta prenda (para compatibilidad con vistas antiguas)
     * Retorna el color del primer registro en prenda_pedido_colores_telas
     */
    public function getColorAttribute()
    {
        $colorTela = $this->coloresTelas()->first();
        return $colorTela ? $colorTela->color : null;
    }

    /**
     * Obtener la primera tela de esta prenda (para compatibilidad con vistas antiguas)
     * Retorna la tela del primer registro en prenda_pedido_colores_telas
     */
    public function getTelaAttribute()
    {
        $colorTela = $this->coloresTelas()->first();
        return $colorTela ? $colorTela->tela : null;
    }

    /**
     * Obtener el primer tipo de manga de esta prenda (para compatibilidad con vistas antiguas)
     * Retorna el tipo_manga del primer registro en prenda_pedido_variantes
     */
    public function getTipoMangaAttribute()
    {
        $variante = $this->variantes()->first();
        return $variante ? $variante->tipo_manga : null;
    }

    /**
     * Obtener el primer tipo de broche de esta prenda (para compatibilidad con vistas antiguas)
     * Retorna el tipo_broche_boton del primer registro en prenda_pedido_variantes
     */
    public function getTipoBrocheAttribute()
    {
        $variante = $this->variantes()->first();
        return $variante ? $variante->tipo_broche_boton : null;
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope: Filtrar por pedido de producción
     * 
     * @param $query
     * @param $pedidoId
     * @return mixed
     */
    public function scopePorPedido($query, $pedidoId)
    {
        return $query->where('pedido_produccion_id', $pedidoId);
    }

    /**
     * Scope: Filtrar por origen (bodega o nuevas)
     * 
     * @param $query
     * @param $deBodega
     * @return mixed
     */
    public function scopePorOrigen($query, $deBodega = false)
    {
        return $query->where('de_bodega', $deBodega);
    }

    /**
     * Scope: Filtrar prendas por género
     * ACTUALIZADO: Usa tabla relacional prenda_pedido_tallas
     * 
     * @param $query
     * @param $genero
     * @return mixed
     */
    public function scopePorGenero($query, $genero)
    {
        return $query->whereHas('tallas', function($q) use ($genero) {
            $q->where('genero', strtoupper($genero));
        });
    }

    // ============================================================
    // ACCESORESY MUTADORES
    // ============================================================

    /**
     * Accessor: Obtener la cantidad total de prendas (suma de todas las tallas)
     * 
     * @return int
     */
    public function getCantidadTotalAttribute(): int
    {
        // Usar la relación de tallas (prenda_pedido_tallas) como fuente de verdad
        if ($this->relationLoaded('tallas') && $this->tallas) {
            $total = 0;
            foreach ($this->tallas as $tallaRecord) {
                $total += $tallaRecord->cantidad;
            }
            return $total;
        }
        
        // Fallback: Si tallas no está cargada, usar suma desde la BD
        return \App\Models\PrendaPedidoTalla::where('prenda_pedido_id', $this->id)->sum('cantidad');
    }

    /**
     * Accessor: Obtener la descripción legible de variantes
     * 
     * Ej: "Dama: Colores rojo, azul; Telas 100% Algodón"
     * 
     * @return string
     */
    public function getDescripcionVariantesAttribute(): string
    {
        if ($this->variantes->isEmpty()) {
            return 'Sin variantes';
        }

        $colores = $this->variantes
            ->pluck('color.nombre')
            ->filter()
            ->unique()
            ->implode(', ');

        $telas = $this->variantes
            ->pluck('tela.nombre')
            ->filter()
            ->unique()
            ->implode(', ');

        $mangas = $this->variantes
            ->pluck('tipoManga.nombre')
            ->filter()
            ->unique()
            ->implode(', ');

        $partes = array_filter([
            $colores ? "Colores: {$colores}" : null,
            $telas ? "Telas: {$telas}" : null,
            $mangas ? "Mangas: {$mangas}" : null,
        ]);

        return implode('; ', $partes) ?: 'Sin detalles';
    }

    // ============================================================
    // MÉTODOS ÚTILES
    // ============================================================

    /**
     * Obtener todas las tallas disponibles para esta prenda
     * 
     * @return \Illuminate\Support\Collection
     */
    public function obtenerTallasDisponibles()
    {
        return $this->variantes()
            ->pluck('talla')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Obtener todas las cantidades agrupadas por talla
     * 
     * @return array
     */
    public function obtenerCantidadesPorTalla(): array
    {
        return $this->variantes()
            ->groupBy('talla')
            ->map(function ($grupo) {
                return $grupo->sum('cantidad');
            })
            ->toArray();
    }

    /**
     * Obtener información detallada de esta prenda para reporte/PDF
     * 
     * @return array
     */
    public function obtenerInfoDetallada(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre_prenda,
            'descripcion' => $this->descripcion,
            'genero' => $this->genero,
            'de_bodega' => $this->de_bodega,
            'cantidad_total' => $this->cantidad_total,
            'variantes' => $this->variantes->map(function ($variante) {
                return [
                    'id' => $variante->id,
                    'talla' => $variante->talla,
                    'cantidad' => $variante->cantidad,
                    'color' => $variante->color?->nombre,
                    'tela' => $variante->tela?->nombre,
                    'manga' => $variante->tipoManga?->nombre,
                    'broche_boton' => $variante->tipoBrocheBoton?->nombre,
                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                    'observaciones' => [
                        'manga' => $variante->manga_obs,
                        'broche_boton' => $variante->broche_boton_obs,
                        'bolsillos' => $variante->bolsillos_obs,
                    ],
                ];
            })->toArray(),
        ];
    }

    // ============================================================
    // EVENTOS DEL MODELO
    // ============================================================

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
            
            // Agregar tallas (NUEVO)
            if ($this->relationLoaded('tallas') && $this->tallas && $this->tallas->count() > 0) {
                $tallasInfo = [];
                
                // Agrupar tallas por género
                $tallasPorGenero = $this->tallas->groupBy('genero');
                
                foreach ($tallasPorGenero as $genero => $tallaRecords) {
                    $tallaTexto = [];
                    foreach ($tallaRecords as $tallaRecord) {
                        $tallaTexto[] = "{$tallaRecord->talla} ({$tallaRecord->cantidad})";
                    }
                    $tallasInfo[] = "{$genero}: " . implode(", ", $tallaTexto);
                }
                
                if (!empty($tallasInfo)) {
                    $lineas[] = "<br><strong>Tallas:</strong> " . implode(" | ", $tallasInfo);
                }
            }
            
            return implode("", $lineas);
        } catch (\Exception $e) {
            \Log::error('Error generando descripción para PrendaPedido', [
                'prenda_pedido_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback a descripción simple
            return "DESCRIPCION: " . ($this->descripcion ? trim($this->descripcion) : 'Sin descripción');
        }
    }

    // ============================================================
    // EVENTOS DEL MODELO
    // ============================================================

    /**
     * Ejecutar acciones cuando se crea o actualiza la prenda
     */
    protected static function boot()
    {
        parent::boot();

        // Al eliminar una prenda, las variantes se eliminan automáticamente (ON DELETE CASCADE)
        // pero podemos hacer validaciones adicionales si es necesario
        static::deleting(function ($prenda) {
            // Log o acciones adicionales
            \Log::info("🗑️ Prenda eliminada: {$prenda->nombre_prenda} (ID: {$prenda->id})");
        });
    }
}
