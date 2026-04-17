<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PrendaPedidoTallaRepository
 *
 * Responsabilidades:
 * - Acceso a datos de tallas de prendas
 * - Calculo de cantidades totales
 * - Soporta dos flujos: talla-color y normal
 */
class PrendaPedidoTallaRepository
{
    /**
     * Calcular cantidades para un lote de recibos sin consultar uno por uno.
     *
     * @param array $recibos
     * @return array<string, int> Mapa por key: pedido|prenda|tipo|consecutivo
     */
    public function calcularCantidadesPorRecibos(array $recibos): array
    {
        if ($recibos === []) {
            return [];
        }

        $prendaIds = [];
        $pedidoIds = [];
        $tiposRecibo = [];
        $consecutivos = [];
        $keysMeta = [];

        foreach ($recibos as $recibo) {
            $prendaId = (int) ($recibo['prenda_id'] ?? 0);
            $pedidoId = (int) ($recibo['pedido_produccion_id'] ?? 0);
            $tipoRecibo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
            $consecutivo = $this->normalizeConsecutivoKey($recibo['consecutivo_actual'] ?? '');

            if ($prendaId <= 0 || $pedidoId <= 0 || $tipoRecibo === '' || $consecutivo === '') {
                continue;
            }

            $key = $this->buildReciboKey($pedidoId, $prendaId, $tipoRecibo, $consecutivo);
            $keysMeta[$key] = [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'tipo_recibo' => $tipoRecibo,
                'consecutivo' => $consecutivo,
            ];

            $prendaIds[$prendaId] = $prendaId;
            $pedidoIds[$pedidoId] = $pedidoId;
            $tiposRecibo[$tipoRecibo] = $tipoRecibo;
            $consecutivos[$consecutivo] = $consecutivo;
        }

        if ($keysMeta === []) {
            return [];
        }

        $baseTotalesPorPrenda = $this->obtenerCantidadesBasePorPrenda(array_values($prendaIds));
        $cantidadesParcialesPorKey = $this->obtenerCantidadesParcialesPorKey(
            pedidoIds: array_values($pedidoIds),
            prendaIds: array_values($prendaIds),
            tiposRecibo: array_values($tiposRecibo),
            consecutivos: array_values($consecutivos)
        );

        $resultado = [];
        foreach ($keysMeta as $key => $meta) {
            $cantidadParcial = (int) ($cantidadesParcialesPorKey[$key] ?? 0);
            if ($cantidadParcial > 0) {
                $resultado[$key] = $cantidadParcial;
                continue;
            }

            $resultado[$key] = (int) ($baseTotalesPorPrenda[$meta['prenda_id']] ?? 0);
        }

        return $resultado;
    }

    /**
     * Calcular cantidad total de una prenda.
     *
     * @param int $prendaPedidoId ID de la prenda del pedido
     * @return int Cantidad total
     */
    public function calcularCantidadTotalPrenda(int $prendaPedidoId): int
    {
        try {
            $cantidadTallaColor = DB::table('prenda_pedido_talla_colores as pptc')
                ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                ->where('ppt.prenda_pedido_id', $prendaPedidoId)
                ->sum('pptc.cantidad');

            if ($cantidadTallaColor > 0) {
                return (int) $cantidadTallaColor;
            }

            $cantidadNormal = DB::table('prenda_pedido_tallas as ppt')
                ->where('ppt.prenda_pedido_id', $prendaPedidoId)
                ->sum('ppt.cantidad');

            return (int) $cantidadNormal;
        } catch (\Exception $e) {
            \Log::error('[PrendaPedidoTallaRepository] Error calculando cantidad de prenda: ' . $e->getMessage(), [
                'prenda_pedido_id' => $prendaPedidoId,
            ]);

            return 0;
        }
    }

    /**
     * Calcular cantidad para un recibo especifico (consecutivo_actual).
     *
     * Si existe un parcial con ese consecutivo, suma desde pedidos_parciales_tallas.
     * Si no existe parcial, devuelve la cantidad total de la prenda.
     *
     * @param int $prendaPedidoId
     * @param mixed $numeroRecibo
     * @param int|null $pedidoProduccionId
     * @param string|null $tipoRecibo
     * @return int
     */
    public function calcularCantidadPorRecibo(
        int $prendaPedidoId,
        mixed $numeroRecibo,
        ?int $pedidoProduccionId = null,
        ?string $tipoRecibo = null
    ): int {
        try {
            $numeroReciboNormalizado = $this->normalizarConsecutivo($numeroRecibo);

            $cantidadParcialesQuery = DB::table('pedidos_parciales_tallas as ppt')
                ->join('pedidos_parciales as pp', 'pp.id', '=', 'ppt.pedido_parcial_id')
                ->where('pp.prenda_pedido_id', $prendaPedidoId)
                ->whereRaw('CAST(pp.consecutivo_actual AS DECIMAL(10,2)) = ?', [$numeroReciboNormalizado]);

            if (!is_null($pedidoProduccionId) && $pedidoProduccionId > 0) {
                $cantidadParcialesQuery->where('pp.pedido_produccion_id', $pedidoProduccionId);
            }

            if (!empty($tipoRecibo)) {
                $cantidadParcialesQuery->whereRaw('UPPER(pp.tipo_recibo) = ?', [strtoupper(trim($tipoRecibo))]);
            }

            $cantidadParciales = (int) $cantidadParcialesQuery->sum('ppt.cantidad');

            if ($cantidadParciales > 0) {
                return $cantidadParciales;
            }

            return $this->calcularCantidadTotalPrenda($prendaPedidoId);
        } catch (\Exception $e) {
            \Log::error('[PrendaPedidoTallaRepository] Error calculando cantidad por recibo: ' . $e->getMessage(), [
                'prenda_pedido_id' => $prendaPedidoId,
                'numero_recibo' => $numeroRecibo,
                'pedido_produccion_id' => $pedidoProduccionId,
                'tipo_recibo' => $tipoRecibo,
            ]);

            return $this->calcularCantidadTotalPrenda($prendaPedidoId);
        }
    }

