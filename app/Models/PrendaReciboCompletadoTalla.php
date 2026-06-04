<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaReciboCompletadoTalla extends Model
{
    use HasFactory;

    protected $table = 'prenda_recibo_completado_tallas';

    protected $fillable = [
        'prenda_recibo_completado_id',
        'talla',
        'cantidad',
        'genero',
        'color_nombre',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function completado(): BelongsTo
    {
        return $this->belongsTo(PrendaReciboCompletado::class, 'prenda_recibo_completado_id');
    }
}
