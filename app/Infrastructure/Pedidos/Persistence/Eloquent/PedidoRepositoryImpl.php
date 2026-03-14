<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Domain\Pedidos\ValueObjects\Estado;
use App\Domain\Pedidos\Entities\PrendaPedido;
use App\Models\PedidoProduccion as PedidoModel;
use App\Models\PrendaPedido as PrendaPedidoModel;

/**
 * Repository Implementation para Pedidos
 * 
 * Mapea el agregado DDD PedidoAggregate a la tabla pedidos_produccion existente
 * 
 * MAPEO DE CAMPOS:
 * ================
 * PedidoAggregate.numero -> pedidos_produccion.numero_pedido
 * PedidoAggregate.estado -> pedidos_produccion.estado
 * PedidoAggregate.descripcion -> pedidos_produccion.novedades
 * PedidoAggregate.observaciones -> pedidos_produccion.novedades (parte de)
 * 
 * Tabla base: pedidos_produccion
 * Tablas relacionadas (lectura): prendas_pedido, prenda_pedido_tallas, prenda_pedido_colores_telas, etc.
 * 
 * ESTADOS ENUM (pedidos_produccion):
 * - Pendiente
 * - Entregado
 * - En Ejecución
 * - No iniciado
 * - Anulada
 * - PENDIENTE_SUPERVISOR
 * 
 * Se mapean a DDD Estados: PENDIENTE, CONFIRMADO, EN_PRODUCCION, COMPLETADO, CANCELADO
 */
class PedidoRepositoryImpl implements PedidoRepository
{
    /**
     * Mapeo de estados: DDD -> BD
     */
    private function mapEstadoABD(string $estadoDDD): string
    {
        $mapa = [
            'PENDIENTE' => 'Pendiente',
            'CONFIRMADO' => 'En Ejecución',
            'EN_PRODUCCION' => 'En Ejecución',
            'COMPLETADO' => 'Entregado',
            'CANCELADO' => 'Anulada',
        ];
        
        return $mapa[$estadoDDD] ?? 'Pendiente';
    }

    /**
     * Mapeo de estados: BD -> DDD
     */
    private function mapEstadoADDD(string $estadoBD): string
    {
        $mapa = [
            'Pendiente' => 'PENDIENTE',
            'PENDIENTE_SUPERVISOR' => 'PENDIENTE',
            'En Ejecución' => 'EN_PRODUCCION',
            'Entregado' => 'COMPLETADO',
            'Anulada' => 'CANCELADO',
            'No iniciado' => 'PENDIENTE',
        ];
        
        return $mapa[$estadoBD] ?? 'PENDIENTE';
    }

    public function guardar(PedidoAggregate $pedido): void
    {
        \DB::transaction(function () use ($pedido) {
            // Mapear agregado DDD a campos BD
            $datos = [
                'numero_pedido' => $pedido->numero()->valor(),
                'cliente_id' => $pedido->clienteId(),
                'estado' => $this->mapEstadoABD($pedido->estado()->valor()),
                'novedades' => $pedido->observaciones(),
            ];

            if ($pedido->id() === null) {
                // Nuevo: crear
                $pedidoModel = PedidoModel::create($datos);
                $pedido->setId($pedidoModel->id);
            } else {
                // Actualizar existente
                $pedidoModel = PedidoModel::findOrFail($pedido->id());
                $pedidoModel->update($datos);
            }

            $this->guardarPrendas($pedido, $pedidoModel);
        });
    }

    public function porId(int $id): ?PedidoAggregate
    {
        $pedidoModel = PedidoModel::with('prendas')->find($id);
        
        if (!$pedidoModel) {
            return null;
        }

        return $this->reconstituir($pedidoModel);
    }

    public function porNumero(NumeroPedido $numero): ?PedidoAggregate
    {
        $pedidoModel = PedidoModel::with('prendas')
            ->where('numero_pedido', $numero->valor())
            ->first();

        if (!$pedidoModel) {
            return null;
        }

        return $this->reconstituir($pedidoModel);
    }

    public function porClienteId(int $clienteId): array
    {
        return PedidoModel::with('prendas')
            ->where('cliente_id', $clienteId)
            ->get()
            ->map(fn($model) => $this->reconstituir($model))
            ->toArray();
    }

