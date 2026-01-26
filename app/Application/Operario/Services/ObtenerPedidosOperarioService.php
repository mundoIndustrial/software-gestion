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
 * Filtra por Ã¡rea segÃºn el tipo de operario
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
        // Verificar si es el usuario especial "Costura-Reflectivo"
        if (strtolower(trim($usuario->name)) === 'costura-reflectivo') {
            return $this->obtenerPedidosCosturaReflectivo($usuario);
        }

        // Obtener tipo de operario del usuario
        $tipoOperario = $this->obtenerTipoOperario($usuario);
        $areaOperario = $this->obtenerAreaOperario($tipoOperario);

        // Obtener pedidos en el Ã¡rea del operario
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
     * Obtener pedidos especiales para Costura-Reflectivo
     * 
     * Filtra pedidos que:
     * 1. Tengan Ã¡rea "Costura" EN pedidos_produccion
     * 2. Y tengan proceso Costura con encargado "Ramiro"
     * 3. Y estÃ©n en estado "En Ejecución" (campo estado del pedido)
     */
    private function obtenerPedidosCosturaReflectivo(User $usuario): ObtenerPedidosOperarioDTO
    {
        \Log::info('=== INICIO obtenerPedidosCosturaReflectivo ===');
        
        $pedidos = PedidoProduccion::where('area', 'Costura')
            ->where('estado', 'En Ejecución')
            ->with(['prendas'])
            ->orderBy('fecha_de_creacion_de_orden', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        \Log::info('Pedidos ANTES del filtro Ramiro:', [
            'total' => $pedidos->count(),
            'pedidos' => $pedidos->map(fn($p) => [
                'numero' => $p->numero_pedido,
                'fecha_orden' => $p->fecha_de_creacion_de_orden?->format('Y-m-d H:i:s'),
                'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
            ])->toArray()
        ]);
        
        $pedidos = $pedidos->filter(function ($pedido) {
            // Verificar que tenga proceso Costura con Ramiro
            return $this->tieneProcesoRamiro($pedido);
        });
        
        \Log::info('Pedidos DESPUÃ‰S del filtro Ramiro (antes de sortByDesc):', [
            'total' => $pedidos->count(),
            'pedidos' => $pedidos->map(fn($p) => [
                'numero' => $p->numero_pedido,
                'fecha_orden' => $p->fecha_de_creacion_de_orden?->format('Y-m-d H:i:s'),
                'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
            ])->toArray()
        ]);
        
        $pedidos = $pedidos->sortByDesc(function ($pedido) {
            // Obtener fecha de inicio del proceso en Costura (sin filtrar por estado)
            $procesoArea = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->where('proceso', 'Costura')
                ->where('encargado', 'Ramiro')
                ->orderBy('fecha_inicio', 'desc')
                ->first();
            
            $fechaOrden = $procesoArea?->fecha_inicio ?? $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at;
            
            \Log::info("Ordenando pedido {$pedido->numero_pedido}: fecha_inicio = " . ($procesoArea?->fecha_inicio?->format('Y-m-d H:i:s') ?? 'null') . ", usando: " . ($fechaOrden ? $fechaOrden->format('Y-m-d H:i:s') : 'null'));
            
            return $fechaOrden;
        })->values();
        
        \Log::info('Pedidos DESPUÃ‰S de sortByDesc:', [
            'total' => $pedidos->count(),
            'pedidos' => $pedidos->map(function($p) {
                $procesoArea = \App\Models\ProcesoPrenda::where('numero_pedido', $p->numero_pedido)
                    ->where('proceso', 'Costura')
                    ->where('encargado', 'Ramiro')
                    ->orderBy('fecha_inicio', 'desc')
                    ->first();
                
                return [
                    'numero' => $p->numero_pedido,
                    'fecha_orden' => $p->fecha_de_creacion_de_orden?->format('Y-m-d H:i:s'),
                    'fecha_inicio_proceso' => $procesoArea?->fecha_inicio?->format('Y-m-d H:i:s'),
                    'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        ]);

        // Contar estados
        $pedidosEnProceso = $pedidos->where('estado', 'En Ejecución')->count();
        $pedidosCompletados = $pedidos->where('estado', 'Completada')->count();

        return new ObtenerPedidosOperarioDTO(
            operarioId: $usuario->id,
            nombreOperario: $usuario->name,
            tipoOperario: 'costurero-reflectivo',
            areaOperario: 'Costura-Reflectivo',
            pedidos: $this->formatearPedidos($pedidos),
            totalPedidos: $pedidos->count(),
            pedidosEnProceso: $pedidosEnProceso,
            pedidosCompletados: $pedidosCompletados
        );
    }

    /**
     * Verificar si el pedido tiene proceso Costura asignado a Ramiro
     * 
     * Busca en procesos_prenda:
     * - proceso = "Costura"
     * - encargado = "Ramiro" (normalizado, sin importar mayÃºsculas)
     */
    private function tieneProcesoRamiro($pedido): bool
    {
        $procesos = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->where('proceso', 'Costura')
            ->get();

        foreach ($procesos as $proceso) {
            if ($proceso->encargado) {
                $encargadoNormalizado = strtolower(trim($proceso->encargado));
                if ($encargadoNormalizado === 'ramiro') {
                    return true;
                }
            }
        }

        return false;
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
     * Obtener Ã¡rea segÃºn tipo de operario
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
     * Obtener pedidos por Ã¡rea
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
     * Verificar si el pedido estÃ¡ asignado al operario actual
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

            // Obtener fecha de inicio del proceso en el Ã¡rea
            $procesoArea = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->where('estado_proceso', '!=', 'Completado')
                ->orderBy('created_at', 'asc')
                ->first();

            $fechaInicioProceso = $procesoArea?->fecha_inicio?->format('d/m/Y') ?? '-';

            return [
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'descripcion' => $descripcionPrendas ?: 'Sin descripción',
                'descripcion_prendas' => $pedido->descripcion_prendas ?? $descripcionPrendas ?: 'Sin descripción',
                'cantidad' => $totalPrendas,
                'estado' => $this->obtenerEstadoActual($pedido->numero_pedido),
                'area' => $this->obtenerAreaActual($pedido->numero_pedido),
                'fecha_creacion' => $pedido->fecha_de_creacion_de_orden?->format('d/m/Y') ?? $pedido->created_at?->format('d/m/Y'),
                'fecha_inicio_proceso' => $fechaInicioProceso,
                'dia_entrega' => $pedido->dia_de_entrega ?? '-',
                'fecha_estimada' => $pedido->fecha_estimada_de_entrega?->format('d/m/Y') ?? '-',
                'asesora' => $pedido->asesora?->name ?? 'Sin asesora',
                'forma_pago' => $pedido->forma_de_pago,
                'novedades' => $pedido->novedades ?? '-',
                'created_at' => $pedido->created_at,
            ];
        })->values()->toArray();
    }

    /**
     * Obtener estado actual del proceso del pedido
     */
    private function obtenerEstadoActual(string $numeroPedido): string
    {
        // Primero buscar procesos activos (no completados)
        $procesoActivo = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->where('estado_proceso', '!=', 'Completado')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($procesoActivo) {
            return $procesoActivo->estado_proceso;
        }

        // Si todos los procesos estÃ¡n completados, buscar el Ãºltimo completado
        $procesoCompletado = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->where('estado_proceso', 'Completado')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($procesoCompletado) {
            return 'Completado';
        }

        return 'Desconocida';
    }

    /**
     * Obtener Ã¡rea actual del pedido
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

