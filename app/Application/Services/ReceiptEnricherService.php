<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;
use App\Models\ReciboPorPartes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReceiptEnricherService
 *
 * Responsabilidad: enriquecer recibos con informacion adicional.
 */
class ReceiptEnricherService
{
    public function __construct(
        private CantidadCalculator $cantidadCalculator
    ) {}

    /**
     * Enriquecer recibos con informacion de pedidos y calculos.
     *
     * @param array $recibos
     * @return array
     */
    public function enriquecer(array $recibos): array
    {
        if ($recibos === []) {
            return [];
        }

        $parcialesPorRecibo = $this->obtenerMapaParcialesPorRecibo($recibos);
        $metaParcialPorRecibo = $this->obtenerMapaMetaParcialPorRecibo($recibos);
        $cantidadesPorRecibo = $this->cantidadCalculator->calcularMasivo($recibos);

        $pedidoIds = collect($recibos)
            ->pluck('pedido_produccion_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $pedidosById = PedidoProduccion::with([
            'prendas.coloresTelas.tela',
            'prendas.coloresTelas.color',
            'prendas.tallas',
        ])
            ->whereIn('id', $pedidoIds)
            ->get()
            ->keyBy('id');

        $diasPorPedido = [];

        return array_map(function (array $recibo) use (
            $parcialesPorRecibo,
            $metaParcialPorRecibo,
            $cantidadesPorRecibo,
            $pedidosById,
            &$diasPorPedido
        ) {
            $pedidoId = (int) ($recibo['pedido_produccion_id'] ?? 0);
            $pedido = $pedidosById->get($pedidoId);

            $reciboKey = $this->buildReciboKey(
                $pedidoId,
                (int) ($recibo['prenda_id'] ?? 0),
                (string) ($recibo['tipo_recibo'] ?? ''),
                $this->normalizeConsecutivo($recibo['consecutivo_actual'] ?? '')
            );

            $totalParciales = (int) ($parcialesPorRecibo[$reciboKey] ?? 0);
            $metaParcial = $metaParcialPorRecibo[$reciboKey] ?? null;

            if ($pedido && !isset($diasPorPedido[$pedidoId])) {
                $diasPorPedido[$pedidoId] = $this->calcularDiasHabiles($pedido->created_at);
            }

            return array_merge($recibo, [
                'pedido_info' => $pedido ? $this->extraerInfoPedido($pedido) : null,
                'descripcion_detallada' => $this->generarDescripcion($pedido, $recibo),
                'dias_calculados' => (int) ($diasPorPedido[$pedidoId] ?? 0),
                'cantidad_total' => (int) ($cantidadesPorRecibo[$reciboKey] ?? 0),
                'tiene_parciales' => $totalParciales > 0,
                'total_parciales' => $totalParciales,
                'es_parcial' => (bool) ($metaParcial['es_parcial'] ?? false),
                'pedido_parcial_id' => $metaParcial['pedido_parcial_id'] ?? null,
            ]);
        }, $recibos);
    }

    /**
     * @param array $recibos
     * @return array<string, array{es_parcial: bool, pedido_parcial_id: ?int}>
     */
    private function obtenerMapaMetaParcialPorRecibo(array $recibos): array
    {
        $pedidoIds = [];
        $prendaIds = [];
        $tiposRecibo = [];
        $consecutivos = [];

        foreach ($recibos as $recibo) {
            $pedidoId = (int) ($recibo['pedido_produccion_id'] ?? 0);
            $prendaId = (int) ($recibo['prenda_id'] ?? 0);
            $tipoRecibo = trim((string) ($recibo['tipo_recibo'] ?? ''));
            $consecutivo = $this->normalizeConsecutivo($recibo['consecutivo_actual'] ?? '');

            if ($pedidoId <= 0 || $prendaId <= 0 || $tipoRecibo === '' || $consecutivo === '') {
                continue;
            }

            $pedidoIds[$pedidoId] = $pedidoId;
            $prendaIds[$prendaId] = $prendaId;
            $tiposRecibo[strtoupper($tipoRecibo)] = strtoupper($tipoRecibo);
            $consecutivos[$consecutivo] = $consecutivo;
        }

        if ($pedidoIds === [] || $prendaIds === [] || $tiposRecibo === [] || $consecutivos === []) {
            return [];
        }

        $rows = DB::table('pedidos_parciales')
            ->select([
                'id',
                'pedido_produccion_id',
                'prenda_pedido_id',
                'tipo_recibo',
                'consecutivo_actual',
            ])
            ->whereNotNull('consecutivo_actual')
            ->whereIn('pedido_produccion_id', array_values($pedidoIds))
            ->whereIn('prenda_pedido_id', array_values($prendaIds))
            ->whereIn(DB::raw('UPPER(tipo_recibo)'), array_values($tiposRecibo))
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $this->buildReciboKey(
                (int) $row->pedido_produccion_id,
                (int) $row->prenda_pedido_id,
                (string) $row->tipo_recibo,
                $this->normalizeConsecutivo($row->consecutivo_actual)
            );

            $map[$key] = [
                'es_parcial' => true,
                'pedido_parcial_id' => (int) $row->id,
            ];
        }

        return $map;
    }

    /**
     * Construye un mapa de cantidad de parciales por recibo original.
     *
     * @param array $recibos
     * @return array<string, int>
     */
    private function obtenerMapaParcialesPorRecibo(array $recibos): array
    {
        $pedidoIds = [];
        $prendaIds = [];
        $tiposRecibo = [];
        $consecutivos = [];

        foreach ($recibos as $recibo) {
            $pedidoId = (int) ($recibo['pedido_produccion_id'] ?? 0);
            $prendaId = (int) ($recibo['prenda_id'] ?? 0);
            $tipoRecibo = trim((string) ($recibo['tipo_recibo'] ?? ''));
            $consecutivo = $this->normalizeConsecutivo($recibo['consecutivo_actual'] ?? '');

            if ($pedidoId <= 0 || $prendaId <= 0 || $tipoRecibo === '' || $consecutivo === '') {
                continue;
            }

            $pedidoIds[$pedidoId] = $pedidoId;
            $prendaIds[$prendaId] = $prendaId;
            $tiposRecibo[$tipoRecibo] = $tipoRecibo;
            $consecutivos[$consecutivo] = $consecutivo;
        }

        if ($pedidoIds === [] || $prendaIds === [] || $tiposRecibo === [] || $consecutivos === []) {
            return [];
        }

        return ReciboPorPartes::query()
            ->select([
                'pedido_produccion_id',
                'prenda_pedido_id',
                'tipo_recibo',
                'consecutivo_original',
            ])
            ->whereIn('pedido_produccion_id', array_values($pedidoIds))
            ->whereIn('prenda_pedido_id', array_values($prendaIds))
            ->whereIn('tipo_recibo', array_values($tiposRecibo))
            ->whereIn('consecutivo_original', array_values($consecutivos))
            ->get()
            ->reduce(function (array $carry, ReciboPorPartes $parcial) {
                $key = $this->buildReciboKey(
                    (int) $parcial->pedido_produccion_id,
                    (int) $parcial->prenda_pedido_id,
                    (string) $parcial->tipo_recibo,
                    $this->normalizeConsecutivo($parcial->consecutivo_original)
                );

                $carry[$key] = (int) ($carry[$key] ?? 0) + 1;
                return $carry;
            }, []);
    }

    private function calcularDiasHabiles(?Carbon $fechaCreacion): int
    {
        if (!$fechaCreacion) {
            return 0;
        }

        $inicioConteo = $fechaCreacion->copy()->startOfDay()->addDay();
        $hoy = now()->startOfDay();

        if ($inicioConteo->gt($hoy)) {
            return 0;
        }

        $dias = 0;
        $fecha = $inicioConteo->copy();

        while ($fecha->lte($hoy)) {
            if ($fecha->isBusinessDay()) {
                $dias++;
            }
            $fecha->addDay();
        }

        return $dias;
    }

    private function buildReciboKey(int $pedidoId, int $prendaId, string $tipoRecibo, string $consecutivo): string
    {
        return implode('|', [
            $pedidoId,
            $prendaId,
            strtoupper(trim($tipoRecibo)),
            $this->normalizeConsecutivo($consecutivo),
        ]);
    }

    /**
     * Normaliza consecutivos para comparar enteros y decimales equivalentes.
     */
    private function normalizeConsecutivo(mixed $consecutivo): string
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

    /**
     * @param PedidoProduccion $pedido
     * @return array
     */
    private function extraerInfoPedido(PedidoProduccion $pedido): array
    {
        return [
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'estado' => $pedido->estado,
            'area' => $pedido->area,
            'dia_de_entrega' => $pedido->dia_de_entrega,
            'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega?->format('d/m/Y'),
            'fecha_creacion_orden' => $pedido->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param PedidoProduccion|null $pedido
     * @param array $recibo
     * @return string
     */
    private function generarDescripcion($pedido, $recibo): string
    {
        if (!$pedido || !isset($recibo['prenda_id'])) {
            return '';
        }

        $prenda = $pedido->prendas->where('id', $recibo['prenda_id'])->first();
        if (!$prenda) {
            return '';
        }

        $desc = 'PRENDA: ' . $prenda->nombre_prenda;

        if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
            $tela = $prenda->coloresTelas->first();
            $desc .= ' | TELA: ' . ($tela->tela->nombre ?? 'Sin tela');
            $desc .= ' | COLOR: ' . ($tela->color->nombre ?? 'Sin color');
        }

        return $desc;
    }
}
