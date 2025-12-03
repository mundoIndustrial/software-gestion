<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrendaCotizacionFriendly extends Model
{
    protected $table = 'prendas_cotizaciones';

    protected $fillable = [
        'cotizacion_id',
        'nombre_producto',
        'genero',
        'es_jean_pantalon',
        'tipo_jean_pantalon',
        'descripcion',
        'tallas',
        'fotos',
        'telas',
        'estado',
        'notas_tallas'
    ];

    protected $casts = [
        'tallas' => 'array',
        'fotos' => 'array',
        'telas' => 'array',
        'es_jean_pantalon' => 'boolean'
    ];

    /**
     * Accessor para convertir fotos a URLs públicas
     */
    public function getFotosAttribute($value)
    {
        if (!$value) {
            return [];
        }
        
        $fotos = is_array($value) ? $value : json_decode($value, true);
        
        return array_map(function($foto) {
            // Si ya es una URL completa, devolverla tal como está
            if (filter_var($foto, FILTER_VALIDATE_URL)) {
                return $foto;
            }
            
            // Si es una ruta relativa, convertirla a URL pública
            if (is_string($foto) && !empty($foto)) {
                return asset($foto);
            }
            
            return null;
        }, $fotos ?? []);
    }

    /**
     * Accessor para convertir telas a URLs públicas
     */
    public function getTelasAttribute($value)
    {
        if (!$value) {
            return [];
        }
        
        $telas = is_array($value) ? $value : json_decode($value, true);
        
        return array_map(function($tela) {
            // Si ya es una URL completa, devolverla tal como está
            if (filter_var($tela, FILTER_VALIDATE_URL)) {
                return $tela;
            }
            
            // Si es una ruta relativa, convertirla a URL pública
            if (is_string($tela) && !empty($tela)) {
                return asset($tela);
            }
            
            return null;
        }, $telas ?? []);
    }

    /**
     * Relación con Cotizacion
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación con VariantePrenda
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(VariantePrenda::class, 'prenda_cotizacion_id');
    }
}
