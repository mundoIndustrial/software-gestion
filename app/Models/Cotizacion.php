<?php

namespace App\Models;

use App\Traits\HasLegibleEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizacion extends Model
{
    use SoftDeletes, HasLegibleEstado;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'asesor_id',
        'cliente_id',
        'numero_cotizacion',
        'tipo_cotizacion_id',
        'tipo_venta',
        'fecha_inicio',
        'fecha_envio',
        'fecha_enviado_a_aprobador',
        'es_borrador',
        'estado',
        'especificaciones',
        'imagenes',
        'tecnicas',
        'observaciones_tecnicas',
        'ubicaciones',
        'observaciones_generales'
    ];

    protected $casts = [
        'es_borrador' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_envio' => 'datetime',
        'fecha_enviado_a_aprobador' => 'datetime',
        'especificaciones' => 'array',
        'imagenes' => 'array',
        'tecnicas' => 'array',
        'observaciones_tecnicas' => 'string',
        'ubicaciones' => 'array',
        'observaciones_generales' => 'array',
        'estado' => 'string',
        'tipo_cotizacion' => 'string'
    ];

    /**
     * Relación: Una cotización pertenece a un asesor (usuario)
     */
    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Alias para compatibilidad hacia atrás
     */
    public function usuario(): BelongsTo
    {
        return $this->asesor();
    }

    /**
     * Relación: Una cotización pertenece a un cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación: Una cotización pertenece a un tipo de cotización
     */
    public function tipoCotizacion(): BelongsTo
    {
        return $this->belongsTo(TipoCotizacion::class, 'tipo_cotizacion_id');
    }

    /**
     * Relación: Una cotización puede tener múltiples pedidos de producción
     */
    public function pedidosProduccion(): HasMany
    {
        return $this->hasMany(PedidoProduccion::class);
    }

    /**
     * Relación con prendas normalizadas (prendas_cot)
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(PrendaCot::class, 'cotizacion_id');
    }

    /**
     * Relación con prendas de cotización (friendly)
     */
    public function prendasCotizaciones(): HasMany
    {
        return $this->hasMany(PrendaCotizacionFriendly::class);
    }

    /**
     * Relación con prenda de cotización (Prenda)
     */
    public function prendaCotizacion()
    {
        return $this->hasOne(PrendaCotizacion::class);
    }

    /**
     * Relación con logo/LOGO de cotización
     */
    public function logoCotizacion()
    {
        return $this->hasOne(LogoCotizacion::class);
    }

    /**
     * Relación con reflectivo de cotización
     */
    public function reflectivo()
    {
        return $this->hasOne(ReflectivoCotizacion::class, 'cotizacion_id');
    }

    /**
     * Relación con fotos de logo (logo_fotos_cot)
     */
    public function logoFotos(): HasMany
    {
        return $this->hasMany(LogoFotoCot::class, 'cotizacion_id');
    }

    /**
     * Relación con historial de cambios (DEPRECATED)
     */
    public function historial()
    {
        return $this->hasMany(HistorialCotizacion::class);
    }

    /**
     * Relación con historial de cambios de estado
     */
    public function historialCambios(): HasMany
    {
        return $this->hasMany(HistorialCambiosCotizacion::class, 'cotizacion_id');
    }

    /**
     * Obtener el código del tipo de cotización
     * Prioridad: tipo_cotizacion_id > detección automática
     */
    public function obtenerTipoCotizacion(): string
    {
        // Si tiene tipo_cotizacion_id, usarlo
        if ($this->tipo_cotizacion_id && $this->tipoCotizacion) {
            return $this->tipoCotizacion->codigo;
        }

        // Si no, detectar basado en contenido
        $tienePrendas = $this->prendasCotizaciones()->exists() || $this->prendaCotizacion()->exists();
        $tieneLogo = $this->logoCotizacion()->exists();

        if ($tienePrendas && $tieneLogo) {
            return 'PB'; // Prenda/Bordado
        } elseif ($tienePrendas) {
            return 'P'; // Prenda
        } elseif ($tieneLogo) {
            return 'B'; // Bordado (Logo)
        }

        return null; // Por defecto
    }

    /**
     * Convertir a array - Retorna solo el nombre del cliente, no el objeto completo
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // Si existe la relación cliente cargada, reemplazarla solo con el nombre
        if (isset($array['cliente']) && is_array($array['cliente'])) {
            $array['cliente'] = $array['cliente']['nombre'] ?? null;
        } elseif ($this->relationLoaded('cliente') && $this->cliente) {
            $array['cliente'] = $this->cliente->nombre;
        }

        return $array;
    }
}
