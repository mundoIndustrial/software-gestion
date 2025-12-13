<?php

namespace App\Application\Operario\Services;

use App\Application\Operario\DTOs\ObtenerPedidosOperarioDTO;
use App\Models\User;
use App\Models\PedidoProduccion;
use Illuminate\Support\Collection;

/**
 * Service: ObtenerPedidosOperarioService
 * 
 * Obtiene los pedidos asignados a un operario (cortador/costurero)
 * Filtra por área según el tipo de operario
 */
class ObtenerPedidosOperarioService
{
    public function __construct()
    {
        // Service sin dependencias - usa modelos directamente
    }

    /**
     * Obtener pedidos del operario autenticado
     */
    public function obtenerPedidosDelOperario(User $usuario): ObtenerPedidosOperarioDTO
    {
        // Obtener tipo de operario del usuario
        $tipoOperario = $this->obtenerTipoOperario($usuario);
        $areaOperario = $this->obtenerAreaOperario($tipoOperario);

        // Obtener pedidos en el área del operario
        $pedidos = $this->obtenerPedidosPorArea($areaOperario);

        // Contar estados
        $pedidosEnProceso = $pedidos->where('estado', 'En Ejecución')->count();
        $pedidosCompletados = $pedidos->where('estado', 'Completada')->count();

        return new ObtenerPedidosOperarioDTO(
            operarioId: $usuario->id,
            nombreOperario: $usuario->name,
            tipoOperario: $tipoOperario,
            areaOperario: $areaOperario,
            pedidos: $this->formatearPedidos($pedidos),
            totalPedidos: $pedidos->count(),
            pedidosEnProceso: $pedidosEnProceso,
            pedidosCompletados: $pedidosCompletados
        );
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

        return 'desconocido';
    }

    /**
     * Obtener área según tipo de operario
     */
    private function obtenerAreaOperario(string $tipoOperario): string
    {
        return match($tipoOperario) {
            'cortador' => 'Corte',
            'costurero' => 'Costura',
            default => 'Desconocida',
        };
    }

    /**
     * Obtener pedidos por área
     */
    private function obtenerPedidosPorArea(string $area): Collection
    {
        $usuarioActual = auth()->user();

        return PedidoProduccion::with(['prendas'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($pedido) use ($area, $usuarioActual) {
                return $this->pedidoPertenecealArea($pedido, $area, $usuarioActual);
            });
    }

    /**
     * Verificar si el pedido está asignado al operario actual
     */
    private function pedidoPertenecealArea($pedido, string $area, $usuarioActual): bool
    {
        // Obtener procesos del pedido (por numero_pedido)
        $procesos = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->get();

        if ($procesos->isEmpty()) {
            return false;
        }

        $usuarioNormalizado = strtolower(trim($usuarioActual->name));

        // Para cortador: buscar procesos "Corte" donde el usuario es el encargado
        if ($area === 'Corte') {
            return $procesos->contains(function ($proceso) use ($usuarioNormalizado) {
                if (!$proceso->encargado) {
                    return false;
                }
                
                $encargadoNormalizado = strtolower(trim($proceso->encargado));
                $procesNormalizado = strtolower(trim($proceso->proceso));
                
                return $procesNormalizado === 'corte' && $encargadoNormalizado === $usuarioNormalizado;
            });
        }

        // Para costurero: buscar procesos "Costura" donde el usuario es el encargado
        if ($area === 'Costura') {
            return $procesos->contains(function ($proceso) use ($usuarioNormalizado) {
                if (!$proceso->encargado) {
                    return false;
                }
                
                $encargadoNormalizado = strtolower(trim($proceso->encargado));
                $procesNormalizado = strtolower(trim($proceso->proceso));
                
                return $procesNormalizado === 'costura' && $encargadoNormalizado === $usuarioNormalizado;
            });
        }

        return false;
    }

    /**
     * Formatear pedidos para respuesta
     */
    private function formatearPedidos(Collection $pedidos): array
    {
        return $pedidos->map(function ($pedido) {
            $prendas = $pedido->prendas ?? collect();
            $totalPrendas = $prendas->sum('cantidad') ?? 0;
            $descripcionPrendas = $prendas->pluck('nombre_prenda')->unique()->join(', ');

            return [
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'descripcion' => $descripcionPrendas ?: 'Sin descripción',
                'descripcion_prendas' => $pedido->descripcion_prendas ?? $descripcionPrendas ?: 'Sin descripción',
                'cantidad' => $totalPrendas,
                'estado' => $pedido->estado,
                'area' => $this->obtenerAreaActual($pedido->numero_pedido),
                'fecha_creacion' => $pedido->fecha_de_creacion_de_orden?->format('d/m/Y') ?? $pedido->created_at?->format('d/m/Y'),
                'dia_entrega' => $pedido->dia_de_entrega ?? '-',
                'fecha_estimada' => $pedido->fecha_estimada_de_entrega?->format('d/m/Y') ?? '-',
                'asesora' => $pedido->asesora?->name ?? 'Sin asesora',
                'forma_pago' => $pedido->forma_de_pago,
                'novedades' => $pedido->novedades ?? '-',
            ];
        })->toArray();
    }

    /**
     * Obtener área actual del pedido
     */
    private function obtenerAreaActual(string $numeroPedido): string
    {
        $procesos = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->where('estado_proceso', '!=', 'Completado')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($procesos) {
            return $procesos->proceso;
        }

        return 'Desconocida';
    }
}
