<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TelaPrenda Model
 * 
 * ACTUALIZACIÓN [16/01/2026]: Tabla telas_prenda con estructura normalizada
 * Tabla: telas_prenda (id, nombre, referencia, descripcion, activo, created_at, updated_at)
 * 
 * Cambios:
 * - Columna 'descripcion' para notas sobre la tela
 * - Columna 'referencia' para código interno o proveedor
 * - Timestamps automáticos (created_at, updated_at)
 * 
 * Relacionada con: PrendaVariante.tela_id
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $referencia
 * @property string|null $descripcion
 * @property bool $activo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TelaPrenda extends Model
{
    // Tabla telas_prenda
    protected $table = 'telas_prenda';
    
    protected $fillable = ['nombre', 'referencia', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
