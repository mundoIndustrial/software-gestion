<?php

namespace App\Application\Services\Asesores;

use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Infrastructure\Repositories\ConsecutivosRecibosRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PrendaEdicionBloqueoService
{
    public function __construct(
        private readonly PedidoProduccionReadRepository $pedidoProduccionReadRepository,
        private readonly ConsecutivosRecibosRepository $consecutivosRecibosRepository,
    ) {
    }

    private const VALORES_CONSECUTIVO_PERMITIDOS = [
        'PENDIENTE_INSUMOS',
        'PENDIENTE_CARTERA',
        'PENDIENTE_SUPERVISOR',
        'DEVUELTO_ASESOR',
        'DEVUELTO_ASESORA',
        'DEVUELTO_A_ASESOR',
        'DEVUELTO_A_ASESORA',
    ];

    private const ESTADOS_PEDIDO_BLOQUEADOS = [
        'EN_EJECUCION',
        'ENTREGADO',
    ];

    private const ESTADOS_CONSECUTIVO_BLOQUEADOS = [
        'PENDIENTE_TELA',
        'PENDIENTE_PLOTTER',
        'PENDIENTE_PLOTER',
        'PENDIENTE_PLOOTER',
        'INSUMOS_PEDIDOS',
    ];
    private const TIPOS_PROCESO_BODEGA_BLOQUEO = [
        'BORDADO',
        'ESTAMPADO',
        'DTF',
        'SUBLIMADO',
        'REFLECTIVO',
    ];

    public function evaluar(int $pedidoId, int $prendaId): array
    {
        $estadoPedido = $this->obtenerEstadoPedido($pedidoId);
        if ($this->pedidoBloqueadoPorEstado($estadoPedido)) {
            return [
                'bloqueada' => true,
                'puede_editar' => false,
                'mensaje' => $this->mensajeBloqueoPorEstadoPedido('editar', $estadoPedido),
                'consecutivo' => null,
                'estado' => null,
                'area' => null,
                'estado_pedido' => $estadoPedido,
            ];
        }

        $registros = $this->obtenerRegistrosConsecutivo($pedidoId, $prendaId);
        if ($registros->isEmpty()) {
            $bloqueoBodegaProcesos = $this->resolverBloqueoBodegaProcesosAprobados($pedidoId, $prendaId);
            if ($bloqueoBodegaProcesos !== null) {
                return $bloqueoBodegaProcesos;
            }
            return $this->respuestaPermitida($estadoPedido);
        }

        $registroBloqueado = $this->resolverRegistroBloqueado($registros);
        if ($registroBloqueado !== null) {
            $area = $this->normalizarArea($registroBloqueado->area ?? null);
            $mensaje = $this->mensajeBloqueoInsumosPedidos($registroBloqueado->estado ?? null);

            Log::info('[PrendaEdicionBloqueo] BLOQUEADA_POR_ESTADO_CONSECUTIVO', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'estado' => $registroBloqueado->estado ?? null,
                'area' => $area,
                'consecutivo' => $registroBloqueado->consecutivo_actual ?? null,
            ]);

            return [
                'bloqueada' => true,
                'puede_editar' => false,
                'mensaje' => $mensaje,
                'consecutivo' => $registroBloqueado->consecutivo_actual,
                'estado' => $registroBloqueado->estado,
                'area' => $area,
                'estado_pedido' => $estadoPedido,
            ];
        }

        $bloqueoBodegaProcesos = $this->resolverBloqueoBodegaProcesosAprobados($pedidoId, $prendaId);
        if ($bloqueoBodegaProcesos !== null) {
            return $bloqueoBodegaProcesos;
        }

        if ($this->resolverRegistroPermitido($registros)) {
            Log::info('[PrendaEdicionBloqueo] PERMITIDA_POR_ESTADO_CONSECUTIVO', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
            ]);
            return $this->respuestaPermitida($estadoPedido);
        }

        $registroBloqueante = $registros->first();
        $area = $this->normalizarArea($registroBloqueante->area ?? null);

        return [
            'bloqueada' => true,
            'puede_editar' => false,
            'mensaje' => $this->mensajeBloqueo('editar', $area),
            'consecutivo' => $registroBloqueante->consecutivo_actual,
            'estado' => $registroBloqueante->estado,
            'area' => $area,
            'estado_pedido' => $estadoPedido,
        ];
    }

    public function mensajeBloqueo(string $accion, ?string $area): string
    {
        $accionNormalizada = trim($accion) !== '' ? trim($accion) : 'editar';
        $areaVisible = $area ?: 'produccion';
        return "Esta prenda se encuentra en {$areaVisible}, por ende no se puede {$accionNormalizada}. Comunicate con el lider de produccion.";
    }

    public function mensajeBloqueoPorEstadoPedido(string $accion, ?string $estadoPedido): string
    {
        $accionNormalizada = trim($accion) !== '' ? trim($accion) : 'editar';
        $estadoVisible = is_string($estadoPedido) && trim($estadoPedido) !== '' ? trim($estadoPedido) : 'estado no editable';
        return "Esta prenda no se puede {$accionNormalizada} porque el pedido se encuentra en estado {$estadoVisible}. Comunicate con el lider de produccion.";
    }

    public function mensajeBloqueoInsumosPedidos(?string $estado = null): string
    {
        $estadoVisible = $this->formatearEstadoVisible($estado);
        return "Esta prenda se encuentra en estado {$estadoVisible}, por ende no se puede editar ya que se pidieron los insumos de esa prenda. Comunicate con el lider de produccion.";
    }

    private function respuestaPermitida(?string $estadoPedido): array
    {
        return [
            'bloqueada' => false,
            'puede_editar' => true,
            'mensaje' => null,
            'consecutivo' => null,
            'estado' => null,
            'area' => null,
            'estado_pedido' => $estadoPedido,
        ];
    }

    private function obtenerRegistrosConsecutivo(int $pedidoId, int $prendaId): Collection
    {
        $registrosActivos = $this->consecutivosRecibosRepository->obtenerPorPrendaYPedido($prendaId, $pedidoId)
            ->values();

        if ($registrosActivos->isNotEmpty()) {
            return $registrosActivos;
        }

        return $this->consecutivosRecibosRepository->obtenerTodosPorPrenda($prendaId, $pedidoId)
            ->values();
    }

    private function resolverRegistroPermitido(Collection $registros): ?object
    {
        foreach ($registros as $registro) {
            $estadoNormalizado = $this->normalizarTexto((string) ($registro->estado ?? ''));

            if (in_array($estadoNormalizado, self::VALORES_CONSECUTIVO_PERMITIDOS, true)) {
                return $registro;
            }
        }

        return null;
    }

    private function resolverRegistroBloqueado(Collection $registros): ?object
    {
        foreach ($registros as $registro) {
            $estadoNormalizado = $this->normalizarTexto((string) ($registro->estado ?? ''));
            if ($this->esEstadoBloqueadoInsumos($estadoNormalizado)) {
                return $registro;
            }
        }

        return null;
    }

    private function obtenerEstadoPedido(int $pedidoId): ?string
    {
        $pedido = $this->pedidoProduccionReadRepository->obtenerPedidoPorId($pedidoId);
        $estado = is_array($pedido) ? ($pedido['estado'] ?? null) : null;

        return is_string($estado) && trim($estado) !== '' ? trim($estado) : null;
    }

    private function pedidoBloqueadoPorEstado(?string $estadoPedido): bool
    {
        if (!is_string($estadoPedido) || trim($estadoPedido) === '') {
            return false;
        }

        $normalizado = $this->normalizarTexto($estadoPedido);
        return in_array($normalizado, self::ESTADOS_PEDIDO_BLOQUEADOS, true);
    }

    private function normalizarArea(?string $area): ?string
    {
        if (!is_string($area)) {
            return null;
        }

        $valor = trim($area);
        return $valor !== '' ? $valor : null;
    }

    private function normalizarTexto(string $valor): string
    {
        $s = trim($valor);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $s = $ascii !== false ? $ascii : $s;
        $s = strtoupper($s);
        return str_replace(' ', '_', $s);
    }

    private function formatearEstadoVisible(?string $estado): string
    {
        $normalizado = $this->normalizarTexto((string) $estado);
        if ($normalizado === '') {
            return 'INDETERMINADO';
        }

        return ucwords(strtolower(str_replace('_', ' ', $normalizado)));
    }

    private function esEstadoBloqueadoInsumos(string $estadoNormalizado): bool
    {
        if ($estadoNormalizado === '') {
            return false;
        }

        if (in_array($estadoNormalizado, self::ESTADOS_CONSECUTIVO_BLOQUEADOS, true)) {
            return true;
        }

        return str_starts_with($estadoNormalizado, 'PENDIENTE_TELA')
            || str_starts_with($estadoNormalizado, 'PENDIENTE_PLOTTER')
            || str_starts_with($estadoNormalizado, 'PENDIENTE_PLOTER')
            || str_starts_with($estadoNormalizado, 'PENDIENTE_PLOOTER')
            || str_starts_with($estadoNormalizado, 'INSUMOS_PEDIDOS');
    }

    private function resolverBloqueoBodegaProcesosAprobados(int $pedidoId, int $prendaId): ?array
    {
        $prenda = DB::table('prendas_pedido')
            ->where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNull('deleted_at')
            ->select('id', 'de_bodega')
            ->first();

        if (!$prenda || !((int) ($prenda->de_bodega ?? 0) === 1)) {
            return null;
        }

        $procesoBloqueante = DB::table('pedidos_procesos_prenda_detalles as ppd')
            ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
            ->where('ppd.prenda_pedido_id', $prendaId)
            ->whereNull('ppd.deleted_at')
            ->whereRaw('UPPER(TRIM(ppd.estado)) = ?', ['APROBADO'])
            ->whereIn(DB::raw('UPPER(TRIM(tp.nombre))'), self::TIPOS_PROCESO_BODEGA_BLOQUEO)
            ->selectRaw('UPPER(TRIM(tp.nombre)) as tipo_proceso')
            ->first();

        if (!$procesoBloqueante) {
            return null;
        }

        $tipoProceso = strtoupper(trim((string) $procesoBloqueante->tipo_proceso));
        $consecutivo = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_id', $prendaId)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoProceso])
            ->whereNotNull('consecutivo_actual')
            ->orderByDesc('id')
            ->first();

        if (!$consecutivo) {
            return null;
        }

        $tipoVisible = ucwords(strtolower($tipoProceso));
        $mensaje = "Esta prenda de bodega ya tiene proceso {$tipoVisible} aprobado y consecutivo generado, por ende no se puede editar. Comunicate con el lider de produccion.";

        Log::info('[PrendaEdicionBloqueo] BLOQUEADA_POR_BODEGA_PROCESO_APROBADO', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'tipo_proceso' => $tipoProceso,
            'consecutivo' => $consecutivo->consecutivo_actual ?? null,
        ]);

        return [
            'bloqueada' => true,
            'puede_editar' => false,
            'mensaje' => $mensaje,
            'consecutivo' => $consecutivo->consecutivo_actual ?? null,
            'estado' => $consecutivo->estado ?? null,
            'area' => $this->normalizarArea($consecutivo->area ?? null),
            'estado_pedido' => $this->obtenerEstadoPedido($pedidoId),
        ];
    }
}