    /**
     * Obtener tallas con desglose por color para una prenda.
     *
     * @param int $prendaPedidoId
     * @return Collection de stdClass con campos: talla, color_nombre, cantidad
     */
    public function getTallasPorColor(int $prendaPedidoId): Collection
    {
        return DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaPedidoId)
            ->select([
                'ppt.talla',
                'pptc.color_nombre',
                'pptc.cantidad',
            ])
            ->get();
    }

    private function normalizarConsecutivo(mixed $numeroRecibo): string
    {
        $valor = trim((string) $numeroRecibo);
        if ($valor === '' || !is_numeric($valor)) {
            return '0.00';
        }

        return number_format((float) $valor, 2, '.', '');
    }

    /**
     * @param int[] $prendaIds
     * @return array<int, int>
     */
    private function obtenerCantidadesBasePorPrenda(array $prendaIds): array
    {
        if ($prendaIds === []) {
            return [];
        }

        $totalesTallaColor = DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->whereIn('ppt.prenda_pedido_id', $prendaIds)
            ->groupBy('ppt.prenda_pedido_id')
            ->selectRaw('ppt.prenda_pedido_id as prenda_id, SUM(pptc.cantidad) as total')
            ->pluck('total', 'prenda_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $totalesNormales = DB::table('prenda_pedido_tallas as ppt')
            ->whereIn('ppt.prenda_pedido_id', $prendaIds)
            ->groupBy('ppt.prenda_pedido_id')
            ->selectRaw('ppt.prenda_pedido_id as prenda_id, SUM(ppt.cantidad) as total')
            ->pluck('total', 'prenda_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $resultado = [];
        foreach ($prendaIds as $prendaId) {
            $tallaColor = (int) ($totalesTallaColor[$prendaId] ?? 0);
            if ($tallaColor > 0) {
                $resultado[$prendaId] = $tallaColor;
                continue;
            }

            $resultado[$prendaId] = (int) ($totalesNormales[$prendaId] ?? 0);
        }

        return $resultado;
    }

    /**
     * @param int[] $pedidoIds
     * @param int[] $prendaIds
     * @param string[] $tiposRecibo
     * @param string[] $consecutivos
     * @return array<string, int>
     */
    private function obtenerCantidadesParcialesPorKey(
        array $pedidoIds,
        array $prendaIds,
        array $tiposRecibo,
        array $consecutivos
    ): array {
        if ($pedidoIds === [] || $prendaIds === [] || $tiposRecibo === [] || $consecutivos === []) {
            return [];
        }

        $consecutivosDecimal = array_values(array_unique(array_map(
            fn ($c) => number_format((float) $c, 2, '.', ''),
            array_filter($consecutivos, fn ($c) => is_numeric($c))
        )));

        $rows = DB::table('pedidos_parciales_tallas as ppt')
            ->join('pedidos_parciales as pp', 'pp.id', '=', 'ppt.pedido_parcial_id')
            ->whereIn('pp.pedido_produccion_id', $pedidoIds)
            ->whereIn('pp.prenda_pedido_id', $prendaIds)
            ->whereIn(DB::raw('UPPER(pp.tipo_recibo)'), $tiposRecibo)
            ->when(
                $consecutivosDecimal !== [],
                fn ($q) => $q->whereIn(DB::raw('CAST(pp.consecutivo_actual AS DECIMAL(10,2))'), $consecutivosDecimal)
            )
            ->groupBy('pp.pedido_produccion_id', 'pp.prenda_pedido_id', 'pp.tipo_recibo', 'pp.consecutivo_actual')
            ->selectRaw('
                pp.pedido_produccion_id,
                pp.prenda_pedido_id,
                pp.tipo_recibo,
                pp.consecutivo_actual,
                SUM(ppt.cantidad) as cantidad_total
            ')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $this->buildReciboKey(
                (int) $row->pedido_produccion_id,
                (int) $row->prenda_pedido_id,
                strtoupper(trim((string) $row->tipo_recibo)),
                $this->normalizeConsecutivoKey($row->consecutivo_actual)
            );

            $map[$key] = (int) $row->cantidad_total;
        }

        return $map;
    }

    private function normalizeConsecutivoKey(mixed $consecutivo): string
    {
        $value = trim((string) $consecutivo);
        if ($value === '') {
            return '';
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;

            if (floor($numeric) === $numeric) {
                return (string) (int) $numeric;
            }

            return rtrim(rtrim(number_format($numeric, 2, '.', ''), '0'), '.');
        }

        return $value;
    }

    private function buildReciboKey(int $pedidoId, int $prendaId, string $tipoRecibo, string $consecutivo): string
    {
        return implode('|', [
            $pedidoId,
            $prendaId,
            strtoupper(trim($tipoRecibo)),
            $this->normalizeConsecutivoKey($consecutivo),
        ]);
    }
}
