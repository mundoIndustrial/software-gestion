<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\ReciboNotificacionesRepository;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReciboNotificacionesRepositoryImpl implements ReciboNotificacionesRepository
{
    public function listarNoVistas(
        int $userId,
        string $tipoRecibo,
        int $limit,
        ?\DateTimeInterface $since,
        ?string $areaFiltro,
        ?string $encargadoNormalizado,
        bool $soloAsignadosAlEncargado
    ): Collection {
        $tipoRecibo = strtoupper(trim($tipoRecibo));
        $limit = max(1, min((int) $limit, 200));

        $query = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->where('tipo_recibo', $tipoRecibo)
            ->whereNotIn('id', function ($sub) use ($userId, $tipoRecibo) {
                $sub->select('consecutivo_recibo_id')
                    ->from('recibos_usuario_vistos')
                    ->where('user_id', (int) $userId)
                    ->where('tipo_recibo', $tipoRecibo);
            })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->with(['pedido:id,numero_pedido,cliente']);

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        if ($areaFiltro !== null && $areaFiltro !== '') {
            $query->where('area', $areaFiltro);
        }

        if ($soloAsignadosAlEncargado) {
            $encargadoNormalizado = strtolower(trim((string) $encargadoNormalizado));
            if ($encargadoNormalizado === '') {
                $query->whereRaw('1 = 0');
            } else {
                $proceso = strtolower(trim((string) $areaFiltro));
                if ($proceso === 'corte' || $proceso === 'costura') {
                    $query->whereExists(function ($sub) use ($encargadoNormalizado, $proceso) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as pp')
                            ->join('pedidos_produccion as ped', 'ped.numero_pedido', '=', 'pp.numero_pedido')
                            ->whereRaw('LOWER(TRIM(pp.proceso)) = ?', [$proceso])
                            ->whereRaw('LOWER(TRIM(pp.encargado)) = ?', [$encargadoNormalizado])
                            ->whereColumn('ped.id', 'consecutivos_recibos_pedidos.pedido_produccion_id')
                            ->whereColumn('pp.prenda_pedido_id', 'consecutivos_recibos_pedidos.prenda_id')
                            ->whereNull('pp.deleted_at');
                    });
                }
            }
        }

        return $query->get(['id', 'pedido_produccion_id', 'tipo_recibo', 'consecutivo_actual', 'created_at', 'updated_at']);
    }

    public function existeRecibo(int $reciboId, string $tipoRecibo): bool
    {
        $tipoRecibo = strtoupper(trim($tipoRecibo));

        return ConsecutivoReciboPedido::query()
            ->where('id', (int) $reciboId)
            ->where('tipo_recibo', $tipoRecibo)
            ->exists();
    }

    public function marcarLeida(int $userId, int $reciboId, string $tipoRecibo, \DateTimeInterface $fecha): void
    {
        $tipoRecibo = strtoupper(trim($tipoRecibo));

        DB::table('recibos_usuario_vistos')->insertOrIgnore([
            'consecutivo_recibo_id' => (int) $reciboId,
            'user_id' => (int) $userId,
            'tipo_recibo' => $tipoRecibo,
            'created_at' => $fecha,
        ]);
    }

    public function marcarTodasLeidas(
        int $userId,
        string $tipoRecibo,
        ?string $areaFiltro,
        ?string $encargadoNormalizado,
        bool $soloAsignadosAlEncargado,
        \DateTimeInterface $fecha
    ): int {
        $tipoRecibo = strtoupper(trim($tipoRecibo));

        $query = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->where('tipo_recibo', $tipoRecibo)
            ->whereNotIn('id', function ($sub) use ($userId, $tipoRecibo) {
                $sub->select('consecutivo_recibo_id')
                    ->from('recibos_usuario_vistos')
                    ->where('user_id', (int) $userId)
                    ->where('tipo_recibo', $tipoRecibo);
            });

        if ($areaFiltro !== null && $areaFiltro !== '') {
            $query->where('area', $areaFiltro);
        }

        if ($soloAsignadosAlEncargado) {
            $encargadoNormalizado = strtolower(trim((string) $encargadoNormalizado));
            if ($encargadoNormalizado === '') {
                $query->whereRaw('1 = 0');
            } else {
                $proceso = strtolower(trim((string) $areaFiltro));
                if ($proceso === 'corte' || $proceso === 'costura') {
                    $query->whereExists(function ($sub) use ($encargadoNormalizado, $proceso) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as pp')
                            ->join('pedidos_produccion as ped', 'ped.numero_pedido', '=', 'pp.numero_pedido')
                            ->whereRaw('LOWER(TRIM(pp.proceso)) = ?', [$proceso])
                            ->whereRaw('LOWER(TRIM(pp.encargado)) = ?', [$encargadoNormalizado])
                            ->whereColumn('ped.id', 'consecutivos_recibos_pedidos.pedido_produccion_id')
                            ->whereColumn('pp.prenda_pedido_id', 'consecutivos_recibos_pedidos.prenda_id')
                            ->whereNull('pp.deleted_at');
                    });
                }
            }
        }

        $ids = $query->pluck('id')->map(fn ($v) => (int) $v)->all();

        if (!empty($ids)) {
            $rows = array_map(function ($reciboId) use ($userId, $tipoRecibo, $fecha) {
                return [
                    'consecutivo_recibo_id' => (int) $reciboId,
                    'user_id' => (int) $userId,
                    'tipo_recibo' => $tipoRecibo,
                    'created_at' => $fecha,
                ];
            }, $ids);

            DB::table('recibos_usuario_vistos')->insertOrIgnore($rows);
        }

        return count($ids);
    }
}

