<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogoObservacionPrendaCot extends Model
{
    protected $table = 'logo_observacion_prenda_cot';

    protected $fillable = [
        'cotizacion_id',
        'prenda_cot_id',
        'observacion',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function prendaCot(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
