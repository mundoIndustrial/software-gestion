<?php

namespace App\Models\Concerns;

trait HasPrendaPedidoScopes
{
    public function scopePorPedido($query, $pedidoId)
    {
        return $query->where('pedido_produccion_id', $pedidoId);
    }

    public function scopePorOrigen($query, $deBodega = false)
    {
        return $query->where('de_bodega', $deBodega);
    }

    public function scopePorGenero($query, $genero)
    {
        return $query->whereHas('tallas', function ($q) use ($genero) {
            $q->where('genero', strtoupper($genero));
        });
    }
}
