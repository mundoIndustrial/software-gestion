<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class LogoPedido extends Model
{
    protected $table = 'logo_pedidos';

    protected $fillable = [
        'pedido_id',
        'logo_cotizacion_id',
        'numero_pedido',
        'descripcion',
        'cantidad',
        'tecnicas',
        'observaciones_tecnicas',
        'ubicaciones',
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
        'ubicaciones' => 'json',
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
     * Genera el siguiente número de pedido en la secuencia LOGO-00001
     */
    public static function generarNumeroPedido(): string
    {
        $ultimoPedido = self::whereRaw('numero_pedido LIKE "LOGO-%"')
                          ->orderByRaw('CAST(SUBSTR(numero_pedido, 6) AS UNSIGNED) DESC')
                          ->first();

        if (!$ultimoPedido) {
            $numero = 1;
        } else {
            $numeroActual = (int) substr($ultimoPedido->numero_pedido, 5);
            $numero = $numeroActual + 1;
        }

        return 'LOGO-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
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
