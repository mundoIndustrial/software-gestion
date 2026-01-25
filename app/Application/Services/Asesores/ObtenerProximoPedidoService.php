<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class ObtenerProximoPedidoService
{
    /**
     * Obtener el siguiente nÃºmero de pedido disponible
     * 
     * @return int
     */
    public function obtenerProximo(): int
    {
        Log::info('ðŸ”¢ [PRÃ“XIMO PEDIDO] Buscando siguiente nÃºmero disponible');

        $ultimoPedido = Pedidos::max('numero_pedido');
        $siguientePedido = $ultimoPedido ? $ultimoPedido + 1 : 1;

        Log::info('ðŸ”¢ [PRÃ“XIMO PEDIDO] Encontrado', [
            'ultimo_pedido' => $ultimoPedido,
            'siguiente_pedido' => $siguientePedido
        ]);

        return $siguientePedido;
    }

    /**
     * Validar si un nÃºmero de pedido ya existe
     * 
     * @param int $numeroPedido
     * @return bool
     */
    public function existeNumeroPedido(int $numeroPedido): bool
    {
        $existe = Pedidos::where('numero_pedido', $numeroPedido)->exists();
        
        Log::info('ðŸ”¢ [VALIDAR PEDIDO] NÃºmero ' . $numeroPedido . ' existe: ' . ($existe ? 'SÃ' : 'NO'));

        return $existe;
    }

    /**
     * Obtener rango de nÃºmeros disponibles (Ãºltimos 10 nÃºmeros usados + prÃ³ximo)
     * Ãštil para generar opciones de selecciÃ³n en formularios
     * 
     * @param int $cantidad Cantidad de nÃºmeros anteriores a mostrar
     * @return array
     */
    public function obtenerRangoDisponible(int $cantidad = 10): array
    {
        $ultimoPedido = Pedidos::max('numero_pedido') ?? 0;
        $proximoPedido = $ultimoPedido + 1;

        // Obtener los Ãºltimos nÃºmeros usados
        $ultimosUsados = Pedidos::select('numero_pedido')
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

