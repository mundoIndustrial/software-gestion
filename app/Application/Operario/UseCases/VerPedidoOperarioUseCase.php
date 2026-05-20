<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Domain\Operario\Services\PedidoFotosReadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerPedidoOperarioUseCase
{
    public function __construct(
        private readonly PedidoProduccionOperarioReadRepository $pedidos,
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly PedidoFotosReadService $fotos,
    ) {}

    /**
     * @return array{status:int,view?:string,data?:array<string,mixed>,redirect_route?:string,redirect_error?:string}
     */
    public function execute(int $numeroPedido, Request $request): array
    {
        \Log::info('[VerPedidoOperarioUseCase] INICIO', [
            'numero_pedido' => $numeroPedido,
        ]);

        $usuario = Auth::user();

        $pedidoDB = null;
        $reciboId = (int) $request->query('recibo_id', 0);
        $tipoReciboRequest = (string) $request->query('tipo_recibo', 'COSTURA');
        $tipoReciboUpper = strtoupper(trim($tipoReciboRequest));
        $isBodegaByTipo = in_array($tipoReciboUpper, ['CORTE-PARA-BODEGA', 'BODEGA'], true);
        $isBodegaOnly = ($reciboId > 0) && (($numeroPedido === 0) || $isBodegaByTipo);

        \Log::info('[VerPedidoOperarioUseCase] Verificando tipo de pedido', [
            'numero_pedido' => $numeroPedido,
            'recibo_id' => $reciboId,
            'tipo_recibo' => $tipoReciboRequest,
            'is_bodega_only' => $isBodegaOnly,
        ]);

        if ($isBodegaOnly) {
            $reciboBodega = \App\Models\ConsecutivoReciboPedido::with(['prendaBodega'])->find($reciboId);
            
            \Log::info('[VerPedidoOperarioUseCase] Recibo de bodega encontrado?', [
                'encontrado' => !!$reciboBodega,
                'tipo_recibo' => $reciboBodega->tipo_recibo ?? 'N/A',
            ]);

            if ($reciboBodega && ($reciboBodega->tipo_recibo === 'CORTE-PARA-BODEGA' || $reciboBodega->tipo_recibo === 'BODEGA')) {
                // Crear un objeto pedido virtual
                $pedidoDB = (object)[
                    'id' => null,
                    'numero_pedido' => 'BODEGA',
                    'cliente' => 'SERVICIO',
                    'asesor_id' => 'SISTEMA',
                    'forma_de_pago' => 'N/A',
                    'estado' => $reciboBodega->estado ?? 'En Ejecución',
                    'created_at' => $reciboBodega->created_at,
                    'fecha_estimada' => null,
                    'descripcion' => $reciboBodega->prendaBodega?->descripcion ?? 'Recibo de Bodega',
                    'total_prendas' => 1,
                    'novedades' => $reciboBodega->notas ?? 'Sin novedades',
                    'nombre_prenda_bodega' => $reciboBodega->prendaBodega?->nombre ?? 'N/A'
                ];
            }
        } else {
            $pedidoDB = $this->pedidos->findByNumeroWithPrendas((int) $numeroPedido);
        }

        \Log::info('[VerPedidoOperarioUseCase] Búsqueda directa en BD finalizada', [
            'numero_pedido' => $numeroPedido,
            'encontrado_en_bd' => !!$pedidoDB,
            'pedido_id' => $pedidoDB->id ?? null,
        ]);

        if (!$pedidoDB) {
            \Log::warning('[VerPedidoOperarioUseCase] Pedido no encontrado en BD');
            return [
                'status' => 302,
                'redirect_route' => 'operario.dashboard',
                'redirect_error' => 'Pedido no encontrado',
            ];
        }

        $fotos = $this->fotos->obtenerFotosPedido((int) $numeroPedido);

        $prendaIdRequest = $request->query('prenda_id');
        $consecutivoParcialParam = $request->query('consecutivo_parcial');

        $numeroReciboCostura = null;

        if ($consecutivoParcialParam !== null && $consecutivoParcialParam !== '') {
            $numeroReciboCostura = (string) $consecutivoParcialParam;
        }

        if ($prendaIdRequest && $numeroReciboCostura === null) {
            $reciboEspecifico = $this->recibos->findActiveByPedidoPrendaTipo(
                pedidoProduccionId: (int) $pedidoDB->id,
                prendaId: (int) $prendaIdRequest,
                tipoRecibo: $tipoReciboRequest
            );

            if ($reciboEspecifico) {
                $numeroReciboCostura = $reciboEspecifico->consecutivo_actual;
            }
        }

        if (!$numeroReciboCostura) {
            $reciboCostura = $this->recibos->findFirstActiveByPedidoTipo(
                pedidoProduccionId: (int) $pedidoDB->id,
                tipoRecibo: $tipoReciboRequest
            );

            if ($reciboCostura) {
                $numeroReciboCostura = $reciboCostura->consecutivo_actual;
            }
        }

        \Log::info('[VerPedidoOperarioUseCase] Renderizando ver-pedido', [
            'numero_pedido' => $numeroPedido,
            'prenda_id_request' => $prendaIdRequest,
            'tipo_recibo_request' => $tipoReciboRequest,
            'total_fotos' => count($fotos),
            'numero_recibo_costura' => $numeroReciboCostura,
        ]);

        return [
            'status' => 200,
            'view' => 'operario.ver-pedido',
            'data' => [
                'operario' => null,
                'pedido' => [
                    'numero_pedido' => $pedidoDB->numero_pedido,
                    'numero_recibo_costura' => $numeroReciboCostura,
                    'prenda_id' => $prendaIdRequest,
                    'tipo_recibo' => $tipoReciboRequest,
                    'cliente' => $pedidoDB->cliente,
                    'asesor' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                    'asesora' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                    'forma_de_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                    'forma_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                    'estado' => $pedidoDB->estado ?? 'Pendiente',
                    'area' => 'Operarios',
                    'fecha_creacion' => $pedidoDB->created_at ? $pedidoDB->created_at->format('d/m/Y') : date('d/m/Y'),
                    'fecha_estimada' => $pedidoDB->fecha_estimada ? $pedidoDB->fecha_estimada->format('d/m/Y') : null,
                    'descripcion' => $pedidoDB->descripcion ?? 'N/A',
                    'descripcion_prendas' => $pedidoDB->descripcion ?? 'N/A',
                    'cantidad' => $pedidoDB->total_prendas ?? 0,
                    'novedades' => $pedidoDB->novedades ?? 'Sin novedades',
                ],
                'usuario' => $usuario,
                'fotos' => $fotos,
            ],
        ];
    }
}
