<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\PrendaPedido;

class ConsecutivoReciboPedido extends Model
{
    protected $table = 'consecutivos_recibos_pedidos';

    protected $fillable = [
        'pedido_produccion_id',
        'prenda_id',
        'tipo_recibo',
        'consecutivo_actual',
        'consecutivo_inicial',
        'activo',
        'estado',
        'area',
        'aprobado_insumos_en',
        'notas',
        'dia_de_entrega',
        'fecha_estimada_de_entrega',
    ];

    protected $casts = [
        'consecutivo_actual' => 'integer',
        'consecutivo_inicial' => 'integer',
        'activo' => 'boolean',
        'dia_de_entrega' => 'integer',
        'fecha_estimada_de_entrega' => 'datetime',
        'aprobado_insumos_en' => 'datetime',
    ];

    /**
     * Estados posibles del recibo
     */
    const ESTADO_PENDIENTE_INSUMOS = 'PENDIENTE_INSUMOS';
    const ESTADO_NO_INICIADO = 'No iniciado';
    const ESTADO_EN_EJECUCION = 'En Ejecución';

    /**
     * Relación: Un consecutivo pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un consecutivo pertenece a una prenda (opcional)
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_id');
    }

    /**
     * Relación: Usuarios que marcaron este recibo como visto
     */
    public function vistoPor()
    {
        return $this->belongsToMany(User::class, 'recibos_vistos_insumos', 'consecutivo_recibo_id', 'user_id');
    }
}
