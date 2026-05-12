<?php

namespace App\Infrastructure\Services\Operario;

use App\Domain\Operario\Services\ReciboOperarioWorkflow;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReciboOperarioWorkflowService implements ReciboOperarioWorkflow
{
    /**
     * @return \App\Models\ReciboPorPartes|\App\Models\PedidoParcial|null
     */
    public function findParcialById(int $id, bool $withRelations = false): ?object
    {
        // Primero buscar en ReciboPorPartes
        $query = \App\Models\ReciboPorPartes::query();
        if ($withRelations) {
            $query->with(['pedido', 'prenda']);
        }
        $parcial = $query->find($id);

        if ($parcial) {
            return $parcial;
        }

        // Si no se encuentra, buscar en PedidoParcial (anexos)
        $queryAnexo = \App\Models\PedidoParcial::query();
        if ($withRelations) {
            $queryAnexo->with(['pedido', 'prenda']);
        }
        
        return $queryAnexo->find($id);
    }

    public function upsertCompletado(?int $idRecibo, ?int $idParcial, string $area, string $numeroRecibo, string $nombreOperario): void
    {
        // Use id_recibo as the primary key for uniqueness (as per table constraint)
        $reciboPrincipal = $idParcial ?? $idRecibo;
        
        DB::table('prenda_recibo_completado')->updateOrInsert(
            [
                'id_recibo' => (int) $reciboPrincipal,
                'area' => $area
            ],
            [
                'id_parcial' => $idParcial,
                'numero_recibo' => $numeroRecibo,
                'nombre_operario' => $nombreOperario,
                'fecha_completado' => now(),
            ]
        );
    }

    public function deleteCompletadoByReciboAndArea(int $idRecibo, string $area): void
    {
        DB::table('prenda_recibo_completado')
            ->where('id_recibo', $idRecibo)
            ->where('area', $area)
            ->delete();
    }

    public function deleteCompletadoByParcialAndArea(int $idParcial, string $area): void
    {
        DB::table('prenda_recibo_completado')
            ->where('id_parcial', $idParcial)
            ->where('area', $area)
            ->delete();
    }

    public function runInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    public function findNumeroPedidoByPrendaId(int $prendaId): ?int
    {
        $prenda = PrendaPedido::query()
            ->where('id', $prendaId)
            ->with(['pedidoProduccion'])
            ->first();

        if (!$prenda || !$prenda->pedidoProduccion) {
            return null;
        }

        return (int) $prenda->pedidoProduccion->numero_pedido;
    }

    public function findProcesoCosturaParcial(object $parcial): ?ProcesoPrenda
    {
        $numeroPedido = (int) ($parcial->pedido?->numero_pedido ?? 0);
        if ($numeroPedido <= 0) {
            return null;
        }

        $query = ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->whereNull('deleted_at')
            ->latest('created_at');

        if ($parcial instanceof \App\Models\ReciboPorPartes) {
            $query->where('numero_recibo_parcial', $parcial->consecutivo_parcial);
        } else {
            // Es un PedidoParcial (anexo)
            $query->where('numero_recibo', $parcial->consecutivo_actual)
                ->where(function ($q) {
                    $q->whereNull('numero_recibo_parcial')
                      ->orWhere('numero_recibo_parcial', '')
                      ->orWhere('numero_recibo_parcial', 0);
                });
        }

        return $query->first();
    }

    public function findParcialIdsForOriginal(object $parcial): array
    {
        if ($parcial instanceof \App\Models\ReciboPorPartes) {
            return \App\Models\ReciboPorPartes::query()
                ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->where('tipo_recibo', $parcial->tipo_recibo)
                ->where('consecutivo_original', $parcial->consecutivo_original)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        // Para PedidoParcial (anexos), el concepto de "original" es diferente
        // Por ahora retornamos solo el ID propio ya que son registros independientes
        return [(int) $parcial->id];
    }

    public function countCompletadosParcialesByArea(array $parcialIds, string $area): int
    {
        if (empty($parcialIds)) {
            return 0;
        }

        return (int) DB::table('prenda_recibo_completado')
            ->where('area', $area)
            ->whereIn('id_parcial', $parcialIds)
            ->count();
    }

    public function findReciboOriginalActivoDesdeParcial(object $parcial): ?ConsecutivoReciboPedido
    {
        $consecutivoOriginal = ($parcial instanceof \App\Models\ReciboPorPartes) 
            ? $parcial->consecutivo_original 
            : $parcial->consecutivo_inicial;

        return ConsecutivoReciboPedido::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_id', $parcial->prenda_pedido_id)
            ->where('tipo_recibo', $parcial->tipo_recibo)
            ->where('consecutivo_actual', $consecutivoOriginal)
            ->where('activo', true)
            ->first();
    }

    public function findVistaCosturaUsers(): Collection
    {
        return User::query()
            ->get()
            ->filter(fn ($user) => $user->hasRole('vista-costura'))
            ->values();
    }
}
