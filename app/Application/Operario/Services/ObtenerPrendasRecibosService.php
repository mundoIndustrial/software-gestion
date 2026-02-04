<?php

namespace App\Application\Operario\Services;

use App\Models\User;
use App\Models\PrendaPedido;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Support\Collection;

/**
 * Service: ObtenerPrendasRecibosService
 * 
 * Obtiene las prendas con sus recibos de costura para un operario
 */
class ObtenerPrendasRecibosService
{
    /**
     * Obtener prendas con recibos de costura del operario
     */
    public function obtenerPrendasConRecibos(User $usuario): Collection
    {
        // Obtener tipo de operario
        $tipoOperario = $this->obtenerTipoOperario($usuario);

        // Obtener todos los recibos de costura activos con relaciones
        $recibos = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA'])
            ->with(['prenda', 'prenda.pedidoProduccion'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar por prenda
        $prendasAgrupadas = $recibos->groupBy('prenda_id')->map(function ($recibosDelaPrenda) {
            $primeRecibo = $recibosDelaPrenda->first();
            $prenda = $primeRecibo->prenda;
            
            // Validar que prenda y pedido existan
            if (!$prenda || !$prenda->pedidoProduccion) {
                return null;
            }
            
            $pedido = $prenda->pedidoProduccion;

            return [
                'prenda_id' => $prenda->id,
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'nombre_prenda' => $prenda->nombre_prenda,
                'descripcion' => $prenda->descripcion,
                'de_bodega' => $prenda->de_bodega,
                'recibos' => $recibosDelaPrenda->map(function ($recibo) {
                    return [
                        'id' => $recibo->id,
                        'tipo_recibo' => $recibo->tipo_recibo,
                        'consecutivo_actual' => $recibo->consecutivo_actual,
                        'consecutivo_inicial' => $recibo->consecutivo_inicial,
                        'notas' => $recibo->notas,
                        'creado_en' => $recibo->created_at,
                    ];
                })->toArray(),
                'total_recibos' => $recibosDelaPrenda->count(),
                'fecha_creacion' => $prenda->created_at,
            ];
        })->filter(fn($item) => $item !== null)->values();

        return $prendasAgrupadas;
    }

    /**
     * Obtener tipo de operario del usuario
     */
    private function obtenerTipoOperario(User $usuario): string
    {
        if ($usuario->hasRole('cortador')) {
            return 'cortador';
        }

        if ($usuario->hasRole('costurero')) {
            return 'costurero';
        }

        if ($usuario->hasRole('bodeguero')) {
            return 'bodeguero';
        }

        if ($usuario->hasRole('costura-reflectivo')) {
            return 'costura-reflectivo';
        }

        return 'desconocido';
    }
}
