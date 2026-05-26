<?php

namespace App\Infrastructure\Http\Controllers\Lavanderia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class LavanderiaController extends Controller
{
    /**
     * Mostrar el dashboard principal de lavandería
     */
    public function index(): View
    {
        return view('lavanderia.index');
    }

    /**
     * Buscar recibos por número
     * Retorna recibos de tipo COSTURA o CORTE-PARA-BODEGA
     */
    public function searchRecibos(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa al menos 2 caracteres'
            ]);
        }

        try {
            // Buscar en ConsecutivoReciboPedido
            $recibos = \App\Models\ConsecutivoReciboPedido::where('consecutivo_actual', 'LIKE', "%{$query}%")
                ->whereIn('tipo_recibo', ['COSTURA', 'CORTE-PARA-BODEGA'])
                ->with(['pedido.cliente', 'prenda.tallas'])
                ->limit(10)
                ->get();

            $resultado = $recibos->map(function ($recibo) {
                // Obtener cliente del pedido
                $clienteNombre = 'Sin cliente';
                if ($recibo->pedido && $recibo->pedido->cliente) {
                    $clienteNombre = $recibo->pedido->cliente->nombre ?? $recibo->pedido->cliente;
                } elseif ($recibo->pedido) {
                    $clienteNombre = $recibo->pedido->cliente ?? 'Sin cliente';
                }

                // Obtener prenda
                $prendaNombre = 'Sin prenda';
                if ($recibo->prenda) {
                    $prendaNombre = $recibo->prenda->nombre_prenda ?? 'Sin prenda';
                }

                // Obtener tallas reales de la prenda
                $tallas = [];
                if ($recibo->prenda && $recibo->prenda->tallas) {
                    $tallas = $recibo->prenda->tallas->map(function ($talla) {
                        return [
                            'id' => $talla->id,
                            'talla' => $talla->talla,
                            'genero' => $talla->genero,
                            'cantidad' => $talla->obtenerCantidadTotal(),
                            'tipo_talla' => $talla->tipo_talla
                        ];
                    })->toArray();
                }

                return [
                    'id' => $recibo->id,
                    'numero_recibo' => $recibo->consecutivo_actual ?? $recibo->id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'cliente' => $clienteNombre,
                    'prenda' => $prendaNombre,
                    'descripcion' => $recibo->prenda?->descripcion ?? '',
                    'cantidad_total' => $tallas ? array_sum(array_column($tallas, 'cantidad')) : 0,
                    'tallas' => $tallas
                ];
            });

            if ($resultado->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No se encontraron recibos'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en searchRecibos:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar recibos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos de lavandería
     */
    public function getMovimientos(): JsonResponse
    {
        try {
            $movimientos = \App\Models\LavanderiaMovimiento::with([
                'consecutivoRecibo.pedido',
                'consecutivoRecibo.prenda',
                'tallas'
            ])
            ->orderBy('fecha_salida', 'desc')
            ->get()
            ->map(function ($movimiento) {
                // Obtener cliente del pedido de producción
                $cliente = 'Sin cliente';
                if ($movimiento->consecutivoRecibo && $movimiento->consecutivoRecibo->pedido) {
                    $cliente = $movimiento->consecutivoRecibo->pedido->cliente ?? 'Sin cliente';
                }

                return [
                    'id' => $movimiento->id,
                    'recibo' => $movimiento->numero_recibo,
                    'cliente' => $cliente,
                    'prenda' => $movimiento->consecutivoRecibo?->prenda?->nombre_prenda ?? 'Sin prenda',
                    'estado' => $movimiento->estado,
                    'fechaSalida' => $movimiento->fecha_salida?->format('Y-m-d H:i') ?? '-',
                    'fechaLlegada' => $movimiento->fecha_llegada?->format('Y-m-d H:i') ?? '-',
                    'tallas' => $movimiento->tallas->map(function ($talla) {
                        return [
                            'talla' => $talla->talla,
                            'genero' => $talla->genero,
                            'cantidad_enviada' => $talla->cantidad_enviada,
                            'cantidad_recibida' => $talla->cantidad_recibida,
                        ];
                    })->toArray()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $movimientos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getMovimientos:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar salida de lavandería
     */
    public function registrarSalida(Request $request): JsonResponse
    {
        \Log::info('Iniciando registrarSalida', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            // Obtener datos sin validación estricta primero
            $data = $request->all();
            
            \Log::info('Datos recibidos:', $data);

            // Validación básica
            if (empty($data['recibo_id']) || empty($data['numero_recibo']) || empty($data['tipo_recibo']) || empty($data['tallas'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan campos requeridos',
                    'received' => $data
                ], 422);
            }

            // Crear movimiento
            $movimiento = \App\Models\LavanderiaMovimiento::create([
                'consecutivo_recibo_pedido_id' => (int)$data['recibo_id'],
                'numero_recibo' => (int)$data['numero_recibo'],
                'tipo_recibo' => $data['tipo_recibo'],
                'fecha_salida' => now(),
                'firma_salida' => 'pendiente',
                'estado' => 'PENDIENTE'
            ]);

            \Log::info('Movimiento creado:', ['id' => $movimiento->id]);

            // Crear registros de tallas
            foreach ($data['tallas'] as $talla) {
                \App\Models\LavanderiaMovimientoTalla::create([
                    'lavanderia_movimiento_id' => $movimiento->id,
                    'talla' => $talla['talla'],
                    'genero' => $talla['genero'] ?? null,
                    'color' => null,
                    'cantidad_enviada' => (int)$talla['cantidad_enviada'],
                    'cantidad_recibida' => 0
                ]);
            }

            \Log::info('Salida registrada exitosamente', ['movimiento_id' => $movimiento->id]);

            return response()->json([
                'success' => true,
                'message' => 'Salida registrada exitosamente',
                'data' => [
                    'id' => $movimiento->id,
                    'numero_recibo' => $movimiento->numero_recibo
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en registrarSalida:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar salida: ' . $e->getMessage()
            ], 500);
        }
    }
}
