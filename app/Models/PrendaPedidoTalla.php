<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    /**
     * Relación con PrendaPedido
     */
    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * ✅ RELACIÓN NUEVA: Colores asociados a esta talla
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
}
