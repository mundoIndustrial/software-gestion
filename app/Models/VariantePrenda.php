<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasLegibleAtributosPrenda;

class VariantePrenda extends Model
{
    use HasLegibleAtributosPrenda;
    protected $table = 'variantes_prenda';

    protected $fillable = [
        'prenda_cotizacion_id',
        'tipo_prenda_id',
        'genero_id',
        'tipo_manga_id',
        'tipo_broche_id',
        'tiene_bolsillos',
        'tiene_reflectivo',
        'cantidad_talla',
        'descripcion_adicional'
    ];

    protected $casts = [
        'cantidad_talla' => 'array',
        'tiene_bolsillos' => 'boolean',
        'tiene_reflectivo' => 'boolean'
    ];

    /**
     * Relaciones
     */
    public function prendaCotizacion(): BelongsTo
    {
        return $this->belongsTo(PrendaCotizacionFriendly::class, 'prenda_cotizacion_id');
    }

    public function tipoPrenda(): BelongsTo
    {
        return $this->belongsTo(TipoPrenda::class);
    }

    /**
     * Relación con telas de la variante
     */
    public function telas(): HasMany
    {
        return $this->hasMany(PrendaTela::class, 'variante_prenda_id');
    }

    public function genero(): BelongsTo
    {
        return $this->belongsTo(GeneroPrenda::class, 'genero_id');
    }

    public function tipoManga(): BelongsTo
    {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }

    public function tipoBroche(): BelongsTo
    {
        return $this->belongsTo(TipoBrocheBoton::class, 'tipo_broche_id');
    }
}
