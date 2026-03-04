<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReciboVistoInsumo extends Model
{
    protected $table = 'recibos_vistos_insumos';

    public $timestamps = false;

    protected $fillable = [
        'consecutivo_recibo_id',
        'user_id',
    ];

    /**
     * Relación: pertenece a un recibo
     */
    public function recibo(): BelongsTo
    {
        return $this->belongsTo(ConsecutivoReciboPedido::class, 'consecutivo_recibo_id');
    }

    /**
     * Relación: pertenece a un usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
