<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * @deprecated La tabla logo_pedidos ha sido eliminada de la base de datos (22/01/2026)
 * Este modelo se mantiene solo por compatibilidad.
 */
class LogoPedido extends Model
{
    protected $table = 'logo_pedidos';

    protected $fillable = [
        'pedido_id',
        'logo_cotizacion_id',
        'numero_pedido',
        'numero_pedido_cost',
        'descripcion',
        'cantidad',
        'tecnicas',
        'observaciones_tecnicas',
        'secciones',  //  Cambiar de 'ubicaciones' a 'secciones' (nombre real de columna)
        'cliente',
        'asesora',
        'forma_de_pago',
        'encargado_orden',
        'fecha_de_creacion_de_orden',
        'estado',
        'area',
        'numero_cotizacion',
        'cotizacion_id',
        'prendas',
        'observaciones',
    ];

    protected $casts = [
        'tecnicas' => 'json',
        'secciones' => 'json',  //  Cambiar de 'ubicaciones' a 'secciones'
    ];

    /**
     * Relación: Un LogoPedido pertenece a un PedidoProduccion
     */
    public function pedidoProduccion(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id');
    }

    /**
     * Relación: Un LogoPedido pertenece a una LogoCotizacion (puede ser nulo)
     */
    public function logoCotizacion(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacion::class, 'logo_cotizacion_id');
    }

    /**
     * Relación: Un LogoPedido tiene muchas imágenes
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(LogoPedidoImagen::class, 'logo_pedido_id')
                    ->orderBy('orden');
    }

    /**
     * Relación: Un LogoPedido tiene muchos procesos/áreas por las que pasa
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(ProcesosPedidosLogo::class, 'logo_pedido_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Obtener el área actual del pedido
     */
    public function getAreaActualAttribute()
    {
        return ProcesosPedidosLogo::obtenerAreaActual($this->id);
    }

    /**
     * @deprecated Método eliminado por problemas de concurrencia
     * Usar tabla numero_secuencias con lockForUpdate() en su lugar
     */
    public static function generarNumeroPedido(): string
    {
        throw new \Exception('Método obsoleto. Usar CarteraPedidosController::generarSiguienteNumeroPedido()');
    }

    /**
     * Retorna las técnicas como array
     */
    public function getTecnicasAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Retorna las ubicaciones como array
     */
    public function getUbicacionesAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }
}
