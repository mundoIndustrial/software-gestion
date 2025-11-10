<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogoColor extends Model
{
    use HasFactory;

    protected $table = 'catalogo_colores';

    protected $fillable = [
        'nombre',
        'codigo_hex',
        'codigo_pantone',
        'imagen',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
