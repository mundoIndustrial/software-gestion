<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LogoFotoPed Model
 * 
 * Representa una foto de logo en un pedido
 * Equivalente a LogoFotoCot pero para pedidos
 */
class LogoFotoPed extends Model
{
    use SoftDeletes;

    protected $table = 'logo_fotos_ped';
    protected $guarded = [];

    /**
     * Relación: Pertenece a un logo
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(LogoPed::class, 'logo_ped_id');
    }
}
