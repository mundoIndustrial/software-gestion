<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TipoManga Model
 * 
 * ACTUALIZACIÓN [16/01/2026]: Tabla tipos_manga con estructura normalizada
 * Tabla: tipos_manga (id, nombre, activo, created_at, updated_at)
 * 
 * Relacionada con: PrendaVariante.tipo_manga_id
 */
class TipoManga extends Model
{
    // Tabla tipos_manga
    protected $table = 'tipos_manga';
    
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
