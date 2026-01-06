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
        'descripcion',
        'imagenes',
        'tecnicas',
        'observaciones_tecnicas',
        'secciones',
        'observaciones_generales',
        'tipo_venta'
    ];

    protected $casts = [
        'imagenes' => 'array',
        'tecnicas' => 'array',
        'secciones' => 'array',
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
     * Relación NUEVA: Un logo puede tener múltiples técnicas (bordado, estampado, etc)
     */
    public function tecnicas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnica::class)
            ->orderBy('orden');
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
