<?php

namespace App\Infrastructure\Insumos\Persistence\Eloquent;

use App\Domain\Insumos\Repositories\RecibosPendientesRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\ReciboVistoInsumo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EloquentRecibosPendientesRepository implements RecibosPendientesRepository
{
    public function cambiarEstadoRecibo(int $reciboId, string $nuevoEstado): array
    {
        try {
            Log::info('[cambiarEstadoRecibo] Iniciando', [
                'reciboId' => $reciboId,
                'nuevoEstado' => $nuevoEstado,
            ]);

            $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
            $estadoAnterior = $recibo->estado ?? 'PENDIENTE_INSUMOS';
            $estadoReciboNormalizado = $this->normalizarEstadoRecibo($nuevoEstado);

            Log::info('[cambiarEstadoRecibo] Estados', [
                'estadoAnterior' => $estadoAnterior,
                'nuevoEstadoRecibido' => $nuevoEstado,
                'estadoReciboNormalizado' => $estadoReciboNormalizado,
            ]);

            if (!in_array($estadoAnterior, ['PENDIENTE_INSUMOS', 'Pendiente_Insumos', 'PENDIENTE_TELA', 'Pendiente Tela', 'PENDIENTE_PLOTTER', 'Pendiente Plotter', 'INSUMOS_PEDIDOS', 'Insumos Pedidos'], true)) {
                Log::warning('[cambiarEstadoRecibo] Estado anterior no válido', [
                    'estadoAnterior' => $estadoAnterior,
                ]);
                return [
                    'success' => false,
                    'message' => 'Este recibo ya ha sido aprobado',
                ];
            }

            $area = $this->determinarAreaPorEstado($estadoReciboNormalizado);
            
            Log::info('[cambiarEstadoRecibo] Determinando área', [
                'area' => $area,
            ]);

            $updateResult = $recibo->update([
                'estado' => $estadoReciboNormalizado,
                'area' => $area,
            ]);

            Log::info('[cambiarEstadoRecibo] Resultado del update', [
                'updateResult' => $updateResult,
                'estadoActualEnDB' => ConsecutivoReciboPedido::find($reciboId)->estado ?? 'NO ENCONTRADO',
            ]);

            $recibosPendientes = ConsecutivoReciboPedido::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->whereIn('estado', ['PENDIENTE_INSUMOS', 'PENDIENTE_TELA', 'PENDIENTE_PLOTTER', 'INSUMOS_PEDIDOS'])
                ->count();

            // Solo actualizar pedidos_produccion si el recibo se aprueba para producción (En Ejecución)
            // En otros estados intermedios NO se toca pedidos_produccion
            if (in_array($estadoReciboNormalizado, ['En Ejecución', 'En Ejecucion'], true)) {
                $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
                if ($pedido) {
                    $pedido->update([
                        'estado' => 'En Ejecución',
                        'area' => 'CORTE',
                    ]);
                    Log::info('[cambiarEstadoRecibo] Pedido actualizado a En Ejecución', [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                    ]);
                }
            }

            if ($this->debeCrearProcesoCorte($estadoReciboNormalizado)) {
                $recibo->refresh();
                $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
                $this->crearProcesoCorteSiNoExiste($recibo, $pedido, $estadoReciboNormalizado);
            }

            Log::info('[cambiarEstadoRecibo] Completado exitosamente', [
                'reciboId' => $reciboId,
                'estadoNuevo' => $estadoReciboNormalizado,
            ]);

            return [
                'success' => true,
                'message' => 'Recibo aprobado correctamente',
                'recibos_pendientes' => $recibosPendientes,
                'estado_guardado' => $estadoReciboNormalizado,
            ];
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del recibo', [
                'reciboId' => $reciboId,
                'nuevoEstado' => $nuevoEstado,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del recibo',
            ];
        }
    }

    public function contarCosturaPendiente(int $userId): array
    {
        try {
            $vistosIds = ReciboVistoInsumo::where('user_id', $userId)
                ->pluck('consecutivo_recibo_id')
                ->toArray();

            $query = ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
                ->whereIn('estado', ['PENDIENTE_INSUMOS', 'PENDIENTE_TELA', 'PENDIENTE_PLOTTER'])
                ->where('activo', 1);

            if (!empty($vistosIds)) {
                $query->whereNotIn('id', $vistosIds);
            }

            $total = $query->count();
            $recibos = $query->with(['pedido:id,numero_pedido,cliente'])->get();

            return [
                'success' => true,
                'total' => $total,
                'recibos' => $recibos,
            ];
        } catch (\Exception $e) {
            Log::error('Error al contar recibos pendientes: ' . $e->getMessage());

            return [
                'success' => false,
                'total' => 0,
                'recibos' => [],
            ];
        }
    }

    public function marcarReciboVisto(int $reciboId, int $userId): array
    {
        try {
            ReciboVistoInsumo::firstOrCreate([
                'consecutivo_recibo_id' => $reciboId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'Recibo marcado como visto',
            ];
        } catch (\Exception $e) {
            Log::error('Error al marcar recibo como visto: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al marcar como visto',
            ];
        }
    }

    public function obtenerResumenRecibosPendientes(int $userId): array
    {
        $resultado = $this->contarCosturaPendiente($userId);

        $lista = collect($resultado['recibos'] ?? [])->map(function ($recibo) {
            return [
                'id' => $recibo->id,
                'numero_recibo' => $recibo->consecutivo_actual,
                'cliente' => $recibo->pedido->cliente ?? 'Sin cliente',
                'pedido_id' => $recibo->pedido_produccion_id,
                'fecha' => $recibo->created_at ? $recibo->created_at->format('d/m/Y H:i') : '',
            ];
        })->values()->all();

        return [
            'success' => true,
            'total' => $resultado['total'] ?? count($lista),
            'recibos' => $lista,
        ];
    }

    public function obtenerRecibosCosturaPendientes(): array
    {
        try {
            $recibos = ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
                ->whereIn('estado', ['PENDIENTE_INSUMOS', 'PENDIENTE_TELA', 'PENDIENTE_PLOTTER'])
                ->with(['pedido', 'prenda'])
                ->orderBy('fecha_estimada_de_entrega', 'asc')
                ->get();

            return [
                'success' => true,
                'total' => $recibos->count(),
                'data' => $recibos->map(function ($recibo) {
                    return [
                        'id' => $recibo->id,
                        'tipo_recibo' => $recibo->tipo_recibo,
                        'estado' => $recibo->estado,
                        'area' => $recibo->area,
                        'consecutivo' => $recibo->consecutivo_actual,
                        'pedido_id' => $recibo->pedido_produccion_id,
                        'prenda_id' => $recibo->prenda_id,
                        'prenda_nombre' => $recibo->prenda?->nombre ?? $recibo->prenda?->nombre_prenda,
                        'pedido_numero' => $recibo->pedido?->numero_pedido,
                        'fecha_estimada' => $recibo->fecha_estimada_de_entrega,
                        'dia_entrega' => $recibo->dia_de_entrega,
                        'notas' => $recibo->notas,
                        'marcar_plooter' => $recibo->marcar_plooter ?? null,
                    ];
                })->values()->all(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener recibos de costura pendiente: ' . $e->getMessage());

            return [
                'success' => false,
                'total' => 0,
                'data' => [],
                'message' => 'Error al obtener recibos de costura pendiente',
            ];
        }
    }

    private function determinarAreaPorEstado(string $estado): string
    {
        return match ($estado) {
            'No iniciado' => 'TRAZO',
            'En Ejecución', 'En Ejecucion' => 'CORTE',
            default => 'INSUMOS',
        };
    }
    private function normalizarEstadoRecibo(string $estado): string
    {
        return match (trim($estado)) {
            'Pendiente_Insumos' => 'PENDIENTE_INSUMOS',
            'Insumos Pedidos' => 'INSUMOS_PEDIDOS',
            'Pendiente Tela' => 'PENDIENTE_TELA',
            'Pendiente Plotter' => 'PENDIENTE_PLOTTER',
            'Devuelto_Asesor' => 'DEVUELTO_ASESOR',
            default => trim($estado),
        };
    }

    
    private function debeCrearProcesoCorte(string $estadoRecibo): bool
    {
        $estadoNormalizado = strtolower(trim(Str::ascii($estadoRecibo)));
        return $estadoNormalizado === 'en ejecucion';
    }

    private function crearProcesoCorteSiNoExiste(
        ConsecutivoReciboPedido $recibo,
        ?PedidoProduccion $pedido,
        string $nuevoEstado
    ): void {
        if (!$pedido) {
            return;
        }

        $existeProceso = ProcesoPrenda::whereNull('deleted_at')
            ->where('numero_pedido', $pedido->numero_pedido)
            ->where('prenda_pedido_id', $recibo->prenda_id)
            ->where('proceso', 'Corte')
            ->when($recibo->consecutivo_actual, function ($query) use ($recibo) {
                $query->where('numero_recibo', $recibo->consecutivo_actual);
            })
            ->exists();

        if ($existeProceso) {
            return;
        }

        $estadoProceso = in_array($nuevoEstado, ['En Ejecución', 'En Ejecucion'], true)
            ? 'En Progreso'
            : 'Pendiente';

        ProcesoPrenda::create([
            'numero_pedido' => $pedido->numero_pedido,
            'prenda_pedido_id' => $recibo->prenda_id,
            'numero_recibo' => $recibo->consecutivo_actual,
            'proceso' => 'Corte',
            'fecha_inicio' => now(),
            'estado_proceso' => $estadoProceso,
            'observaciones' => 'Proceso creado automáticamente al aprobar recibo desde Insumos',
            'codigo_referencia' => sprintf(
                'P%s-COR-PP%s-R%s',
                $pedido->numero_pedido,
                $recibo->prenda_id ?? '0',
                $recibo->consecutivo_actual ?? '0'
            ),
        ]);
    }
}

