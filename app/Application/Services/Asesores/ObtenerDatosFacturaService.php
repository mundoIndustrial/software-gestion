<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ObtenerDatosFacturaService
{
    /**
     * Obtener datos completos de factura para un pedido
     * Soporta tanto PedidoProduccion como LogoPedido
     * 
     * @param int $pedidoId
     * @return array
     * @throws \Exception
     */
    public function obtener(int $pedidoId): array
    {
        Log::info('[FACTURA] Obteniendo datos para pedido: ' . $pedidoId);

        // Intentar obtener como PedidoProduccion
        $pedido = PedidoProduccion::find($pedidoId);
        
        if ($pedido) {
            // Verificar permisos
            if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
                throw new \Exception('No tienes permiso para ver este pedido', 403);
            }
            
            return $this->obtenerDatosPedidoProduccion($pedido);
        }

        // Intentar obtener como LogoPedido
        $pedido = LogoPedido::find($pedidoId);
        
        if ($pedido) {
            return $this->obtenerDatosLogoPedido($pedido);
        }

        throw new \Exception('Pedido no encontrado', 404);
    }

    /**
     * Obtener datos de factura para PedidoProduccion
     */
    private function obtenerDatosPedidoProduccion(PedidoProduccion $pedido): array
    {
        Log::info('[FACTURA] Procesando PedidoProduccion', ['id' => $pedido->id]);

        // Usar el repository si está disponible
        $repository = resolve(\App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository::class);
        $datos = $repository->obtenerDatosFactura($pedido->id);

        Log::info('[FACTURA] Datos extraídos', [
            'numero_pedido' => $datos['numero_pedido'],
            'prendas_count' => count($datos['prendas']),
            'total_items' => $datos['total_items'],
        ]);

        return $datos;
    }

    /**
     * Obtener datos de factura para LogoPedido
     */
    private function obtenerDatosLogoPedido(LogoPedido $pedido): array
    {
        Log::info('[FACTURA] Procesando LogoPedido', ['id' => $pedido->id]);

        $datos = [
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'numero_pedido_temporal' => $pedido->numero_pedido ?? 0,
            'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
            'asesora' => is_object($pedido->asesora) 
                ? $pedido->asesora->name 
                : ($pedido->asesora ?? 'Sin asignar'),
            'forma_de_pago' => $pedido->forma_de_pago ?? 'No especificada',
            'fecha' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
            'fecha_creacion' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
            'observaciones' => $pedido->observaciones ?? '',
            'descripcion' => $pedido->descripcion ?? '',
            'tecnicas' => $pedido->tecnicas ?? '',
            'ubicaciones' => $pedido->ubicaciones ?? '',
            'prendas' => [],
            'total_items' => 0,
        ];

        Log::info('[FACTURA] Datos de LogoPedido listos', [
            'numero_pedido' => $datos['numero_pedido'],
            'cliente' => $datos['cliente'],
        ]);

        return $datos;
    }

    /**
     * Obtener datos resumidos de factura (sin prendas detalladas)
     */
    public function obtenerResumen(int $pedidoId): array
    {
        Log::info('[FACTURA-RESUMEN] Obteniendo resumen para: ' . $pedidoId);

        $pedido = PedidoProduccion::find($pedidoId);
        
        if (!$pedido) {
            $pedido = LogoPedido::find($pedidoId);
        }

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        return [
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
            'fecha' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
            'tipo' => get_class($pedido) === 'App\Models\LogoPedido' ? 'logo' : 'produccion',
        ];
    }
}
