<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PrendaPedidoTalla Model
 * 
 * @property int $id
 * @property int $prenda_pedido_id
 * @property string $genero
 * @property string|null $talla
 * @property string|null $tipo_talla
 * @property int|null $cantidad
 * @property bool $es_sobremedida
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PrendaPedidoTalla extends Model
{
    protected $table = 'prenda_pedido_tallas';

    protected $fillable = [
        'prenda_pedido_id',
        'genero',
        'talla',
        'tipo_talla',
        'cantidad',
        'es_sobremedida',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function setGeneroAttribute($value): void
    {
        $normalized = strtoupper((string) $value);
        $normalized = match ($normalized) {
            'HOMBRE', 'CABALLERO' => 'CABALLERO',
            'MUJER', 'DAMA' => 'DAMA',
            default => 'UNISEX',
        };

        $this->attributes['genero'] = $normalized;
    }

    /**
     * Relación con PrendaPedido
     */
    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     *  RELACIÓN NUEVA: Colores asociados a esta talla
     * Esto reemplaza el campo JSON 'colores' con una tabla relacional
     */
    public function coloresAsignados(): HasMany
    {
        return $this->hasMany(PrendaPedidoTallaColor::class, 'prenda_pedido_talla_id');
    }

    /**
     * Obtener colores en formato array (desde tabla relacional)
     */
    public function obtenerColores()
    {
        return $this->coloresAsignados()
            ->select(['color_nombre', 'tela_nombre', 'cantidad'])
            ->get()
            ->map(function ($color) {
                return [
                    'color' => $color->color_nombre,
                    'tela' => $color->tela_nombre,
                    'cantidad' => $color->cantidad,
                ];
            })
            ->toArray();
    }

    /**
     * Obtener la cantidad total considerando si hay colores desglosados
     * 
     * Si la talla tiene colores asignados (prenda_pedido_talla_colores),
     * suma las cantidades de cada color. Si no, retorna la cantidad directa.
     * 
     * @return int
     */
    public function obtenerCantidadTotal(): int
    {
        // Verificar si hay colores desglosados para esta talla
        $coloresCount = $this->coloresAsignados()->count();
        
        if ($coloresCount > 0) {
            // Sumar cantidades desde tabla de colores
            return (int) $this->coloresAsignados()->sum('cantidad');
        }
        
        // Usar cantidad directa de la talla
        return (int) ($this->cantidad ?? 0);
    }
}
