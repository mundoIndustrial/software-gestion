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

    private const ESTADOS_PEDIDO_HABILITAN_EDICION = [
        'DEVUELTO_A_ASESORA',
        'DEVUELTO_ASESOR',
        'DEVUELTO_A_ASESOR',
        'DEVUELTO_ASESORA',
    ];

    private const ESTADOS_CONSECUTIVO_HABILITAN_EDICION = [
        'DEVUELTO_A_ASESORA',
        'DEVUELTO_A_ASESOR',
        'DEVUELTO_ASESORA',
        'DEVUELTO_ASESOR',
    ];
    private const ESTADO_PEDIDO_PENDIENTE_INSUMOS = 'PENDIENTE_INSUMOS';

    public function evaluar(int $pedidoId, int $prendaId): array
    {
        $estadoPedido = $this->obtenerEstadoPedido($pedidoId);

        $bloqueoPorReglasPedido = $this->resolverBloqueoReglasPedido($pedidoId, $prendaId, $estadoPedido);
        if ($bloqueoPorReglasPedido !== null) {
            return $bloqueoPorReglasPedido;
        }

        if ($this->pedidoBloqueadoPorEstado($estadoPedido)) {
            $estadoNormalizado = $this->normalizarTexto((string) $estadoPedido);

            if ($estadoNormalizado === 'EN_EJECUCION') {
                $prendaBodegaSinProcesos = $this->esPrendaBodegaSinProcesos($prendaId);
                if ($prendaBodegaSinProcesos) {
                    return null;
                }
            }

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

        // Validar consecutivos COSTURA primero (NUEVA LÓGICA)
        $bloqueoCostura = $this->validarConsecutivosCostura($pedidoId, $prendaId);
        if ($bloqueoCostura !== null) {
            return $bloqueoCostura;
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

    /**
     * Validar que los consecutivos de COSTURA tengan estado "DEVUELTO_ASESOR"
     * 
     * LÓGICA:
     * - Si NO hay consecutivos de COSTURA → Retorna null (permitir)
     * - Si hay consecutivos de COSTURA → AL MENOS UNO debe tener estado "DEVUELTO_ASESOR"
     * - Si NINGUNO tiene estado "DEVUELTO_ASESOR" → Retorna array bloqueador
     * 
     * @param int $pedidoId
     * @param int $prendaId
     * @return array|null
     */
    private function validarConsecutivosCostura(int $pedidoId, int $prendaId): ?array
    {
        $estadoPedido = $this->obtenerEstadoPedido($pedidoId);
        $estadoNormalizado = $this->normalizarTexto((string) $estadoPedido);
        if ($estadoNormalizado !== self::ESTADO_PEDIDO_PENDIENTE_INSUMOS) {
            return null;
        }

        // Obtener todos los consecutivos COSTURA para esta prenda
        $consecutivosCostura = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_id', $prendaId)
            ->where('tipo_recibo', 'COSTURA')
            ->whereNotNull('consecutivo_actual')
            ->get();

        // Si no hay consecutivos de costura, permitir edición
        if ($consecutivosCostura->isEmpty()) {
            return null;
        }

        // Verificar si AL MENOS UNO tiene estado "DEVUELTO_ASESOR"
        $tieneDevueltoAsesor = $consecutivosCostura->contains(function ($consecutivo) {
            $estado = $consecutivo->estado ?? '';
            return strtoupper(trim($estado)) === 'DEVUELTO_ASESOR';
        });

        // Si AL MENOS UNO tiene estado "DEVUELTO_ASESOR", permitir edición
        if ($tieneDevueltoAsesor) {
            return null;
        }

        // Si NINGUNO tiene estado "DEVUELTO_ASESOR", bloquear
        $primerConsecutivo = $consecutivosCostura->first();
        $area = $this->normalizarArea($primerConsecutivo->area ?? null);

        Log::info('[PrendaEdicionBloqueo] BLOQUEADA_POR_CONSECUTIVO_COSTURA_NO_DEVUELTO', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'consecutivo' => $primerConsecutivo->consecutivo_actual ?? null,
            'estado' => $primerConsecutivo->estado ?? null,
        ]);

        return [
            'bloqueada' => true,
            'puede_editar' => false,
            'mensaje' => 'El pedido ya fue aprobado por ende no se puede editar. Comuníquese con el líder de producción.',
            'consecutivo' => $primerConsecutivo->consecutivo_actual,
            'estado' => $primerConsecutivo->estado,
            'area' => $area,
            'estado_pedido' => $this->obtenerEstadoPedido($pedidoId),
        ];
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

        $procesoAsociado = DB::table('pedidos_procesos_prenda_detalles as ppd')
            ->where('ppd.prenda_pedido_id', $prendaId)
            ->whereNull('ppd.deleted_at')
            ->select('ppd.id', 'ppd.estado')
            ->first();

        if (!$procesoAsociado) {
            return null;
        }

        // Si el pedido está en estado DEVUELTO_A_ASESORA, permitir edición de prendas de bodega con procesos
        $estadoPedido = $this->obtenerEstadoPedido($pedidoId);
        $estadoNormalizado = $this->normalizarTexto((string) $estadoPedido);
        if ($estadoNormalizado === 'DEVUELTO_A_ASESORA') {
            return null;
        }

        $mensaje = "Esta prenda de bodega ya tiene procesos asociados, por ende no se puede editar. Comunicate con el lider de produccion.";

        Log::info('[PrendaEdicionBloqueo] BLOQUEADA_POR_BODEGA_TIENE_PROCESOS', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'estado_proceso' => $procesoAsociado->estado ?? null,
        ]);

        return [
            'bloqueada' => true,
            'puede_editar' => false,
            'mensaje' => $mensaje,
            'consecutivo' => null,
            'estado' => $procesoAsociado->estado ?? null,
            'area' => null,
            'estado_pedido' => $estadoPedido,
        ];
    }

    private function resolverBloqueoReglasPedido(int $pedidoId, int $prendaId, ?string $estadoPedido): ?array
    {
        $estadoNormalizado = $this->normalizarTexto((string) $estadoPedido);
        $estadoPermiteEdicion = in_array($estadoNormalizado, self::ESTADOS_PEDIDO_HABILITAN_EDICION, true);
        if ($estadoPermiteEdicion) {
            return null;
        }

        $prendaActual = DB::table('prendas_pedido')
            ->where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNull('deleted_at')
            ->select('de_bodega')
            ->first();

        $pedidoTienePrendasDeBodega = DB::table('prendas_pedido')
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNull('deleted_at')
            ->where('de_bodega', 1)
            ->exists();

        if ($pedidoTienePrendasDeBodega) {
            if ($this->prendaTieneConsecutivoDevueltoAsesor($pedidoId, $prendaId)) {
                return null;
            }

            if ($prendaActual && (int) ($prendaActual->de_bodega ?? 0) === 1) {
                $tieneProcesos = DB::table('pedidos_procesos_prenda_detalles')
                    ->where('prenda_pedido_id', $prendaId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$tieneProcesos) {
                    return null;
                }
            }

            $mensaje = 'Este pedido tiene al menos una prenda de bodega. Solo se pueden editar o eliminar prendas cuando el pedido este en DEVUELTO_A_ASESORA/DEVUELTO_ASESOR o cuando la prenda tenga consecutivo DEVUELTO.';

            Log::info('[PrendaEdicionBloqueo] BLOQUEADA_POR_PEDIDO_CON_PRENDAS_BODEGA', [
                'pedido_id' => $pedidoId,
                'estado_pedido' => $estadoPedido,
            ]);

            return [
                'bloqueada' => true,
                'puede_editar' => false,
                'mensaje' => $mensaje,
                'consecutivo' => null,
                'estado' => null,
                'area' => null,
                'estado_pedido' => $estadoPedido,
            ];
        }

        $esPendienteInsumos = $estadoNormalizado === self::ESTADO_PEDIDO_PENDIENTE_INSUMOS;
        if (!$esPendienteInsumos) {
            return null;
        }

        $pedidoTieneConsecutivosCostura = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->whereIn(DB::raw('UPPER(TRIM(tipo_recibo))'), ['COSTURA', 'COSTURA-BODEGA'])
            ->whereNotNull('consecutivo_actual')
            ->exists();

        if (!$pedidoTieneConsecutivosCostura) {
            return null;
        }

        if ($this->prendaTieneConsecutivoDevueltoAsesor($pedidoId, $prendaId)) {
            return null;
        }

        $mensaje = 'Este pedido ya tiene consecutivos de COSTURA generados en estado PENDIENTE_INSUMOS. Solo se pueden editar o eliminar prendas cuando el pedido este en DEVUELTO_A_ASESORA.';

        Log::info('[PrendaEdicionBloqueo] BLOQUEADA_POR_COSTURA_PENDIENTE_INSUMOS', [
            'pedido_id' => $pedidoId,
            'estado_pedido' => $estadoPedido,
        ]);

        return [
            'bloqueada' => true,
            'puede_editar' => false,
            'mensaje' => $mensaje,
            'consecutivo' => null,
            'estado' => null,
            'area' => null,
            'estado_pedido' => $estadoPedido,
        ];
    }

    private function prendaTieneConsecutivoDevueltoAsesor(int $pedidoId, int $prendaId): bool
    {
        $consecutivos = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_id', $prendaId)
            ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA'])
            ->whereNotNull('consecutivo_actual')
            ->get(['estado']);

        if ($consecutivos->isEmpty()) {
            return false;
        }

        foreach ($consecutivos as $consecutivo) {
            $estadoNormalizado = $this->normalizarTexto((string) ($consecutivo->estado ?? ''));
            if (in_array($estadoNormalizado, self::ESTADOS_CONSECUTIVO_HABILITAN_EDICION, true)) {
                return true;
            }
        }

        return false;
    }

    private function esPrendaBodegaSinProcesos(int $prendaId): bool
    {
        $prenda = DB::table('prendas_pedido')
            ->where('id', $prendaId)
            ->whereNull('deleted_at')
            ->select('de_bodega')
            ->first();

        if (!$prenda || (int) ($prenda->de_bodega ?? 0) !== 1) {
            return false;
        }

        $tieneProcesos = DB::table('pedidos_procesos_prenda_detalles')
            ->where('prenda_pedido_id', $prendaId)
            ->whereNull('deleted_at')
            ->exists();

        return !$tieneProcesos;
    }
}
