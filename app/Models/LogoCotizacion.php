<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogoCotizacion extends Model
{
    protected $table = 'logo_cotizaciones';

    protected $fillable = [
        'cotizacion_id',
        'observaciones_generales',
        'tipo_venta'
    ];

    protected $casts = [
        'observaciones_generales' => 'array'
    ];

    /**
     * Accessor para compatibilidad: ubicaciones retorna secciones
     */
    public function getUbicacionesAttribute()
    {
        return $this->secciones ?? [];
    }

    /**
     * Relación con Cotizacion
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación: Un logo puede tener múltiples fotos (máximo 5)
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(LogoFotoCot::class, 'logo_cotizacion_id')->orderBy('orden');
    }

    /**
     * Relación: Acceso directo a prendas técnicas (sin pasar por técnicas)
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnicaPrenda::class, 'logo_cotizacion_id');
    }

    /**
     * Alias para prendas: técnicas prendas con todas sus relaciones
     */
    public function tecnicasPrendas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnicaPrenda::class, 'logo_cotizacion_id')
            ->with('prenda', 'tipoLogo');
    }

    /**
     * Relación ANTIGUA: Un logo puede tener múltiples técnicas (bordado, estampado, etc)
     * NOTA: Esta tabla no existe en la versión nueva. Se mantiene para compatibilidad.
     */
    public function tecnicas(): HasMany
    {
        // Retorna una colección vacía para compatibilidad
        return $this->hasMany(LogoCotizacionTecnica::class);
    }

    /**
     * Obtener todas las prendas de todas las técnicas
     */
    public function obtenerTodasLasPrendas()
    {
        return $this->tecnicas()
            ->with('prendas')
            ->get()
            ->flatMap(function($tecnica) {
                return $tecnica->prendas;
            });
    }

    /**
     * Obtener técnicas agrupadas por tipo
     */
    public function tecnicasAgrupadas()
    {
        return $this->tecnicas()
            ->with('tipo')
            ->get()
            ->groupBy('tipo.nombre');
    }
}
