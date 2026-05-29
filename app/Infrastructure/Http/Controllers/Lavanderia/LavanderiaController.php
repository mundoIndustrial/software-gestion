<?php

namespace App\Infrastructure\Http\Controllers\Lavanderia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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

        if (strlen($query) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa al menos 1 carácter'
            ]);
        }

        try {
            // Buscar en ConsecutivoReciboPedido
            $recibos = \App\Models\ConsecutivoReciboPedido::where('consecutivo_actual', 'LIKE', "%{$query}%")
                ->whereIn('tipo_recibo', ['COSTURA', 'CORTE-PARA-BODEGA'])
                ->with(['pedido.cliente', 'prenda.tallas', 'prendaBodega.tallas'])
                ->orderBy('consecutivo_actual', 'asc')
                ->orderBy('tipo_recibo', 'asc')
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

                // Obtener prenda según el tipo de recibo
                $prendaNombre = 'Sin prenda';
                $tallas = [];

                if ($recibo->tipo_recibo === 'CORTE-PARA-BODEGA') {
                    // Para recibos de CORTE-PARA-BODEGA, usar prendaBodega
                    if ($recibo->prendaBodega) {
                        $prendaNombre = $recibo->prendaBodega->nombre ?? 'Sin prenda';
                        
                        // Obtener tallas de prenda_tallas_bodega
                        if ($recibo->prendaBodega->tallas) {
                            $tallas = $recibo->prendaBodega->tallas->map(function ($talla) {
                                return [
                                    'id' => $talla->id,
                                    'talla' => $talla->talla ?? 'Cantidad',
                                    'genero' => $talla->genero,
                                    'cantidad' => $talla->cantidad,
                                    'tipo_talla' => 'bodega'
                                ];
                            })->toArray();
                        }
                    }
                } else {
                    // Para recibos de COSTURA, usar prenda normal
                    if ($recibo->prenda) {
                        $prendaNombre = $recibo->prenda->nombre_prenda ?? 'Sin prenda';
                        
                        // Obtener tallas reales de la prenda
                        if ($recibo->prenda->tallas) {
                            $tallas = $recibo->prenda->tallas->map(function ($talla) {
                                return [
                                    'id' => $talla->id,
                                    'talla' => $talla->talla ?? 'Cantidad',
                                    'genero' => $talla->genero,
                                    'cantidad' => $talla->obtenerCantidadTotal(),
                                    'tipo_talla' => 'normal'
                                ];
                            })->toArray();
                        }
                    }
                }

                return [
                    'id' => $recibo->id,
                    'numero_recibo' => $recibo->consecutivo_actual ?? $recibo->id,
                    'tipo_recibo' => $recibo->tipo_recibo === 'CORTE-PARA-BODEGA' ? 'BODEGA' : $recibo->tipo_recibo,
                    'tipo_recibo_original' => $recibo->tipo_recibo,
                    'cliente' => $clienteNombre,
                    'prenda' => $prendaNombre,
                    'descripcion' => $recibo->prenda?->descripcion ?? $recibo->prendaBodega?->descripcion ?? '',
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
                'recibos.recibo.pedido',
                'recibos.recibo.prenda',
                'recibos.recibo.prendaBodega',
                'tallas.prenda',
                'tallas.prendaBodega'
            ])
            ->orderBy('fecha_movimiento', 'desc')
            ->get()
            ->map(function ($movimiento) {
                // Obtener información de los recibos
                $recibosInfo = $movimiento->recibos->map(function ($reciboMovimiento) {
                    $recibo = $reciboMovimiento->recibo;
                    
                    // Obtener cliente del pedido
                    $cliente = 'Sin cliente';
                    if ($recibo && $recibo->pedido) {
                        $cliente = $recibo->pedido->cliente ?? 'Sin cliente';
                    }

                    // Obtener prenda según el tipo de recibo
                    $prenda = 'Sin prenda';
                    if ($reciboMovimiento->tipo_recibo === 'CORTE-PARA-BODEGA') {
                        $prenda = $recibo?->prendaBodega?->nombre ?? 'Sin prenda';
                    } else {
                        $prenda = $recibo?->prenda?->nombre_prenda ?? 'Sin prenda';
                    }

                    return [
                        'id' => $reciboMovimiento->id,
                        'recibo_id' => $reciboMovimiento->consecutivo_recibo_pedido_id,
                        'numero_recibo' => $reciboMovimiento->numero_recibo,
                        'tipo_recibo' => $reciboMovimiento->tipo_recibo,
                        'tipo_recibo_mostrar' => $reciboMovimiento->tipo_recibo === 'CORTE-PARA-BODEGA' ? 'BODEGA' : $reciboMovimiento->tipo_recibo,
                        'cliente' => $cliente,
                        'prenda' => $prenda,
                    ];
                })->toArray();

                // Agrupar tallas por género
                $tallasPorGenero = [];
                foreach ($movimiento->tallas as $talla) {
                    $genero = $talla->genero ?? 'Sin género';
                    $key = $genero;

                    if (!isset($tallasPorGenero[$key])) {
                        $tallasPorGenero[$key] = [
                            'genero' => $genero,
                            'tallas' => []
                        ];
                    }

                    $tallasPorGenero[$key]['tallas'][] = [
                        'talla' => $talla->talla,
                        'cantidad_enviada' => $talla->cantidad_enviada,
                        'cantidad_recibida' => $talla->cantidad_recibida,
                    ];
                }

                // Determinar estado de firma
                $estadoFirma = 'PENDIENTE FIRMA';
                if ($movimiento->firma_movimiento && $movimiento->firma_movimiento !== 'pendiente') {
                    $estadoFirma = 'FIRMADO';
                }

                return [
                    'id' => $movimiento->id,
                    'recibos' => $recibosInfo,
                    'estado' => $movimiento->estado,
                    'estadoFirma' => $estadoFirma,
                    'tipoMovimiento' => $movimiento->tipo_movimiento,
                    'novedad' => $movimiento->novedad,
                    'fechaMovimiento' => $movimiento->fecha_movimiento?->format('Y-m-d H:i') ?? '-',
                    'firmaMovimiento' => $movimiento->firma_movimiento,
                    'fechaFirma' => $movimiento->fecha_firma?->format('Y-m-d H:i') ?? null,
                    'tallasPorGenero' => array_values($tallasPorGenero)
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
     * Guardar firma de movimiento
     */
    public function guardarFirmaSalida(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'movimiento_id' => 'required|integer|exists:lavanderia_movimientos,id',
                'firma' => 'required|file|mimes:webp,png,jpg,jpeg|max:5120',
            ]);

            $movimiento = \App\Models\LavanderiaMovimiento::findOrFail($validated['movimiento_id']);
            
            // Crear directorio si no existe
            $storagePath = storage_path('app/public/firmas/' . $movimiento->id);
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Guardar archivo WebP
            $file = $request->file('firma');
            $filename = 'img_' . time() . '.webp';
            $file->move($storagePath, $filename);

            // Guardar ruta en la base de datos, cambiar estado a COMPLETADO y guardar fecha_firma
            $firmaPath = 'storage/firmas/' . $movimiento->id . '/' . $filename;
            $movimiento->update([
                'firma_movimiento' => $firmaPath,
                'fecha_firma' => now(),
                'estado' => 'COMPLETADO'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Firma guardada exitosamente',
                'data' => [
                    'id' => $movimiento->id,
                    'estadoFirma' => 'FIRMADO',
                    'firma_url' => '/' . $firmaPath,
                    'fecha_firma' => $movimiento->fecha_firma->format('Y-m-d H:i')
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en guardarFirmaSalida:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar firma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar salida de lavandería (múltiples recibos)
     */
    public function registrarSalida(Request $request): JsonResponse
    {
        \Log::info('Iniciando registrarSalida', [
            'request_data' => $request->all(),
        ]);

        try {
            $data = $request->all();
            
            \Log::info('Datos recibidos:', $data);

            if (empty($data['recibos']) || !is_array($data['recibos']) || empty($data['tallas'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan campos requeridos (recibos, tallas)',
                    'received' => $data
                ], 422);
            }

            // Crear movimiento
            $movimiento = \App\Models\LavanderiaMovimiento::create([
                'tipo_movimiento' => $data['tipo_movimiento'] ?? 'SALIDA',
                'fecha_movimiento' => now(),
                'firma_movimiento' => 'pendiente',
                'novedad' => $data['novedad'] ?? null,
                'estado' => 'PENDIENTE'
            ]);

            \Log::info('Movimiento creado:', ['id' => $movimiento->id]);

            // Crear registros de recibos asociados al movimiento
            foreach ($data['recibos'] as $recibo) {
                \App\Models\LavanderiaMovimientoRecibo::create([
                    'lavanderia_movimiento_id' => $movimiento->id,
                    'consecutivo_recibo_pedido_id' => (int)$recibo['recibo_id'],
                    'numero_recibo' => (int)$recibo['numero_recibo'],
                    'tipo_recibo' => $recibo['tipo_recibo'],
                ]);
            }

            \Log::info('Recibos asociados al movimiento:', ['cantidad' => count($data['recibos'])]);

            // Crear registros de tallas
            foreach ($data['tallas'] as $talla) {
                $tipoPrenda = $talla['tipo_prenda'] ?? 'COSTURA';
                
                $tallaData = [
                    'lavanderia_movimiento_id' => $movimiento->id,
                    'talla' => $talla['talla'],
                    'genero' => $talla['genero'] ?? null,
                    'color' => null,
                    'cantidad_enviada' => (int)$talla['cantidad_enviada'],
                    'cantidad_recibida' => 0,
                    'tipo_prenda' => $tipoPrenda,
                ];

                // Agregar relación a prenda según el tipo
                if ($tipoPrenda === 'BODEGA') {
                    $tallaData['prenda_bodega_id'] = $talla['prenda_bodega_id'] ?? null;
                } else {
                    $tallaData['prenda_id'] = $talla['prenda_id'] ?? null;
                }

                \App\Models\LavanderiaMovimientoTalla::create($tallaData);
            }

            \Log::info('Salida registrada exitosamente', ['movimiento_id' => $movimiento->id]);

            return response()->json([
                'success' => true,
                'message' => 'Salida registrada exitosamente',
                'data' => [
                    'id' => $movimiento->id,
                    'recibos_count' => count($data['recibos'])
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
