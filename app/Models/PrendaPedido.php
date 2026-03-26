<?php

namespace App\Models;

use App\Models\Concerns\HasPrendaPedidoCompatibilityAttributes;
use App\Models\Concerns\HasPrendaPedidoRelations;
use App\Models\Concerns\HasPrendaPedidoScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PrendaPedido Model
 *
 * Modelo normalizado para gestionar prendas en pedidos de produccion.
 *
 * Cambios importantes:
 * - Usa `pedido_produccion_id` como FK (NO numero_pedido)
 * - Almacena solo datos basicos de la prenda
 * - Variantes (color, tela, manga, broche, bolsillos) estan en tabla hija prenda_variantes
 * - NO maneja reflectivo
 * - Escalable para ERP de produccion textil
 *
 * @property int $id
 * @property int $pedido_produccion_id
 * @property string $nombre_prenda
 * @property string|null $descripcion
 * @property bool $de_bodega
 * @property int|null $prenda_id
 * @property string|null $observaciones
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string $color
 * @property string $tela
 * @property string $tipo_manga
 * @property string $tipo_broche
 */
class PrendaPedido extends Model
{
    use SoftDeletes;
    use HasPrendaPedidoRelations;
    use HasPrendaPedidoCompatibilityAttributes;
    use HasPrendaPedidoScopes;

    protected $table = 'prendas_pedido';

    protected $fillable = [
        'pedido_produccion_id',
        'nombre_prenda',
        'descripcion',
        'de_bodega',
        'prenda_id',
        'observaciones',
    ];

    protected $casts = [
        'de_bodega' => 'boolean',
        'tiene_bolsillos' => 'boolean',
        'tiene_reflectivo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'color',
        'tela',
        'tipo_manga',
        'tipo_broche',
    ];
}
