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
    public function findParcialById(int $id, bool $withRelations = false): ?ReciboPorPartes
    {
        $query = ReciboPorPartes::query();
        if ($withRelations) {
            $query->with(['pedido', 'prenda']);
        }

        return $query->find($id);
    }

    public function upsertCompletado(?int $idRecibo, ?int $idParcial, string $area, string $numeroRecibo, string $nombreOperario): void
    {
        $keys = $idParcial
            ? ['id_parcial' => $idParcial, 'area' => $area]
            : ['id_recibo' => (int) $idRecibo, 'area' => $area];

        DB::table('prenda_recibo_completado')->updateOrInsert(
            $keys,
            [
                'id_recibo' => $idParcial ? (int) $idParcial : (int) $idRecibo,
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

    public function findProcesoCosturaParcial(ReciboPorPartes $parcial): ?ProcesoPrenda
    {
        $numeroPedido = (int) ($parcial->pedido?->numero_pedido ?? 0);
        if ($numeroPedido <= 0) {
            return null;
        }

        return ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->first();
    }

    public function findParcialIdsForOriginal(ReciboPorPartes $parcial): array
    {
        return ReciboPorPartes::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->where('tipo_recibo', $parcial->tipo_recibo)
            ->where('consecutivo_original', $parcial->consecutivo_original)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
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

    public function findReciboOriginalActivoDesdeParcial(ReciboPorPartes $parcial): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_id', $parcial->prenda_pedido_id)
            ->where('tipo_recibo', $parcial->tipo_recibo)
            ->where('consecutivo_actual', $parcial->consecutivo_original)
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
