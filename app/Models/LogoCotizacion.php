<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * LogoCotizacion Model
 * @property int $id
 * @property int $cotizacion_id
 * @property array $observaciones_generales
 * @property string $tipo_venta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class LogoCotizacion extends Model
{
    use HasFactory;

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
     * Relación: Información de Telas, Colores y Referencias de prendas
     */
    public function telasPrendas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTelasPrenda::class, 'logo_cotizacion_id');
    }

    /**
     * Relación: Todas las fotos de todas las prendas técnicas
     * Utiliza HasManyThrough para atravesar LogoCotizacionTecnicaPrenda
     */
    public function fotos(): HasManyThrough
    {
        return $this->hasManyThrough(
            LogoCotizacionTecnicaPrendaFoto::class,
            LogoCotizacionTecnicaPrenda::class,
            'logo_cotizacion_id', // FK en prendas técnicas
            'logo_cotizacion_tecnica_prenda_id', // FK en fotos
            'id', // PK de logo_cotizacion
            'id'  // PK de prendas técnicas
        );
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
