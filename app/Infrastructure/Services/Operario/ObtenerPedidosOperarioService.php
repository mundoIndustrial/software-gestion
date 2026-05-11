<?php

namespace App\Infrastructure\Services\Operario;

use App\Application\Operario\DTOs\ObtenerPedidosOperarioDTO;
use App\Domain\Operario\Services\OperarioPedidosReadService;
use App\Models\User;
use App\Models\PedidoProduccion;
use Illuminate\Support\Collection;
use App\Application\Pedidos\Services\PedidoDescriptionService;

/**
 * Service: ObtenerPedidosOperarioService
 * Obtiene los pedidos asignados a un operario (cortador/costurero)
 * Filtra por area según el tipo de operario
 */
class ObtenerPedidosOperarioService implements OperarioPedidosReadService
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

        // Para costura-reflectivo: lógica especial
        if ($tipoOperario === 'costura-reflectivo') {
            return $this->obtenerPedidosCosturaReflectivo($usuario);
        }

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
    /**
     * Obtener pedidos especiales para Costura-Reflectivo
     * 
     * Filtra pedidos que:
     * 1. CUALQUIER proceso donde el usuario sea el encargado
     */
    private function obtenerPedidosCosturaReflectivo(User $usuario): ObtenerPedidosOperarioDTO
    {
        $usuarioNormalizado = strtolower(trim($usuario->name));
        
        // Obtener pedidos filtrando directamente en BD
        $pedidos = PedidoProduccion::with(['prendas'])
            ->whereHas('procesos', function ($query) use ($usuarioNormalizado) {
                $query->whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNormalizado]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        // El ordenamiento por fecha de inicio del proceso se mantiene en memoria si es necesario, 
        // pero ahora sobre un set mucho más pequeño de datos.
        $pedidos = $pedidos->sortByDesc(function ($pedido) use ($usuarioNormalizado) {
            $procesoDelUsuario = $pedido->procesos->first(function ($proceso) use ($usuarioNormalizado) {
                return $proceso->encargado && strtolower(trim($proceso->encargado)) === $usuarioNormalizado;
            });
            return $procesoDelUsuario?->fecha_inicio ?? $pedido->created_at;
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

        if ($usuario->hasAnyRole(['costurero', 'vista-costura'])) {
            return 'costurero';
        }

        if ($usuario->hasRole('bodeguero')) {
            return 'bodeguero';
        }

        if ($usuario->hasRole('costura-reflectivo')) {
            return 'costura-reflectivo';
        }

        if ($usuario->hasRole('lider-reflectivo')) {
            return 'costura-reflectivo'; // Mismo comportamiento que costura-reflectivo
        }

        return 'desconocido';
    }

    /**
     * Obtener area según tipo de operario
     */
    private function obtenerAreaOperario(string $tipoOperario): string
    {
        return match($tipoOperario) {
            'cortador' => 'Corte',
            'costurero' => 'Costura',
            'bodeguero' => 'Bodega',
            'costura-reflectivo' => 'Costura-Reflectivo',
            default => 'Desconocida',
        };
    }

    /**
     * Obtener pedidos por area
     * Filtra pedidos donde el usuario sea el encargado de algún proceso
     */
    private function obtenerPedidosPorArea(string $area, User $usuario): Collection
    {
        $usuarioNormalizado = strtolower(trim($usuario->name));

        return PedidoProduccion::with(['prendas', 'procesos'])
            ->whereHas('procesos', function ($query) use ($usuarioNormalizado) {
                $query->whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNormalizado]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
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

            // Obtener fecha de inicio del proceso en el área (desde la relación ya cargada)
            $procesoArea = $pedido->procesos
                ->where('estado_proceso', '!=', 'Completado')
                ->sortBy('created_at')
                ->first();

            $fechaInicioProceso = $procesoArea?->fecha_inicio?->format('d/m/Y') ?? '-';

            // Generar descripción completa usando el servicio
            $descriptionService = app(PedidoDescriptionService::class);
            $descripcionCompleta = $descriptionService->generatePrendasDescription($pedido) ?: $descripcionPrendas ?: 'Sin descripción';

            return [
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'descripcion' => $descripcionPrendas ?: 'Sin descripción',
                'descripcion_prendas' => $descripcionCompleta,
                'cantidad' => $totalPrendas,
                'estado' => $this->obtenerEstadoDesdeRelacion($pedido, $tipoOperario),
                'area' => $this->obtenerAreaDesdeRelacion($pedido),
                'fecha_creacion' => $pedido->created_at?->format('d/m/Y'),
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

    private function obtenerEstadoDesdeRelacion($pedido, $tipoOperario): string
    {
        if ($tipoOperario === 'bodeguero') {
            $tieneCosturaBodega = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedido->id)
                ->where('tipo_recibo', 'COSTURA-BODEGA')
                ->where('activo', 1)
                ->exists();
            return $tieneCosturaBodega ? 'En Ejecución' : 'Desconocida';
        }

        $procesoActivo = $pedido->procesos
            ->where('estado_proceso', '!=', 'Completado')
            ->sortBy('created_at')
            ->first();

        if ($procesoActivo) return $procesoActivo->estado_proceso;

        $procesoCompletado = $pedido->procesos
            ->where('estado_proceso', 'Completado')
            ->sortByDesc('created_at')
            ->first();

        return $procesoCompletado ? 'Completado' : 'Desconocida';
    }

    private function obtenerAreaDesdeRelacion($pedido): string
    {
        if (auth()->check() && auth()->user()->hasRole('bodeguero')) {
            $tieneCosturaBodega = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedido->id)
                ->where('tipo_recibo', 'COSTURA-BODEGA')
                ->where('activo', 1)
                ->exists();
            return $tieneCosturaBodega ? 'EN BODEGA' : 'Desconocida';
        }

        $procesoActivo = $pedido->procesos
            ->where('estado_proceso', '!=', 'Completado')
            ->sortBy('created_at')
            ->first();

        return $procesoActivo ? $procesoActivo->proceso : 'Desconocida';
    }


}

