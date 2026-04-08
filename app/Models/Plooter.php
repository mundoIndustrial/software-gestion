<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plooter extends Model
{
    protected $table = 'plooter';

    protected $fillable = [
        'consecutivo_recibo_pedido_id',
        'fecha_envio',
        'fecha_llegada',
        'notas',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_llegada' => 'datetime',
    ];

    /**
     * Relación: Un registro de plooter pertenece a un recibo
     */
    public function recibo()
    {
        return $this->belongsTo(ConsecutivosRecibosPedidos::class, 'consecutivo_recibo_pedido_id');
    }

    /**
     * Scope: Obtener registros con fecha de envío
     */
    public function scopeConFechaEnvio($query)
    {
        return $query->whereNotNull('fecha_envio');
    }

    /**
     * Scope: Obtener registros con fecha de llegada
     */
    public function scopeConFechaLlegada($query)
    {
        return $query->whereNotNull('fecha_llegada');
    }

    /**
     * Scope: Obtener registros pendientes de llegada
     */
    public function scopePendienteLlegada($query)
    {
        return $query->whereNotNull('fecha_envio')->whereNull('fecha_llegada');
    }
}
