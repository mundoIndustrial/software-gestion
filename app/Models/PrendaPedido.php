<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrendaPedido extends Model
{
    use SoftDeletes;

    protected $table = 'prendas_pedido';

    protected $fillable = [
        'pedido_produccion_id',
        'nombre_prenda',
        'cantidad',
        'descripcion',
        'cantidad_talla',
    ];

    /**
     * Relación: Una prenda pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Una prenda tiene muchos procesos
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(ProcesoPrenda::class);
    }
}
