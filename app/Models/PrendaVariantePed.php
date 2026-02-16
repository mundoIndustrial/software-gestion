<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PrendaVariantePed Model
 * 
 * Representa una variante de una prenda en un pedido
 * Guarda: tipo_manga_id, tipo_broche_boton_id, tiene_bolsillos, tiene_reflectivo y observaciones
 * Los colores y telas se guardan en PrendaPedidoColorTela
 * 
 * @property int $id
 * @property int $prenda_pedido_id
 * @property int|null $tipo_manga_id
 * @property int|null $tipo_broche_boton_id
 * @property string|null $manga_obs
 * @property string|null $broche_boton_obs
 * @property bool $tiene_bolsillos
 * @property string|null $bolsillos_obs
 * @property array $telas_multiples
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PrendaVariantePed extends Model
{
    protected $table = 'prenda_pedido_variantes';
    protected $guarded = [];
    protected $casts = [
        'telas_multiples' => 'json',
    ];

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Tiene muchos colores y telas
     */
    public function coloresTelas(): HasMany
    {
        return $this->hasMany(PrendaPedidoColorTela::class, 'prenda_pedido_id', 'prenda_pedido_id');
    }

    /**
     * Relación: Tipo de manga
     */
    public function tipoManga(): BelongsTo
    {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }

    /**
     * Relación: Tipo de broche
     */
    public function tipoBroche(): BelongsTo
    {
        // Usa la clase TipoBroche del archivo TipoBrocheBoton.php
        return $this->belongsTo('App\Models\TipoBrocheBoton', 'tipo_broche_boton_id');
    }

    /**
     * Alias de tipoBroche() para compatibilidad con eager loading
     * Algunos servicios usan 'tipoBrocheBoton' como nombre de relación
     */
    public function tipoBrocheBoton(): BelongsTo
    {
        return $this->belongsTo('App\Models\TipoBrocheBoton', 'tipo_broche_boton_id');
    }
}
