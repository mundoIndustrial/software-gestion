<?php

namespace App\Http\Controllers;

use App\Domain\Pedidos\Services\PedidoWebService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Test Controller para validar el flujo de creación de pedidos
 */
class TestPedidoWebServiceController extends Controller
{
    private PedidoWebService $pedidoWebService;

    public function __construct(PedidoWebService $pedidoWebService)
    {
        $this->pedidoWebService = $pedidoWebService;
    }

    /**
     * TEST 1: Verificar que PedidoWebService se puede instanciar
     */
    public function testInstancia()
    {
        try {
            $service = $this->pedidoWebService;
            return response()->json([
                'status' => 'success',
                'message' => 'PedidoWebService instanciado correctamente',
                'service' => get_class($service),
            ]);
        } catch (\Exception $e) {
            Log::error('[TEST] Error al instanciar PedidoWebService: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * TEST 2: Crear un pedido minimalista para verificar DB
     */
    public function testCrearPedidoMinimo()
    {
        try {
            $datosValidados = [
                'numero_cotizacion' => null,
                'cliente_id' => 1,
                'estado' => 'en_progreso',
                'asesor_id' => 1,
                'forma_de_pago' => 'contado',
                'dia_de_entrega' => '2026-02-24',
                'items' => [
                    [
                        'nombre_prenda' => '[TEST] Camiseta básica',
                        'cantidad' => 10,
                        'de_bodega' => false,
                        'cantidad_talla' => [
                            ['talla' => 'S', 'cantidad' => 5, 'genero' => 'UNISEX'],
                            ['talla' => 'M', 'cantidad' => 5, 'genero' => 'UNISEX'],
                        ],
                    ]
                ],
            ];

            $pedido = $this->pedidoWebService->crearPedidoCompleto($datosValidados, 1);

            Log::info('[TEST] Pedido creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pedido de prueba creado exitosamente',
                'pedido' => [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado' => $pedido->estado,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[TEST] Error al crear pedido minimalista: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * TEST 3: Verificar que todos los modelos se pueden usar
     */
    public function testModelos()
    {
        try {
            $modelos = [
                'PedidoProduccion' => \App\Models\PedidoProduccion::class,
                'PrendaPedido' => \App\Models\PrendaPedido::class,
                'PrendaPedidoTalla' => \App\Models\PrendaPedidoTalla::class,
                'PrendaPedidoVariante' => \App\Models\PrendaVariantePed::class,
                'PrendaPedidoColorTela' => \App\Models\PrendaPedidoColorTela::class,
                'PrendaFotoPedido' => \App\Models\PrendaFotoPedido::class,
                'PrendaFotoTelaPedido' => \App\Models\PrendaFotoTelaPedido::class,
                'PedidosProcesosPrendaDetalle' => \App\Models\PedidosProcesosPrendaDetalle::class,
                'PedidosProcesosPrendaTalla' => \App\Models\PedidosProcesosPrendaTalla::class,
                'PedidosProcessImagenes' => \App\Models\PedidosProcessImagenes::class,
                'PedidoEpp' => \App\Models\PedidoEpp::class,
                'PedidoEppImagen' => \App\Models\PedidoEppImagen::class,
            ];

            $resultados = [];
            foreach ($modelos as $nombre => $clase) {
                try {
                    if (class_exists($clase)) {
                        $resultados[$nombre] = [
                            'status' => 'ok',
                            'class' => $clase,
                            'table' => (new ($clase))->getTable(),
                        ];
                    } else {
                        $resultados[$nombre] = [
                            'status' => 'error',
                            'message' => "Clase no encontrada: $clase",
                        ];
                    }
                } catch (\Exception $e) {
                    $resultados[$nombre] = [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Verificación de modelos completada',
                'modelos' => $resultados,
            ]);
        } catch (\Exception $e) {
            Log::error('[TEST] Error al verificar modelos: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
