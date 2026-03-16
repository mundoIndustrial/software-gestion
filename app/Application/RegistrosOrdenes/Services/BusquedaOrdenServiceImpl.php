<?php

namespace App\Application\RegistrosOrdenes\Services;

use App\Application\RegistrosOrdenes\Contracts\BusquedaOrdenService;

/**
 * BusquedaOrdenServiceImpl
 * 
 * Implementación del servicio de búsqueda
 */
class BusquedaOrdenServiceImpl implements BusquedaOrdenService
{
    public function aplicar(&$query, $termino): void
    {
        if (empty($termino)) {
            return;
        }

        $query->where(function ($q) use ($termino) {
            $q->where('numero_pedido', 'like', "%{$termino}%")
                ->orWhere('cliente', 'like', "%{$termino}%")
                ->orWhere('referencia', 'like', "%{$termino}%")
                ->orWhere('novedades', 'like', "%{$termino}%");
        });
    }
}
