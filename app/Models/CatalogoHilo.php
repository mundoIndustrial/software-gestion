<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogoHilo extends Model
{
    use HasFactory;

    protected $table = 'catalogo_hilos';

    protected $fillable = [
        'referencia',
        'nombre',
        'codigo_hex',
        'imagen',
        'marca',
        'composicion',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
