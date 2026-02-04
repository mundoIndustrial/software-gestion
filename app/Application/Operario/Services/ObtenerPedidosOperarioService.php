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
        // Obtener tipo de operario del usuario
        $tipoOperario = $this->obtenerTipoOperario($usuario);
        $areaOperario = $this->obtenerAreaOperario($tipoOperario);

        // Para bodeguero: obtener SOLO pedidos que tengan RECIBO COSTURA-BODEGA
        if ($tipoOperario === 'bodeguero') {
            $pedidos = PedidoProduccion::with(['prendas'])
                ->whereHas('consecutivosRecibos', function ($query) {
                    $query->where('tipo_recibo', 'COSTURA-BODEGA')->where('activo', 1);
                })
                ->orderBy('created_at', 'desc')
                ->get();
            
            \Log::info('[ObtenerPedidosOperarioService] Bodeguero - Obteniendo pedidos con RECIBO COSTURA-BODEGA', [
                'usuario' => $usuario->name,
                'total_pedidos' => $pedidos->count()
            ]);
        } else {
            // Obtener pedidos filtrando por procesos donde el usuario sea el encargado
            $pedidos = $this->obtenerPedidosPorArea($areaOperario, $usuario);
        }

        // Contar estados
        $pedidosEnProceso = $pedidos->where('estado', 'En Ejecución')->count();
        $pedidosCompletados = $pedidos->where('estado', 'Completada')->count();

        return new ObtenerPedidosOperarioDTO(
            operarioId: $usuario->id,
            nombreOperario: $usuario->name,
            tipoOperario: $tipoOperario,
            areaOperario: $areaOperario,
            pedidos: $this->formatearPedidos($pedidos, $tipoOperario),
            totalPedidos: $pedidos->count(),
            pedidosEnProceso: $pedidosEnProceso,
            pedidosCompletados: $pedidosCompletados
        );
    }

    /**
     * Obtener pedidos especiales para Costura-Reflectivo
     * 
     * Filtra pedidos que:
     * 1. CUALQUIER proceso donde el usuario sea el encargado (sin restricción de área ni estado)
     * 2. O procesos del área "Costura" en estado "En Ejecución"
     */
    private function obtenerPedidosCosturaReflectivo(User $usuario): ObtenerPedidosOperarioDTO
    {
        \Log::info('=== INICIO obtenerPedidosCosturaReflectivo ===', [
            'usuario' => $usuario->name,
            'usuario_id' => $usuario->id
        ]);
        
        $usuarioNormalizado = strtolower(trim($usuario->name));
        
        // Obtener TODOS los pedidos
        $todosPedidos = PedidoProduccion::with(['prendas'])
            ->orderBy('fecha_de_creacion_de_orden', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        \Log::info('Total de pedidos en BD:', ['total' => $todosPedidos->count()]);
        
        // Filtrar: procesos donde el usuario es el encargado
        $pedidos = $todosPedidos->filter(function ($pedido) use ($usuarioNormalizado) {
            $procesos = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->get();
            
            if ($procesos->isEmpty()) {
                return false;
            }
            
            // Buscar CUALQUIER proceso donde el usuario sea el encargado
            $tieneProcesoDelUsuario = $procesos->contains(function ($proceso) use ($usuarioNormalizado) {
                if (!$proceso->encargado) {
                    return false;
                }
                
                $encargadoNormalizado = strtolower(trim($proceso->encargado));
                return $encargadoNormalizado === $usuarioNormalizado;
            });
            
            return $tieneProcesoDelUsuario;
        });
        
        \Log::info('Pedidos asignados al usuario:', [
            'usuario' => $usuario->name,
            'total' => $pedidos->count(),
            'pedidos' => $pedidos->map(fn($p) => $p->numero_pedido)->toArray()
        ]);
        
        $pedidos = $pedidos->sortByDesc(function ($pedido) use ($usuarioNormalizado) {
            // Obtener fecha de inicio del primer proceso del usuario
            $procesoDelUsuario = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->get()
                ->first(function ($proceso) use ($usuarioNormalizado) {
                    if (!$proceso->encargado) {
                        return false;
                    }
                    return strtolower(trim($proceso->encargado)) === $usuarioNormalizado;
                });
            
            $fechaOrden = $procesoDelUsuario?->fecha_inicio ?? $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at;
            return $fechaOrden;
        })->values();

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
            'bodeguero' => 'Bodega',
            default => 'Desconocida',
        };
    }

    /**
     * Obtener pedidos por Ã¡rea
     * Filtra pedidos donde el usuario sea el encargado de algún proceso
     */
    private function obtenerPedidosPorArea(string $area, User $usuario): Collection
    {
        return PedidoProduccion::with(['prendas'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($pedido) use ($area, $usuario) {
                return $this->pedidoPertenecealArea($pedido, $area, $usuario);
            });
    }

    /**
     * Verificar si el pedido estÃ¡ asignado al operario actual
     * 
     * Lógica:
     * 1. Busca procesos del área del operario (Corte/Costura) donde el usuario sea el encargado
     * 2. O, SI NO ENCUENTRA, busca CUALQUIER proceso donde el usuario sea el encargado
     *    (para permitir que el operario vea pedidos asignados a él directamente)
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

        // PASO 1: Buscar procesos del área específica donde el usuario sea el encargado
        $procesoDelArea = $procesos->contains(function ($proceso) use ($usuarioNormalizado, $area) {
            if (!$proceso->encargado) {
                return false;
            }
            
            $encargadoNormalizado = strtolower(trim($proceso->encargado));
            $procesNormalizado = strtolower(trim($proceso->proceso));
            
            // Validar que sea del área correcta
            $areaEsperada = strtolower(trim($area));
            return $procesNormalizado === $areaEsperada && $encargadoNormalizado === $usuarioNormalizado;
        });

        if ($procesoDelArea) {
            return true;
        }

        // PASO 2: Si no hay procesos del área, buscar CUALQUIER proceso donde el usuario sea el encargado
        // Esto permite que aparezcan pedidos asignados directamente al usuario
        $cualquierProcesoDelUsuario = $procesos->contains(function ($proceso) use ($usuarioNormalizado) {
            if (!$proceso->encargado) {
                return false;
            }
            
            $encargadoNormalizado = strtolower(trim($proceso->encargado));
            return $encargadoNormalizado === $usuarioNormalizado;
        });

        return $cualquierProcesoDelUsuario;
    }

    /**
     * Formatear pedidos para respuesta
     */
    private function formatearPedidos(Collection $pedidos, string $tipoOperario = null): array
    {
        return $pedidos->map(function ($pedido) use ($tipoOperario) {
            // Validar que el pedido tenga número_pedido
            if (!$pedido->numero_pedido) {
                \Log::warning('[ObtenerPedidosOperarioService] Pedido sin numero_pedido', [
                    'pedido_id' => $pedido->id,
                    'cliente' => $pedido->cliente
                ]);
                return null;
            }
            
            $prendas = $pedido->prendas ?? collect();
            
            // Calcular total de prendas sumando cantidades de las tallas
            $totalPrendas = 0;
            foreach ($prendas as $prenda) {
                if ($prenda->tallas && is_countable($prenda->tallas)) {
                    $totalPrendas += collect($prenda->tallas)->sum('cantidad') ?? 0;
                }
            }
            
            $descripcionPrendas = $prendas->pluck('nombre_prenda')->unique()->join(', ');

            // Obtener fecha de inicio del proceso en el área
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
                'estado' => $this->obtenerEstadoActual($pedido->numero_pedido, $tipoOperario),
                'area' => $this->obtenerAreaActual($pedido->numero_pedido, $tipoOperario),
                'fecha_creacion' => $pedido->fecha_de_creacion_de_orden?->format('d/m/Y') ?? $pedido->created_at?->format('d/m/Y'),
                'fecha_inicio_proceso' => $fechaInicioProceso,
                'dia_entrega' => $pedido->dia_de_entrega ?? '-',
                'fecha_estimada' => $pedido->fecha_estimada_de_entrega?->format('d/m/Y') ?? '-',
                'asesora' => $pedido->asesora?->name ?? 'Sin asesora',
                'forma_pago' => $pedido->forma_de_pago,
                'novedades' => $pedido->novedades ?? '-',
                'created_at' => $pedido->created_at,
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Obtener estado actual del proceso del pedido
     */
    private function obtenerEstadoActual(string $numeroPedido, string $tipoOperario = null): string
    {
        // Si es bodeguero, verificar recibo COSTURA-BODEGA en lugar de procesos
        if ($tipoOperario === 'bodeguero') {
            $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if ($pedido) {
                $tieneCosturaBodega = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $pedido->id)
                    ->where('tipo_recibo', 'COSTURA-BODEGA')
                    ->where('activo', 1)
                    ->exists();
                
                if ($tieneCosturaBodega) {
                    return 'En Ejecución';
                }
            }
            
            return 'Desconocida';
        }
        
        // Para otros roles, buscar procesos como antes
        // Primero buscar procesos activos (no completados)
        $procesoActivo = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->where('estado_proceso', '!=', 'Completado')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($procesoActivo) {
            return $procesoActivo->estado_proceso;
        }

        // Si todos los procesos están completados, buscar el último completado
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
     * Obtener área actual del pedido
     */
    private function obtenerAreaActual(string $numeroPedido): string
    {
        // Si es bodeguero, verificar recibo COSTURA-BODEGA en lugar de procesos
        if (auth()->check() && auth()->user()->hasRole('bodeguero')) {
            $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if ($pedido) {
                $tieneCosturaBodega = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $pedido->id)
                    ->where('tipo_recibo', 'COSTURA-BODEGA')
                    ->where('activo', 1)
                    ->exists();
                
                if ($tieneCosturaBodega) {
                    return 'EN BODEGA';
                }
            }
            
            return 'Desconocida';
        }
        
        // Para otros roles, buscar procesos como antes
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

