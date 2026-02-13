<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ColorPrenda Model
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $codigo
 * @property bool $activo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ColorPrenda extends Model
{
    protected $table = 'colores_prenda';
    protected $fillable = ['nombre', 'codigo', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
