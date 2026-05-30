<?php

namespace App\Infrastructure\Http\Controllers\Lavanderia;

use App\Http\Controllers\Controller;
use App\Application\Lavanderia\UseCases\ObtenerOrdenesSeguimientoLavanderiaUseCase;
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
     * Mostrar el módulo de seguimiento de lavandería
     */
    public function seguimiento(): View
    {
        return view('seguimiento-lavanderia.index');
    }

    /**
     * API: listar órdenes del seguimiento de lavandería
     */
    public function apiOrdenesSeguimiento(
        Request $request,
        ObtenerOrdenesSeguimientoLavanderiaUseCase $useCase
    ): JsonResponse {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(max(1, (int) $request->query('per_page', 25)), 100);
        $search = trim((string) $request->query('search', ''));

        $ordenes = $useCase->execute($page, $perPage, $search);

        return response()->json([
            'success' => true,
            'data' => $ordenes->items(),
            'pagination' => [
                'current_page' => $ordenes->currentPage(),
                'last_page' => $ordenes->lastPage(),
                'per_page' => $ordenes->perPage(),
                'total' => $ordenes->total(),
                'from' => $ordenes->firstItem(),
                'to' => $ordenes->lastItem(),
            ],
        ]);
    }

    /**
     * Obtener movimientos de un recibo específico en seguimiento de lavandería
     */
    public function apiMovimientosRecibo(int $reciboId): JsonResponse
    {
        try {
            $movimientos = \App\Models\LavanderiaMovimientoRecibo::where('consecutivo_recibo_pedido_id', $reciboId)
                ->with([
                    'movimiento.tallas.prenda',
                    'movimiento.tallas.prendaBodega',
                    'recibo.prenda',
                    'recibo.prendaBodega',
                    'recibo.pedido'
                ])
                ->get()
                ->map(function ($reciboMovimiento) {
                    $movimiento = $reciboMovimiento->movimiento;
                    $recibo = $reciboMovimiento->recibo;

                    // Obtener prenda según el tipo de recibo
                    $prenda = 'Sin prenda';
                    if ($reciboMovimiento->tipo_recibo === 'CORTE-PARA-BODEGA') {
                        $prenda = $recibo?->prendaBodega?->nombre ?? 'Sin prenda';
                    } else {
                        $prenda = $recibo?->prenda?->nombre_prenda ?? 'Sin prenda';
                    }

                    // Obtener todas las tallas del movimiento
                    $tallas = $movimiento->tallas
                        ->map(function ($talla) {
                            return [
                                'talla' => $talla->talla,
                                'cantidad_enviada' => $talla->cantidad_enviada,
                                'cantidad_recibida' => $talla->cantidad_recibida,
                                'genero' => $talla->genero,
                                'color' => $talla->color,
                            ];
                        })
                        ->values()
                        ->toArray();

                    return [
                        'id' => $movimiento->id,
                        'tipo_movimiento' => $movimiento->tipo_movimiento,
                        'fecha_movimiento' => $movimiento->fecha_movimiento?->format('Y-m-d H:i') ?? '-',
                        'estado' => $movimiento->estado,
                        'novedad' => $movimiento->novedad,
                        'prenda' => $prenda,
                        'tallas' => $tallas,
                    ];
                })
                ->sortByDesc('fecha_movimiento')
                ->values();

            // Agrupar por tipo de movimiento
            $movimientosAgrupados = [
                'entradas' => $movimientos->filter(fn($m) => $m['tipo_movimiento'] === 'ENTRADA')->values(),
                'salidas' => $movimientos->filter(fn($m) => $m['tipo_movimiento'] === 'SALIDA')->values(),
            ];

            return response()->json([
                'success' => true,
                'data' => $movimientosAgrupados,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en apiMovimientosRecibo:', [
                'recibo_id' => $reciboId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos del recibo'
            ], 500);
        }
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

            $reciboIds = $recibos->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $saldoTallasPorRecibo = [];

            if (!empty($reciboIds)) {
                $movimientosRecibo = \App\Models\LavanderiaMovimientoRecibo::with([
                    'movimiento:id,tipo_movimiento',
                    'movimiento.tallas:id,lavanderia_movimiento_id,prenda_id,prenda_bodega_id,talla,genero,cantidad_enviada'
                ])
                ->whereIn('consecutivo_recibo_pedido_id', $reciboIds)
                ->get();

                foreach ($movimientosRecibo as $movimientoRecibo) {
                    $reciboId = (int) $movimientoRecibo->consecutivo_recibo_pedido_id;
                    $tipoMovimiento = strtoupper((string) ($movimientoRecibo->movimiento?->tipo_movimiento ?? 'SALIDA'));
                    $factor = $tipoMovimiento === 'ENTRADA' ? 1 : -1;

                    foreach (($movimientoRecibo->movimiento?->tallas ?? []) as $tallaMovimiento) {
                        if (!empty($tallaMovimiento->prenda_bodega_id)) {
                            $prendaKey = 'BODEGA:' . (int) $tallaMovimiento->prenda_bodega_id;
                        } elseif (!empty($tallaMovimiento->prenda_id)) {
                            $prendaKey = 'COSTURA:' . (int) $tallaMovimiento->prenda_id;
                        } else {
                            continue;
                        }

                        $tallaKey = strtoupper(trim((string) ($tallaMovimiento->talla ?? '')));
                        $generoKey = strtoupper(trim((string) ($tallaMovimiento->genero ?? '')));
                        $key = $prendaKey . '|' . $tallaKey . '|' . $generoKey;

                        if (!isset($saldoTallasPorRecibo[$reciboId])) {
                            $saldoTallasPorRecibo[$reciboId] = [];
                        }

                        if (!isset($saldoTallasPorRecibo[$reciboId][$key])) {
                            $saldoTallasPorRecibo[$reciboId][$key] = 0;
                        }

                        $saldoTallasPorRecibo[$reciboId][$key] += $factor * (int) $tallaMovimiento->cantidad_enviada;
                    }
                }
            }

            $resultado = $recibos->map(function ($recibo) use ($saldoTallasPorRecibo) {
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
                $prendaKey = '';

                if ($recibo->tipo_recibo === 'CORTE-PARA-BODEGA') {
                    // Para recibos de CORTE-PARA-BODEGA, usar prendaBodega
                    if ($recibo->prendaBodega) {
                        $prendaNombre = $recibo->prendaBodega->nombre ?? 'Sin prenda';
                        $prendaKey = 'BODEGA:' . (int) $recibo->prendaBodega->id;
                        
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
                        $prendaKey = 'COSTURA:' . (int) $recibo->prenda->id;
                        
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

                $tallasDisponibles = [];
                if ($prendaKey === '') {
                    $tallas = [];
                }
                foreach ($tallas as $talla) {
                    $cantidadOriginal = (int) ($talla['cantidad'] ?? 0);
                    $tallaKey = strtoupper(trim((string) ($talla['talla'] ?? '')));
                    $generoKey = strtoupper(trim((string) ($talla['genero'] ?? '')));
                    $saldoMovimiento = (int) ($saldoTallasPorRecibo[$recibo->id][$prendaKey . '|' . $tallaKey . '|' . $generoKey] ?? 0);
                    $cantidadDisponible = max(0, $cantidadOriginal + $saldoMovimiento);

                    if ($cantidadDisponible <= 0) {
                        continue;
                    }

                    $talla['cantidad_original'] = $cantidadOriginal;
                    $talla['cantidad_disponible'] = $cantidadDisponible;
                    $talla['cantidad'] = $cantidadDisponible;
                    $tallasDisponibles[] = $talla;
                }

                return [
                    'id' => $recibo->id,
                    'numero_recibo' => $recibo->consecutivo_actual ?? $recibo->id,
                    'tipo_recibo' => $recibo->tipo_recibo === 'CORTE-PARA-BODEGA' ? 'BODEGA' : $recibo->tipo_recibo,
                    'tipo_recibo_original' => $recibo->tipo_recibo,
                    'cliente' => $clienteNombre,
                    'prenda_id' => $recibo->prenda?->id,
                    'prenda_bodega_id' => $recibo->prendaBodega?->id,
                    'prenda' => $prendaNombre,
                    'descripcion' => $recibo->prenda?->descripcion ?? $recibo->prendaBodega?->descripcion ?? '',
                    'cantidad_total' => $tallasDisponibles ? array_sum(array_column($tallasDisponibles, 'cantidad')) : 0,
                    'tallas' => $tallasDisponibles
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
                'tallas.prendaBodega',
                'tallas.prendaAgregada'
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

                // Agrupar prendas manuales por prenda agregada
                $prendasManuales = $movimiento->tallas
                    ->filter(function ($talla) {
                        return !empty($talla->prenda_agregada_id) && $talla->prendaAgregada;
                    })
                    ->groupBy('prenda_agregada_id')
                    ->map(function ($tallasPrenda) {
                        $primeraTalla = $tallasPrenda->first();
                        $prendaAgregada = $primeraTalla->prendaAgregada;

                        return [
                            'id' => $prendaAgregada?->id,
                            'descripcion' => $prendaAgregada?->descripcion ?? 'Sin descripción',
                            'genero' => $primeraTalla->genero ?? null,
                            'tallas' => $tallasPrenda->map(function ($talla) {
                                return [
                                    'talla' => $talla->talla,
                                    'cantidad_enviada' => $talla->cantidad_enviada,
                                    'cantidad_recibida' => $talla->cantidad_recibida,
                                ];
                            })->values()->toArray()
                        ];
                    })
                    ->values()
                    ->toArray();

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
                    'tallasPorGenero' => array_values($tallasPorGenero),
                    'prendasManuales' => $prendasManuales
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
     * Registrar salida de lavandería (múltiples recibos + prendas manuales)
     */
    public function registrarSalida(Request $request): JsonResponse
    {
        \Log::info('Iniciando registrarSalida', [
            'request_data' => $request->all(),
        ]);

        try {
            $data = $request->all();
            
            \Log::info('Datos recibidos:', $data);

            // Validar que haya al menos recibos o prendas manuales
            $tieneRecibos = !empty($data['recibos']) && is_array($data['recibos']);
            $tienePrendasManuales = !empty($data['prendas_manuales']) && is_array($data['prendas_manuales']);
            $tieneTallas = !empty($data['tallas']) && is_array($data['tallas']);

            if (!$tieneTallas) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan tallas',
                    'received' => $data
                ], 422);
            }

            if (!$tieneRecibos && !$tienePrendasManuales) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe agregar al menos un recibo o una prenda manual',
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
            if ($tieneRecibos) {
                foreach ($data['recibos'] as $recibo) {
                    \App\Models\LavanderiaMovimientoRecibo::create([
                        'lavanderia_movimiento_id' => $movimiento->id,
                        'consecutivo_recibo_pedido_id' => (int)$recibo['recibo_id'],
                        'numero_recibo' => (int)$recibo['numero_recibo'],
                        'tipo_recibo' => $recibo['tipo_recibo'],
                    ]);
                }

                \Log::info('Recibos asociados al movimiento:', ['cantidad' => count($data['recibos'])]);
            }

            // Crear prendas manuales y mapear IDs temporales a IDs reales
            $prendaManualIdMap = []; // Mapeo de IDs temporales a IDs reales
            if ($tienePrendasManuales) {
                foreach ($data['prendas_manuales'] as $index => $prendaManual) {
                    $tempId = $prendaManual['temp_id'] ?? $index;
                    $prendaAgregada = \App\Models\LavanderiaPrendaAgregada::create([
                        'lavanderia_movimiento_id' => $movimiento->id,
                        'descripcion' => $prendaManual['descripcion'],
                    ]);
                    
                    // Mapear el ID temporal (índice) al ID real de la base de datos
                    $prendaManualIdMap[$tempId] = $prendaAgregada->id;
                }

                \Log::info('Prendas manuales agregadas:', ['cantidad' => count($data['prendas_manuales']), 'map' => $prendaManualIdMap]);
            }

            // Crear registros de tallas
            foreach ($data['tallas'] as $talla) {
                $tallaData = [
                    'lavanderia_movimiento_id' => $movimiento->id,
                    'talla' => $talla['talla'],
                    'genero' => $talla['genero'] ?? null,
                    'color' => null,
                    'cantidad_enviada' => (int)$talla['cantidad_enviada'],
                    'cantidad_recibida' => 0,
                ];

                // Agregar relación a prenda según el tipo
                if (!empty($talla['prenda_bodega_id'])) {
                    $tallaData['prenda_bodega_id'] = $talla['prenda_bodega_id'];
                } elseif (!empty($talla['prenda_agregada_id'])) {
                    // Usar el ID real mapeado desde el ID temporal
                    $tempId = $talla['prenda_agregada_id'];
                    $tallaData['prenda_agregada_id'] = $prendaManualIdMap[$tempId] ?? $tempId;
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
                    'recibos_count' => $tieneRecibos ? count($data['recibos']) : 0,
                    'prendas_manuales_count' => $tienePrendasManuales ? count($data['prendas_manuales']) : 0
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
