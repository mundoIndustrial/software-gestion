<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogoTela extends Model
{
    use HasFactory;

    protected $table = 'catalogo_telas';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'imagen',
        'composicion',
        'peso',
        'ancho',
        'colores_disponibles',
        'activo',
    ];

    protected $casts = [
        'colores_disponibles' => 'array',
        'peso' => 'decimal:2',
        'ancho' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
