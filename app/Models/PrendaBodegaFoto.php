<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaBodegaFoto extends Model
{
    protected $table = 'prenda_bodega_fotos';

    protected $appends = ['url'];

    protected $fillable = [
        'prenda_bodega_id',
        'ruta',
        'orden',
    ];

    public function prendaBodega(): BelongsTo
    {
        return $this->belongsTo(PrendaBodega::class, 'prenda_bodega_id');
    }

    public function getUrlAttribute(): string
    {
        $ruta = (string) ($this->ruta ?? '');
        if ($ruta === '') {
            return '';
        }

        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }

        while (str_starts_with($ruta, '/storage/')) {
            $ruta = ltrim($ruta, '/');
        }

        if (str_starts_with($ruta, '/')) {
            $ruta = ltrim($ruta, '/');
        }

        return $ruta;
    }
}
