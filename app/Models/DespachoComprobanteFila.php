<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespachoComprobanteFila extends Model
{
    protected $table = 'despacho_comprobante_filas';

    protected $fillable = [
        'despacho_comprobante_id',
        'orden',
        'cantidad',
        'articulo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'cantidad' => 'integer',
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(DespachoComprobante::class, 'despacho_comprobante_id');
    }
}
