<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class ObtenerProximoPedidoService
{
    /**
     * Obtener el siguiente numero de pedido disponible
     * 
     * @return int
     */
    public function obtenerProximo(): int
    {
        Log::info('ðŸ”¢ [PRÃ“XIMO PEDIDO] Buscando siguiente numero disponible');

        $ultimoPedido = PedidoProduccion::max('numero_pedido');
        $siguientePedido = $ultimoPedido ? $ultimoPedido + 1 : 1;

        Log::info('ðŸ”¢ [PRÃ“XIMO PEDIDO] Encontrado', [
            'ultimo_pedido' => $ultimoPedido,
            'siguiente_pedido' => $siguientePedido
        ]);

        return $siguientePedido;
    }

    /**
     * Validar si un numero de pedido ya existe
     * 
     * @param int $numeroPedido
     * @return bool
     */
    public function existeNumeroPedido(int $numeroPedido): bool
    {
        $existe = PedidoProduccion::where('numero_pedido', $numeroPedido)->exists();
        
        Log::info('ðŸ”¢ [VALIDAR PEDIDO] numero ' . $numeroPedido . ' existe: ' . ($existe ? 'SÃ' : 'NO'));

        return $existe;
    }

    /**
     * Obtener rango de numeros disponibles (últimos 10 numeros usados + próximo)
     * Ãštil para generar opciones de selección en formularios
     * 
     * @param int $cantidad Cantidad de numeros anteriores a mostrar
     * @return array
     */
    public function obtenerRangoDisponible(int $cantidad = 10): array
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 0;
        $proximoPedido = $ultimoPedido + 1;

        // Obtener los últimos numeros usados
        $ultimosUsados = PedidoProduccion::select('numero_pedido')
            ->orderBy('numero_pedido', 'desc')
            ->limit($cantidad)
            ->pluck('numero_pedido')
            ->toArray();

        Log::info('ðŸ”¢ [RANGO DISPONIBLE] Generado', [
            'ultimo_numero' => $ultimoPedido,
            'proximo_numero' => $proximoPedido,
            'ultimos_usados_count' => count($ultimosUsados)
        ]);

        return [
            'ultimo_usado' => $ultimoPedido,
            'proximo' => $proximoPedido,
            'ultimos_usados' => $ultimosUsados
        ];
    }
}

