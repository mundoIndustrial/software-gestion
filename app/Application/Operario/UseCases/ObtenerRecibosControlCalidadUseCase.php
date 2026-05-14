<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Domain\Pedidos\Repositories\PrendaPedidoReadRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ObtenerRecibosControlCalidadUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $reciboRepository,
        private readonly ReciboDistribucionReadRepository $distribucionRepository,
        private readonly PedidoProduccionOperarioReadRepository $pedidoRepository,
        private readonly PrendaPedidoReadRepository $prendaRepository,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(string $tipoRecibo): array
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->hasRole('vista-costura')) {
            \Log::warning('[ObtenerRecibosControlCalidadUseCase] Acceso denegado', [
                'usuario_id' => $usuario?->id,
                'tipo_recibo' => $tipoRecibo,
            ]);

            return [
                'status' => 403,
                'payload' => [
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta información',
                ],
            ];
        }

        try {
            $tipoRecibo = strtoupper(trim((string) $tipoRecibo));

            \Log::info('[ObtenerRecibosControlCalidadUseCase] Iniciando búsqueda', [
                'tipo_recibo_solicitado' => $tipoRecibo,
                'usuario_id' => $usuario->id,
            ]);

            $recibosEnCC = [];

            // Fuente de verdad de "completado":
            // prenda_recibo_completado (área Control Calidad / Control de Calidad)
            // No depender de procesos_prenda para decidir visibilidad.

            // ==================== PARTE 1: RECIBOS NORMALES COMPLETADOS ====================
            $recibosNormales = DB::table('prenda_recibo_completado as prc')
                ->join('consecutivos_recibos_pedidos as crp', 'prc.id_recibo', '=', 'crp.id')
                ->join('pedidos_produccion as pp', 'crp.pedido_produccion_id', '=', 'pp.id')
                ->whereNull('prc.id_parcial')
                ->whereRaw('LOWER(TRIM(prc.area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->where('crp.tipo_recibo', $tipoRecibo)
                ->where('crp.activo', 1)
                ->select(
                    'crp.id',
                    'crp.pedido_produccion_id',
                    'pp.numero_pedido',
                    'crp.prenda_id',
                    'crp.tipo_recibo',
                    'crp.consecutivo_actual',
                    'crp.notas',
                    'pp.cliente'
                )
                ->distinct()
                ->get();

            \Log::info('[ObtenerRecibosControlCalidadUseCase] Recibos normales encontrados', [
                'tipo_recibo' => $tipoRecibo,
                'total_recibos_normales' => $recibosNormales->count(),
            ]);

            foreach ($recibosNormales as $reciboNormal) {
                $prenda = $this->prendaRepository->obtenerPorId((int) $reciboNormal->prenda_id);

                if ($prenda) {
                    $parcialId = null;
                    $notas = isset($reciboNormal->notas) ? (string) $reciboNormal->notas : '';
                    if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                        $parcialId = (int) $matches[1];
                    }
                    $esParcial = $parcialId !== null;

                    $recibosEnCC[] = [
                        'id' => $reciboNormal->id,
                        'pedido_produccion_id' => (int) $reciboNormal->pedido_produccion_id,
                        'numero_pedido' => $reciboNormal->numero_pedido,
                        'prenda_id' => (int) $reciboNormal->prenda_id,
                        'nombre_prenda' => $prenda->nombre_prenda,
                        'cliente' => $reciboNormal->cliente,
                        'tipo_recibo' => $reciboNormal->tipo_recibo,
                        'consecutivo_actual' => $reciboNormal->consecutivo_actual,
                        'es_parcial' => $esParcial,
                        'parcial_id' => $parcialId,
                        'tiene_parciales' => false,
                    ];

                    \Log::debug('[ObtenerRecibosControlCalidadUseCase] Recibo normal agregado', [
                        'recibo_id' => $reciboNormal->id,
                        'pedido_numero' => $reciboNormal->numero_pedido,
                        'prenda_nombre' => $prenda->nombre_prenda,
                    ]);
                }
            }

            // ==================== PARTE 2: PARCIALES COMPLETADOS ====================
            $parciales = DB::table('prenda_recibo_completado as prc')
                ->join('recibo_por_partes as rbp', 'prc.id_parcial', '=', 'rbp.id')
                ->join('pedidos_produccion as pedprod', 'rbp.pedido_produccion_id', '=', 'pedprod.id')
                ->whereNotNull('prc.id_parcial')
                ->whereRaw('LOWER(TRIM(prc.area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->where('rbp.tipo_recibo', $tipoRecibo)
                ->select(
                    'rbp.id as recibo_id',
                    'pedprod.id as pedido_produccion_id',
                    'pedprod.numero_pedido',
                    'rbp.prenda_pedido_id as prenda_id',
                    'rbp.tipo_recibo',
                    'rbp.consecutivo_parcial as consecutivo_actual',
                    'pedprod.cliente',
                    'rbp.id as parcial_id',
                    'rbp.consecutivo_original'
                )
                ->distinct()
                ->get();

            \Log::info('[ObtenerRecibosControlCalidadUseCase] Parciales encontrados', [
                'tipo_recibo' => $tipoRecibo,
                'total_parciales' => $parciales->count(),
            ]);

            foreach ($parciales as $parcial) {
                $prenda = $this->prendaRepository->obtenerPorId((int) $parcial->prenda_id);

                if ($prenda) {
                    $recibosEnCC[] = [
                        'id' => $parcial->recibo_id,
                        'pedido_produccion_id' => (int) $parcial->pedido_produccion_id,
                        'numero_pedido' => $parcial->numero_pedido,
                        'prenda_id' => (int) $parcial->prenda_id,
                        'nombre_prenda' => $prenda->nombre_prenda,
                        'cliente' => $parcial->cliente,
                        'tipo_recibo' => $parcial->tipo_recibo,
                        'consecutivo_actual' => $parcial->consecutivo_actual,
                        'es_parcial' => true,
                        'parcial_id' => (int) $parcial->parcial_id,
                        'consecutivo_original' => $parcial->consecutivo_original,
                        'tiene_parciales' => false,
                    ];

                    \Log::debug('[ObtenerRecibosControlCalidadUseCase] Parcial agregado', [
                        'parcial_id' => $parcial->parcial_id,
                        'pedido_numero' => $parcial->numero_pedido,
                        'prenda_nombre' => $prenda->nombre_prenda,
                    ]);
                }
            }

            \Log::info('[ObtenerRecibosControlCalidadUseCase] Búsqueda completada', [
                'tipo_recibo' => $tipoRecibo,
                'total_recibos_normales' => $recibosNormales->count(),
                'total_parciales' => $parciales->count(),
                'total_recibos_mostrados' => count($recibosEnCC),
            ]);

            if (empty($recibosEnCC)) {
                return [
                    'status' => 200,
                    'payload' => [
                        'success' => true,
                        'data' => [],
                        'total' => 0,
                        'mensaje' => 'No hay recibos en Control de Calidad',
                    ],
                ];
            }

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'data' => $recibosEnCC,
                    'total' => count($recibosEnCC),
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('[ObtenerRecibosControlCalidadUseCase] Error obteniendo recibos en CC', [
                'tipo_recibo' => $tipoRecibo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'usuario_id' => $usuario->id,
            ]);

            return [
                'status' => 500,
                'payload' => [
                    'success' => false,
                    'message' => 'Error al obtener recibos: ' . $e->getMessage(),
                ],
            ];
        }
    }
}
