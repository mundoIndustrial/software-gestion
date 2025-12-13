<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrendaTelaFotoCot extends Model
{
    use HasFactory;

    protected $table = 'prenda_tela_fotos_cot';

    protected $fillable = [
        'prenda_cot_id',
        'ruta_original',
        'ruta_webp',
        'ruta_miniatura',
        'orden',
        'ancho',
        'alto',
        'tamaño',
    ];

    protected $casts = [
        'prenda_cot_id' => 'integer',
        'orden' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'tamaño' => 'integer',
    ];

    /**
     * Relación: Una foto de tela pertenece a una prenda de cotización
     */
    public function prendaCot()
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
