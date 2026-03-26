<?php

namespace App\Infrastructure\Insumos\Persistence\Eloquent;

use App\Domain\Insumos\Repositories\RecibosPendientesRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\ReciboVistoInsumo;
use Illuminate\Support\Facades\Log;

class EloquentRecibosPendientesRepository implements RecibosPendientesRepository
{
    public function cambiarEstadoRecibo(int $reciboId, string $nuevoEstado): array
    {
        try {
            $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
            $estadoAnterior = $recibo->estado ?? 'PENDIENTE_INSUMOS';

            if ($estadoAnterior !== 'PENDIENTE_INSUMOS') {
                return [
                    'success' => false,
                    'message' => 'Este recibo ya ha sido aprobado',
                ];
            }

            $area = $this->determinarAreaPorEstado($nuevoEstado);

            $recibo->update([
                'estado' => $nuevoEstado,
                'area' => $area,
            ]);

            $recibosPendientes = ConsecutivoReciboPedido::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->where('estado', 'PENDIENTE_INSUMOS')
                ->count();

            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            if ($pedido && $pedido->estado === 'PENDIENTE_INSUMOS') {
                $pedido->update([
                    'estado' => $nuevoEstado,
                    'area' => $area,
                ]);
            }

            $this->crearProcesoCorteSiNoExiste($recibo, $pedido, $nuevoEstado);

            return [
                'success' => true,
                'message' => 'Recibo aprobado correctamente',
                'recibos_pendientes' => $recibosPendientes,
            ];
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del recibo: ' . $e->getMessage());

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
                ->where('estado', 'PENDIENTE_INSUMOS')
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
                'recibo_id' => $reciboId,
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
                ->where('estado', 'PENDIENTE_INSUMOS')
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

