<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Application\Services\Asesores\ObtenerPedidoDetalleService;

class ObtenerDatosFacturaService
{
    public function __construct(
        private PedidoProduccionRepository $pedidoProduccionRepository
    ) {}

    /**
     * Obtener datos completos de factura para un pedido
     * Soporta tanto Pedidos como LogoPedido
     * 
     * @param int $pedidoId
     * @return array
     * @throws \Exception
     */
    public function obtener(int $pedidoId): array
    {
        // Intentar obtener como Pedidos
        $pedido = PedidoProduccion::find($pedidoId);
        
        if ($pedido) {
            // Verificar permisos - permitir al asesor dueño o usuarios con roles de cartera/supervisor
            $usuario = Auth::user();
            if ($usuario) {
                // Si es el asesor dueño, permitir acceso
                if ($pedido->asesor_id && $pedido->asesor_id === $usuario->id) {
                    return $this->obtenerDatosPedidos($pedido);
                }
                
                // Si tiene roles de cartera o supervisor, permitir acceso
                $roles = $usuario->roles()->pluck('name')->toArray();
                if (in_array('cartera', $roles) || in_array('supervisor_pedidos', $roles) || in_array('admin', $roles)) {
                    return $this->obtenerDatosPedidos($pedido);
                }
            }
            
            throw new \Exception('No tienes permiso para ver este pedido', 403);
        }

        // Intentar obtener como LogoPedido
        $pedido = LogoPedido::find($pedidoId);
        
        if ($pedido) {
            return $this->obtenerDatosLogoPedido($pedido);
        }

        throw new \Exception('Pedido no encontrado', 404);
    }

    /**
     * Obtener datos de factura para Pedidos
     */
    private function obtenerDatosPedidos(PedidoProduccion $pedido): array
    {


        // Usar obtenerDatosFactura() que incluye manga, broche, bolsillos con todas las observaciones
        $datos = $this->pedidoProduccionRepository->obtenerDatosFactura($pedido->id);
        
        // Agregar el ID del pedido para poder usarlo en el frontend
        $datos['id'] = $pedido->id;

        return $datos;
    }

    /**
     * Obtener datos de factura para LogoPedido
     */
    private function obtenerDatosLogoPedido(LogoPedido $pedido): array
    {


        // Determinar la fecha de creación
        $fechaCreacion = $pedido->created_at 
            ? $pedido->created_at->format('d/m/Y') 
            : date('d/m/Y');

        $datos = [
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'numero_pedido_temporal' => $pedido->numero_pedido ?? 0,
            'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
            'asesora' => is_object($pedido->asesora) 
                ? $pedido->asesora->name 
                : ($pedido->asesora ?? 'Sin asignar'),
            'forma_de_pago' => $pedido->forma_de_pago ?? 'No especificada',
            'fecha' => $fechaCreacion,
            'fecha_creacion' => $fechaCreacion,
            'observaciones' => $pedido->observaciones ?? '',
            'descripcion' => $pedido->descripcion ?? '',
            'tecnicas' => $pedido->tecnicas ?? '',
            'ubicaciones' => $pedido->ubicaciones ?? '',
            'prendas' => [],
            'total_items' => 0,
        ];

        // Log::info('[FACTURA] Datos de LogoPedido listos', [
        //     'numero_pedido' => $datos['numero_pedido'],
        //     'cliente' => $datos['cliente'],
        // ]);

        return $datos;
    }

    /**
     * Obtener datos resumidos de factura (sin prendas detalladas)
     */
    public function obtenerResumen(int $pedidoId): array
    {


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

