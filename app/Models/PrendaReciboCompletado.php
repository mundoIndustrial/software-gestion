<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrendaReciboCompletado extends Model
{
    use HasFactory;

    protected $table = 'prenda_recibo_completado';

    public $timestamps = false; // La tabla no tiene created_at ni updated_at

    protected $fillable = [
        'id_recibo',
        'id_parcial',
        'numero_recibo',
        'area',
        'nombre_operario',
        'fecha_completado',
    ];

    protected $casts = [
        'fecha_completado' => 'datetime',
        'id_recibo' => 'integer',
        'id_parcial' => 'integer',
        'numero_recibo' => 'decimal:2',
    ];
}
