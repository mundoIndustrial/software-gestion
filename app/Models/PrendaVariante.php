<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaVariante Model
 * 
 * Modelo para gestionar variantes de prendas en pedidos de producción.
 * 
 * Una variante es una combinación específica de:
 * - Talla
 * - Cantidad para esa talla
 * - Color
 * - Tela
 * - Tipo de manga
 * - Tipo de broche/botón
 * - Bolsillos
 * - Observaciones específicas
 * 
 * Ejemplo:
 * Una prenda "CAMISA POLO" puede tener variantes:
 * - M, 50 unidades, Rojo, Algodón 100%, Manga Corta, Botones, Sin Bolsillos
 * - L, 30 unidades, Azul, Algodón 100%, Manga Corta, Botones, Con Bolsillo Pecho
 * - XL, 20 unidades, Verde, Tela Mixta, Manga Larga, Broche, Sin Bolsillos
 */
class PrendaVariante extends Model
{
    protected $table = 'prenda_pedido_variantes';

    protected $fillable = [
        'prenda_pedido_id',
        'talla',
        'cantidad',
        'color_id',
        'tela_id',
        'tipo_manga_id',
        'tipo_broche_boton_id',
        'manga_obs',
        'broche_boton_obs',
        'tiene_bolsillos',
        'bolsillos_obs',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'tiene_bolsillos' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    /**
     * Relación: Una variante pertenece a una prenda
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Una variante tiene un color (catálogo)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(ColorPrenda::class, 'color_id');
    }

    /**
     * Relación: Una variante tiene una tela (catálogo)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tela(): BelongsTo
    {
        return $this->belongsTo(TelaPrenda::class, 'tela_id');
    }

    /**
     * Relación: Una variante tiene un tipo de manga (catálogo)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tipoManga(): BelongsTo
    {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }

    /**
     * Relación: Una variante tiene un tipo de broche/botón (catálogo)
     * 
     * ACTUALIZACIÓN [16/01/2026]:
     * - Campo FK: tipo_broche_boton_id (antes tipo_broche_id)
     * - Tabla: tipos_broche_boton (antes tipos_broche)
     * - Modelo: Sigue siendo TipoBroche por compatibilidad
     * 
     * Nota: El nombre es "tipo_broche_boton" porque puede ser broche O botón
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tipoBrocheBoton(): BelongsTo
    {
        return $this->belongsTo(TipoBroche::class, 'tipo_broche_boton_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope: Filtrar variantes por talla
     * 
     * @param $query
     * @param $talla
     * @return mixed
     */
    public function scopePorTalla($query, $talla)
    {
        return $query->where('talla', $talla);
    }

    /**
     * Scope: Filtrar variantes por color
     * 
     * @param $query
     * @param $colorId
     * @return mixed
     */
    public function scopePorColor($query, $colorId)
    {
        return $query->where('color_id', $colorId);
    }

    /**
     * Scope: Filtrar variantes por tela
     * 
     * @param $query
     * @param $telaId
     * @return mixed
     */
    public function scopePorTela($query, $telaId)
    {
        return $query->where('tela_id', $telaId);
    }

    /**
     * Scope: Filtrar variantes que tienen bolsillos
     * 
     * @param $query
     * @return mixed
     */
    public function scopeConBolsillos($query)
    {
        return $query->where('tiene_bolsillos', true);
    }

    // ============================================================
    // ACCESORESY MUTADORES
    // ============================================================

    /**
     * Accessor: Obtener descripción completa de la variante
     * 
     * Ej: "M - Rojo, Algodón, Manga Corta, 50 unidades"
     * 
     * @return string
     */
    public function getDescripcionComletaAttribute(): string
    {
        $partes = [$this->talla];

        if ($this->color) {
            $partes[] = $this->color->nombre;
        }

        if ($this->tela) {
            $partes[] = $this->tela->nombre;
        }

        if ($this->tipoManga) {
            $partes[] = $this->tipoManga->nombre;
        }

        $partes[] = "{$this->cantidad} unidades";

        return implode(', ', $partes);
    }

    // ============================================================
    // EVENTOS DEL MODELO
    // ============================================================

    protected static function boot()
    {
        parent::boot();

        /**
         * Cuando se crea o actualiza una variante,
         * actualizar la cantidad total de la prenda padre
         */
        static::saved(function ($variante) {
            if ($variante->prendaPedido) {
                // Recalcular cantidad total de la prenda
                // (No es necesario si usamos un accessor en PrendaPedido)
                \Log::info("📦 Variante guardada: Talla {$variante->talla}, Cantidad {$variante->cantidad}");
            }
        });

        /**
         * Cuando se elimina una variante
         */
        static::deleting(function ($variante) {
            \Log::info("🗑️ Variante eliminada: Talla {$variante->talla} de Prenda ID {$variante->prenda_pedido_id}");
        });
    }
}