    public function porEstado(string $estado): array
    {
        // Convertir estado DDD a estado BD
        $estadoBD = $this->mapEstadoABD($estado);
        
        return PedidoModel::with('prendas')
            ->where('estado', $estadoBD)
            ->get()
            ->map(fn($model) => $this->reconstituir($model))
            ->toArray();
    }

    public function eliminar(int $id): void
    {
        \DB::transaction(function () use ($id) {
            // Soft delete en pedidos_produccion (respeta datos históricos)
            PedidoModel::destroy($id);
        });
    }

    private function reconstituir(PedidoModel $model): PedidoAggregate
    {
        // Reconstruir agregado desde modelo BD existente (pedidos_produccion)
        // Mapear estados: BD -> DDD
        $estadoDDD = $this->mapEstadoADDD($model->estado);
        
        return PedidoAggregate::reconstruir(
            id: $model->id,
            numero: NumeroPedido::desde($model->numero_pedido),
            clienteId: $model->cliente_id ?? null,
            estado: Estado::desde($estadoDDD),
            descripcion: $model->novedades ?? '',
            prendas: [], // Las prendas se cargan desde prendas_pedido si es necesario
            fechaCreacion: $model->created_at,
            observaciones: $model->novedades ?? '',
            fechaActualizacion: $model->updated_at
        );
    }

    private function guardarPrendas(PedidoAggregate $pedido, PedidoModel $pedidoModel): void
    {
        // Las prendas se gestionan a través de la tabla prendas_pedido
        // Que usa pedido_produccion_id como FK
        // Esto se implementará en una segunda fase: PrendaPedidoRepository
    }

    /**
     * FASE 5: Calcular cantidad total de prendas en un pedido
     * 
     * @param int $pedidoId
     * @return int
     */
    public function calcularCantidadTotalPrendas(int $pedidoId): int
    {
        try {
            $cantidad = \DB::table('pedidos_procesos_prenda_tallas as pppt')
                ->selectRaw('COALESCE(SUM(pppt.cantidad), 0) as total')
                ->join('pedidos_procesos_prenda_detalles as ppd', 'pppt.proceso_prenda_detalle_id', '=', 'ppd.id')
                ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
                ->where('pp.pedido_produccion_id', $pedidoId)
                ->value('total');

            return (int) $cantidad ?? 0;
        } catch (\Exception $e) {
            \Log::warning('[PEDIDO-REPOSITORY] Error calculando cantidad de prendas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * FASE 5: Calcular cantidad total de EPPs en un pedido
     * 
     * @param int $pedidoId
     * @return int
     */
    public function calcularCantidadTotalEpps(int $pedidoId): int
    {
        try {
            $cantidad = \DB::table('pedido_epp')
                ->where('pedido_produccion_id', $pedidoId)
                ->sum('cantidad');

            return (int) $cantidad ?? 0;
        } catch (\Exception $e) {
            \Log::warning('[PEDIDO-REPOSITORY] Error calculando cantidad de EPPs', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * FASE 5: Crear notificación de pedido creado
     * 
     * @param object $pedido PedidoProduccion model
     * @param object $cliente Cliente model
     * @param int $usuarioId ID usuario creador
     * @param int $cantidadPrendas Cantidad de prendas
     * @param int $cantidadEpps Cantidad de EPPs
     * @return void
     */
    public function crearNotificacionPedido($pedido, $cliente, int $usuarioId, int $cantidadPrendas, int $cantidadEpps): void
    {
        try {
            $user = \Auth::user();
            $nombreAsesor = $user->name ?? 'Sistema';

            \App\Models\News::create([
                'event_type' => 'pedido_creado',
                'table_name' => 'pedidos_produccion',
                'record_id' => $pedido->id,
                'description' => "Asesor {$nombreAsesor} creó el Pedido #{$pedido->numero_pedido} - Cliente: {$cliente->nombre}",
                'user_id' => $usuarioId,
                'pedido' => $pedido->numero_pedido,
                'metadata' => [
                    'tipo' => 'pedido_creado',
                    'pedido_id' => $pedido->id,
                    'cliente' => $cliente->nombre,
                    'prendas' => $cantidadPrendas,
                    'epps' => $cantidadEpps,
                ],
            ]);

            \Log::info('[PEDIDO-REPOSITORY] Notificación creada', [
                'pedido_id' => $pedido->id,
                'usuario_id' => $usuarioId,
            ]);
        } catch (\Exception $e) {
            \Log::warning('[PEDIDO-REPOSITORY] Error creando News', [
                'pedido_id' => $pedido->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
