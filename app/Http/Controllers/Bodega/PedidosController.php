<?php

namespace App\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\PedidoAuditoria;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;
use App\Models\CosturaBodegaDetalle;
use App\Models\EppBodegaAuditoria;
use App\Models\CosturaBodegaAuditoria;
use App\Models\BodegaNota;
use App\Events\BodegaNotasGuardada;
use App\Events\BodegaDetallesActualizados;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PedidosController extends Controller
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    /**
     * Obtener la clase del modelo de detalles según el rol del usuario
     */
    private function getDetallesModelClass($rolesDelUsuario): string
    {
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            return EppBodegaDetalle::class;
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            return CosturaBodegaDetalle::class;
        }
        // Por defecto: Bodeguero
        return BodegaDetallesTalla::class;
    }

    /**
     * Obtener instancia del modelo de detalles según el rol del usuario
     */
    private function getDetallesModel($rolesDelUsuario)
    {
        $modelClass = $this->getDetallesModelClass($rolesDelUsuario);
        return app($modelClass);
    }

    /**
     * Mostrar lista de pedidos para bodeguero
     */
    public function index(Request $request)
    {
        // Obtener usuario y sus roles
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();
        
        // Determinar áreas que puede ver según su rol
        $areasPermitidas = [];
        if (in_array('Costura-Bodega', $rolesDelUsuario)) {
            $areasPermitidas[] = 'Costura';
        }
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            $areasPermitidas[] = 'EPP';
        }
        // Si no tiene roles específicos de área, ver todas las áreas
        if (empty($areasPermitidas)) {
            $areasPermitidas = ['Costura', 'EPP', 'Otro', null];
        }
        
        // Estados permitidos en bodega (case-insensitive)
        $estadosPermitidos = ['ENTREGADO', 'EN EJECUCIÓN', 'NO INICIADO', 'ANULADA', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'];
        
        // Obtener TODOS los pedidos con estados permitidos
        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->where(function($q) use ($estadosPermitidos) {
                foreach($estadosPermitidos as $estado) {
                    $q->orWhereRaw('UPPER(TRIM(estado)) = ?', [strtoupper($estado)]);
                }
            })
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Filtrar por áreas permitidas según el rol
        $pedidosFiltradosPorRol = $todosLosPedidos->filter(function($item) use ($areasPermitidas) {
            $bdDetalles = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)->get();
            
            if ($bdDetalles->isEmpty()) {
                return in_array(null, $areasPermitidas);
            }
            
            foreach ($bdDetalles as $detalle) {
                if (in_array($detalle->area, $areasPermitidas)) {
                    return true;
                }
            }
            
            return false;
        })->values();

        // Obtener números de pedidos ÚNICOS (filtrados por rol)
        $numerosPedidosUnicos = $pedidosFiltradosPorRol->pluck('numero_pedido')->unique()->values();
        
        // Total de pedidos únicos según el rol del usuario
        $totalPedidos = $numerosPedidosUnicos->count();

        // Paginar - 1 pedido por página
        $paginaActual = $request->get('page', 1);
        $porPagina = 1;
        $offset = ($paginaActual - 1) * $porPagina;

        // Obtener los numero_pedidos de la página actual (filtrados por rol)
        $pedidosPaginados = $numerosPedidosUnicos->slice($offset, $porPagina);

        // Ahora obtener SOLO los pedidos de esta página
        $pedidos = $pedidosFiltradosPorRol->whereIn('numero_pedido', $pedidosPaginados)->values();

        // Cargar datos básicos SIEMPRE de bodega_detalles_talla
        $datosBodegaBasicos = BodegaDetallesTalla::all()
            ->map(function ($item) {
                return $item->toArray();
            })
            ->keyBy(function ($item) {
                return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
            });
        
        // Cargar estado ESPECÍFICO del rol
        $datosEstadoRol = collect();
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            // EPP-Bodega: obtener estado de epp_bodega_detalles
            $datosEstadoRol = EppBodegaDetalle::all()
                ->map(function ($item) {
                    return $item->toArray();
                })
                ->keyBy(function ($item) {
                    return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
                });
            
            \Log::info('[BODEGA-DEBUG] EPP-Bodega: cargados ' . $datosEstadoRol->count() . ' registros de estado desde epp_bodega_detalles');
            
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            // Costura-Bodega: obtener estado de costura_bodega_detalles
            $datosEstadoRol = CosturaBodegaDetalle::all()
                ->map(function ($item) {
                    return $item->toArray();
                })
                ->keyBy(function ($item) {
                    return $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
                });
            
            \Log::info('[BODEGA-DEBUG] Costura-Bodega: cargados ' . $datosEstadoRol->count() . ' registros de estado desde costura_bodega_detalles');
        }
        
        // Fusionar: datos básicos + estado del rol
        $datosBodega = $datosBodegaBasicos->map(function($item) use ($datosEstadoRol, $rolesDelUsuario) {
            $clave = $item['numero_pedido'] . '|' . $item['talla'] . '|' . ($item['prenda_nombre'] ?? '') . '|' . ($item['cantidad'] ?? 0);
            
            // Para EPP-Bodega y Costura-Bodega: el estado SOLO viene de sus tablas, no de bodega_detalles_talla
            if (in_array('EPP-Bodega', $rolesDelUsuario) || in_array('Costura-Bodega', $rolesDelUsuario)) {
                // El estado viene SOLO de la tabla del rol
                if ($datosEstadoRol->has($clave)) {
                    $estadoRol = $datosEstadoRol[$clave];
                    $item['estado_bodega'] = $estadoRol['estado_bodega'] ?? null;
                } else {
                    // Si el item no existe en la tabla del rol, no tiene estado
                    $item['estado_bodega'] = null;
                }
            }
            // Para Bodeguero: el estado está en bodega_detalles_talla (no se cambia)
            
            return $item;
        });
        
        \Log::info('[BODEGA-DEBUG] Datos cargados por rol', [
            'usuario_roles' => $rolesDelUsuario,
            'total_bodega_detalles_basicos' => $datosBodegaBasicos->count(),
            'total_estado_rol' => $datosEstadoRol->count(),
            'total_datosBodega_fusionados' => $datosBodega->count(),
        ]);

        // Cargar TODAS las notas de bodega_notas precargadas
        $notasBodega = \App\Models\BodegaNota::all()
            ->groupBy(function ($item) {
                return $item->numero_pedido . '|' . $item->talla;
            })
            ->map(function ($notas) {
                return $notas->map(function ($nota) {
                    return [
                        'id' => $nota->id,
                        'contenido' => $nota->contenido,
                        'usuario_nombre' => $nota->usuario_nombre,
                        'usuario_rol' => $nota->usuario_rol,
                        'usuario_id' => $nota->usuario_id,
                        'ip_address' => $nota->ip_address,
                        'fecha' => $nota->created_at->format('d/m/Y'),
                        'hora' => $nota->created_at->format('H:i:s'),
                        'fecha_completa' => $nota->created_at->format('d/m/Y H:i:s'),
                        'created_at' => $nota->created_at,
                    ];
                })->sortByDesc('created_at')->values()->toArray();
            });

        // Agrupar solo los pedidos de la página actual (ya filtrados por rol en la paginación)
        $pedidosAgrupados = $pedidos
            ->groupBy('numero_pedido')
            ->mapWithKeys(function ($items, $numeroPedido) use ($datosBodega, $areasPermitidas, $rolesDelUsuario) {
            $itemsConTallas = [];
            
            // Obtener estado del PedidoProduccion para este número de pedido (una sola vez)
            $pedidoProduccion = PedidoProduccion::with(['asesor'])->where('numero_pedido', $numeroPedido)->first();
            $estadoPedidoProduccion = $pedidoProduccion?->estado ?? null;
            $nombreAsesor = $pedidoProduccion?->asesor?->nombre ?? $pedidoProduccion?->asesor?->name ?? null;
            
            foreach ($items as $item) {
                try {
                    // Obtener datos COMPLETOS del pedido (con variantes, manga, broche, bolsillos)
                    $datosCompletos = $this->obtenerPedidoUseCase->ejecutar($item->id);
                    
                    // Procesar PRENDAS - primero filtrar solo items que deben incluirse
                    if (isset($datosCompletos->prendas) && is_array($datosCompletos->prendas)) {
                        foreach ($datosCompletos->prendas as $prendaEnriquecida) {
                            $variantes = $prendaEnriquecida['variantes'] ?? [];
                            
                            if (count($variantes) > 0) {
                                // Primero, contar cuántos variantes realmente se incluirán
                                $variantesAIncluir = [];
                                foreach ($variantes as $variante) {
                                    $talla = $variante['talla'] ?? '';
                                    
                                    // SIEMPRE obtener datos BASE de bodega_detalles_talla
                                    $bodegaDataBase = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)
                                        ->where('talla', $talla)
                                        ->first();
                                    
                                    // Obtener estado de la tabla específica del rol
                                    $bodegaDataEstado = null;
                                    if (in_array('EPP-Bodega', $rolesDelUsuario)) {
                                        $bodegaDataEstado = EppBodegaDetalle::where('numero_pedido', $item->numero_pedido)
                                            ->where('talla', $talla)
                                            ->where('prenda_nombre', $bodegaDataBase?->prenda_nombre ?? $prendaEnriquecida['nombre'] ?? null)
                                            ->where('cantidad', $bodegaDataBase?->cantidad ?? $prendaEnriquecida['cantidad'] ?? 0)
                                            ->first();
                                    } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
                                        $bodegaDataEstado = CosturaBodegaDetalle::where('numero_pedido', $item->numero_pedido)
                                            ->where('talla', $talla)
                                            ->where('prenda_nombre', $bodegaDataBase?->prenda_nombre ?? $prendaEnriquecida['nombre'] ?? null)
                                            ->where('cantidad', $bodegaDataBase?->cantidad ?? $prendaEnriquecida['cantidad'] ?? 0)
                                            ->first();
                                    }
                                    
                                    // Para roles específicos de área, usar base + estado del rol
                                    // Para bodeguero, usar solo base
                                    if (in_array('EPP-Bodega', $rolesDelUsuario) || in_array('Costura-Bodega', $rolesDelUsuario)) {
                                        $bodegaData = $bodegaDataBase; // Datos básicos
                                    } else {
                                        $bodegaData = $bodegaDataBase;
                                    }

                                    // Determinar si se debe incluir
                                    $debeIncluir = false;
                                    if ($bodegaData) {
                                        // Si tiene datos en bodega_detalles_talla, validar el área
                                        if (in_array($bodegaData->area, $areasPermitidas)) {
                                            $debeIncluir = true;
                                        }
                                    } else {
                                        // Si no hay registro en bodega pero es bodeguero normal, aún incluir (nuevo item)
                                        if (!in_array('Costura-Bodega', $rolesDelUsuario) && !in_array('EPP-Bodega', $rolesDelUsuario)) {
                                            $debeIncluir = true;
                                        }
                                    }

                                    if ($debeIncluir) {
                                        $variantesAIncluir[] = [
                                            'variante' => $variante,
                                            'bodegaData' => $bodegaData,
                                            'bodegaDataBase' => $bodegaDataBase,
                                            'bodegaDataEstado' => $bodegaDataEstado
                                        ];
                                    }
                                }

                                // Ahora agregar solo los variantes que pasan el filtro, con rowspan correcto
                                $rowspanCorreto = count($variantesAIncluir);
                                $firstRow = true;
                                foreach ($variantesAIncluir as $varianteData) {
                                    $variante = $varianteData['variante'];
                                    $bodegaData = $varianteData['bodegaData'];
                                    $bodegaDataBase = $varianteData['bodegaDataBase'];
                                    $bodegaDataEstado = $varianteData['bodegaDataEstado'];

                                    $row = [
                                        'id' => $item->id,
                                        'tipo' => 'prenda',
                                        'numero_pedido' => $item->numero_pedido,
                                        'asesor' => $item->asesor->nombre ?? $item->asesor->name ?? 'N/A',
                                        'asesor_rowspan' => $firstRow ? $rowspanCorreto : 0,
                                        'empresa' => $item->cliente ?? 'N/A',
                                        'empresa_rowspan' => $firstRow ? $rowspanCorreto : 0,
                                        'descripcion' => $prendaEnriquecida,
                                        'descripcion_rowspan' => $firstRow ? $rowspanCorreto : 0,
                                        'talla' => $variante['talla'] ?? '—',
                                        'cantidad_total' => $variante['cantidad'] ?? 0,
                                        'observaciones' => $bodegaData?->observaciones_bodega ?? null,
                                        'pendientes' => $bodegaData?->pendientes ?? null,
                                        'fecha_entrega' => $bodegaDataBase?->fecha_entrega ? Carbon::parse($bodegaDataBase->fecha_entrega)->format('Y-m-d') : ($item->fecha_estimada_de_entrega ? Carbon::parse($item->fecha_estimada_de_entrega)->format('Y-m-d') : null),
                                        'fecha_pedido' => $bodegaDataBase?->fecha_pedido ? Carbon::parse($bodegaDataBase->fecha_pedido)->format('Y-m-d') : null,
                                        'estado_bodega' => (in_array('EPP-Bodega', $rolesDelUsuario) || in_array('Costura-Bodega', $rolesDelUsuario)) 
                                            ? ($bodegaDataEstado?->estado_bodega ?? null) 
                                            : ($bodegaDataEstado?->estado_bodega ?? $bodegaDataBase?->estado_bodega ?? $item->estado),
                                        'estado_pedido_produccion' => $estadoPedidoProduccion,
                                        'nombre_asesor_anulacion' => $nombreAsesor,
                                        'area' => $bodegaData?->area ?? null,
                                        'usuario_bodega_nombre' => $bodegaData?->usuario_bodega_nombre ?? null,
                                        'bodega_id' => $bodegaData?->id ?? null,
                                        'tuvo_cambios_recientes' => PedidoAuditoria::tuvoChangiosRecientes($item->id, 48)
                                    ];
                                    $itemsConTallas[] = $row;
                                    $firstRow = false;
                                }
                            }
                        }
                    }
                    
                    // Procesar EPPS
                    if (isset($datosCompletos->epps) && is_array($datosCompletos->epps)) {
                        foreach ($datosCompletos->epps as $eppIndex => $eppEnriquecido) {
                            $eppNombre = $eppEnriquecido['nombre'] ?? 'EPP';
                            $eppCantidad = $eppEnriquecido['cantidad'] ?? 0;
                            $eppId = md5($item->numero_pedido . '|' . $eppNombre . '|' . $eppCantidad);
                            
                            // SIEMPRE obtener datos BASE (fechas) de bodega_detalles_talla
                            $bodegaDataBase = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)
                                ->where('talla', $eppId)
                                ->first();
                            
                            // Obtener estado de la tabla específica del rol
                            $bodegaDataEstado = null;
                            if (in_array('EPP-Bodega', $rolesDelUsuario)) {
                                $bodegaDataEstado = EppBodegaDetalle::where('numero_pedido', $item->numero_pedido)
                                    ->where('talla', $eppId)
                                    ->first();
                            } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
                                $bodegaDataEstado = CosturaBodegaDetalle::where('numero_pedido', $item->numero_pedido)
                                    ->where('talla', $eppId)
                                    ->first();
                            }
                            
                            // Usar datos base de bodega_detalles_talla
                            $bodegaData = $bodegaDataBase;

                            // Determinar si se debe incluir
                            $debeIncluir = false;
                            if ($bodegaData) {
                                // Si tiene datos en bodega_detalles_talla, validar el área
                                if (in_array($bodegaData->area, $areasPermitidas)) {
                                    $debeIncluir = true;
                                }
                            } else {
                                // Si no hay registro en bodega pero es bodeguero normal, aún incluir (nuevo item)
                                if (!in_array('Costura-Bodega', $rolesDelUsuario) && !in_array('EPP-Bodega', $rolesDelUsuario)) {
                                    $debeIncluir = true;
                                }
                            }

                            if ($debeIncluir) {
                                $itemsConTallas[] = [
                                    'id' => $item->id,
                                    'tipo' => 'epp',
                                    'numero_pedido' => $item->numero_pedido,
                                    'asesor' => $item->asesor->nombre ?? $item->asesor->name ?? 'N/A',
                                    'asesor_rowspan' => 1,
                                    'empresa' => $item->cliente ?? 'N/A',
                                    'empresa_rowspan' => 1,
                                    'descripcion' => $eppEnriquecido,
                                    'descripcion_rowspan' => 1,
                                    'talla' => $eppId,
                                    'cantidad_total' => $eppCantidad,
                                    'observaciones' => $bodegaData?->observaciones_bodega ?? null,
                                    'pendientes' => $bodegaData?->pendientes ?? null,
                                    'fecha_entrega' => $bodegaDataBase?->fecha_entrega ? Carbon::parse($bodegaDataBase->fecha_entrega)->format('Y-m-d') : ($item->fecha_estimada_de_entrega ? Carbon::parse($item->fecha_estimada_de_entrega)->format('Y-m-d') : null),
                                    'fecha_pedido' => $bodegaDataBase?->fecha_pedido ? Carbon::parse($bodegaDataBase->fecha_pedido)->format('Y-m-d') : null,
                                    'estado_bodega' => (in_array('EPP-Bodega', $rolesDelUsuario) || in_array('Costura-Bodega', $rolesDelUsuario)) 
                                        ? ($bodegaDataEstado?->estado_bodega ?? null) 
                                        : ($bodegaDataEstado?->estado_bodega ?? $bodegaDataBase?->estado_bodega ?? $item->estado),
                                    'estado_pedido_produccion' => $estadoPedidoProduccion,
                                    'nombre_asesor_anulacion' => $nombreAsesor,
                                    'area' => $bodegaData?->area ?? null,
                                    'usuario_bodega_nombre' => $bodegaData?->usuario_bodega_nombre ?? null,
                                    'bodega_id' => $bodegaData?->id ?? null,
                                    'tuvo_cambios_recientes' => PedidoAuditoria::tuvoChangiosRecientes($item->id, 48)
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback: si ObtenerPedidoUseCase falla, usar datos básicos
                    \Log::warning('[Bodega] Error al obtener datos completos del pedido', [
                        'pedido_id' => $item->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return [$numeroPedido => $itemsConTallas];
        })->toArray();

        // Obtener lista única de asesores para filtro
        $asesores = $pedidos->pluck('asesor.nombre')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Crear objeto de paginación manual
        $paginacion = new \Illuminate\Pagination\LengthAwarePaginator(
            $pedidosPaginados,
            $totalPedidos,
            $porPagina,
            $paginaActual,
            [
                'path' => route('gestion-bodega.pedidos'),
                'query' => $request->query(),
            ]
        );

        // Determine which view to use based on role
        $esReadOnly = in_array('Costura-Bodega', $rolesDelUsuario) || in_array('EPP-Bodega', $rolesDelUsuario);
        $viewName = $esReadOnly ? 'bodega.pedidos-readonly' : 'bodega.pedidos';

        return view($viewName, [
            'pedidosAgrupados' => $pedidosAgrupados,
            'asesores' => $asesores,
            'paginacion' => $paginacion,
            'totalPedidos' => $totalPedidos,
            'datosBodega' => $datosBodega,
            'notasBodega' => $notasBodega,
        ]);
    }

    /**
     * Marcar pedido como entregado
     */
    public function entregar(Request $request, $id): JsonResponse
    {
        try {
            $reciboPrenda = ReciboPrenda::findOrFail($id);
            $numeroPedido = $reciboPrenda->numero_pedido;

            // Actualizar estado
            $reciboPrenda->update([
                'estado' => 'entregado',
                'fecha_entrega_real' => Carbon::now(),
            ]);

            // Verificar si todos los artículos del pedido están entregados
            $allDelivered = ReciboPrenda::where('numero_pedido', $numeroPedido)
                ->where('estado', '!=', 'entregado')
                ->doesntExist();

            $pedidoEstado = $allDelivered ? 'entregado' : 'pendiente';

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como entregado correctamente',
                'allDelivered' => $allDelivered,
                'pedidoEstado' => $pedidoEstado,
                'data' => [
                    'id' => $reciboPrenda->id,
                    'estado' => 'entregado',
                    'fecha_entrega_real' => $reciboPrenda->fecha_entrega_real,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como entregado: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualizar observaciones
     */
    public function actualizarObservaciones(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $reciboPrenda = ReciboPrenda::findOrFail($validated['id']);

            // Validar permiso
            $this->authorize('bodegueroDashboard');

            // Actualizar observaciones
            $reciboPrenda->update([
                'observaciones' => $validated['observaciones'],
            ]);

            // Registrar en auditoría

            return response()->json([
                'success' => true,
                'message' => 'Observaciones actualizadas correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar observaciones: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualizar fecha de entrega
     */
    public function actualizarFecha(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'fecha_entrega' => 'required|date',
            ]);

            $reciboPrenda = ReciboPrenda::findOrFail($validated['id']);

            // Validar permiso
            $this->authorize('bodegueroDashboard');

            // Actualizar fecha
            $reciboPrenda->update([
                'fecha_entrega' => Carbon::createFromFormat('Y-m-d', $validated['fecha_entrega']),
            ]);

            // Actualizar fecha
            $reciboPrenda->update(['fecha_entrega' => $validated['fecha_entrega']]);

            return response()->json([
                'success' => true,
                'message' => 'Fecha de entrega actualizada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar fecha: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener datos de factura para modal - Usa la misma vista que Despacho
     */
    public function obtenerDatosFacturaJSON($id)
    {
        try {
            // Obtener el ReciboPrenda y su numero_pedido en una sola query
            $reciboPrenda = ReciboPrenda::select('id', 'numero_pedido')
                ->findOrFail($id);
            
            // Obtener el PedidoProduccion usando numero_pedido con direct join
            $pedido = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)
                ->firstOrFail();
            
            // Usar el repositorio que obtiene los datos completos (ya optimizado con eager loading)
            $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
            
            return response()->json($datos);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[ERROR] obtenerDatosFacturaJSON | ID: ' . $id . ' | ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function obtenerDatosFactura($id)
    {
        try {
            // Obtener el recibo
            $reciboPrenda = ReciboPrenda::find($id);
            
            if (!$reciboPrenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo de prenda no encontrado'
                ], 404);
            }
            
            // Obtener el pedido de producción asociado
            $pedido = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido de producción no encontrado'
                ], 404);
            }
            
            // Usar el repositorio que obtiene los datos completos (igual que despacho)
            $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
            
            return response()->json($datos);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFactura: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Determinar estado del pedido
     */
    private function determinarEstado($item)
    {
        // Por defecto, retornar null (sin estado asignado)
        return null;
    }

    /**
     * Exportar datos (opcional)
     */
    public function export(Request $request)
    {
        // Implementar exportación a Excel/PDF si es necesario
    }

    /**
     * Dashboard con estadísticas (opcional)
     */
    public function dashboard()
    {
        $totalPedidos = ReciboPrenda::whereDate('created_at', Carbon::today())->count();
        $entregadosHoy = ReciboPrenda::where('estado', 'entregado')
            ->whereDate('fecha_entrega_real', Carbon::today())
            ->count();
        $retrasados = ReciboPrenda::where('estado', '!=', 'entregado')
            ->where('fecha_entrega', '<', Carbon::now())
            ->count();

        return view('bodega.dashboard', [
            'totalPedidos' => $totalPedidos,
            'entregadosHoy' => $entregadosHoy,
            'retrasados' => $retrasados,
        ]);
    }

    /**
     * Guardar detalles de bodega por talla
     */
    public function guardarDetallesTalla(Request $request): JsonResponse
    {
        try {
            // Agregar logging para debugging
            \Log::info('Datos recibidos en guardarDetallesTalla', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'prenda_nombre' => 'nullable|string',
                'asesor' => 'nullable|string',
                'empresa' => 'nullable|string',
                'cantidad' => 'nullable|integer',
                'pendientes' => 'nullable|string',
                'observaciones_bodega' => 'nullable|string',
                'fecha_entrega' => 'nullable|date',
                'fecha_pedido' => 'nullable|date',
                'estado_bodega' => 'nullable|string|in:Pendiente,Entregado',
                'area' => 'nullable|string|in:Costura,EPP,Otro',
                'last_updated_at' => 'nullable|string', // Más flexible, accepts any string format
            ]);

            $usuario = auth()->user();
            $rolesDelUsuario = $usuario->getRoleNames()->toArray();
            
            // Obtener el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $validated['numero_pedido'])->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // PASO 1: GUARDAR FECHAS EN bodega_detalles_talla (compartido entre todos los roles)
            $datosBasicos = [
                'numero_pedido' => $validated['numero_pedido'],
                'talla' => $validated['talla'],
            ];

            $datosActualizarBasicos = [
                'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                'asesor' => $validated['asesor'] ?? null,
                'empresa' => $validated['empresa'] ?? null,
                'cantidad' => $validated['cantidad'] ?? 0,
                'pendientes' => $validated['pendientes'] ?? null,
                'observaciones_bodega' => $validated['observaciones_bodega'] ?? null,
                'fecha_entrega' => $validated['fecha_entrega'] ?? null,  // Fecha compartida
                'fecha_pedido' => $validated['fecha_pedido'] ?? null,    // Fecha compartida
                'usuario_bodega_id' => $usuario->id,
                'usuario_bodega_nombre' => $usuario->name,
            ];
            
            // Solo agreguar estado_bodega si es Bodeguero (no EPP ni Costura)
            if (!in_array('EPP-Bodega', $rolesDelUsuario) && !in_array('Costura-Bodega', $rolesDelUsuario)) {
                $datosActualizarBasicos['estado_bodega'] = $validated['estado_bodega'] ?? 'Pendiente';
            }

            // Guardar/actualizar en bodega_detalles_talla (siempre, todos los roles)
            $detalleBasico = \App\Models\BodegaDetallesTalla::updateOrCreate(
                [
                    'pedido_produccion_id' => $pedido->id,
                    'numero_pedido' => $validated['numero_pedido'],
                    'talla' => $validated['talla'],
                    'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                    'cantidad' => $validated['cantidad'] ?? 0,
                ],
                $datosActualizarBasicos
            );

            // PASO 2: GUARDAR ESTADO EN TABLA DEL ROL CORRESPONDIENTE
            $detalle = $detalleBasico; // Por defecto usar el detalle básico

            // Validar optimistic locking (conflictos de concurrencia)
            if (!empty($validated['last_updated_at'])) {
                $lastUpdatedAt = \Carbon\Carbon::parse($validated['last_updated_at']);
            } else {
                $lastUpdatedAt = null;
            }

            if (in_array('EPP-Bodega', $rolesDelUsuario)) {
                // GUARDAR ESTADO EN epp_bodega_detalles
                \Log::info('[EPP-BODEGA GUARDAR] Iniciando guardado para EPP-Bodega', [
                    'numero_pedido' => $validated['numero_pedido'],
                    'talla' => $validated['talla'],
                    'estado_bodega' => $validated['estado_bodega'] ?? null,
                    'roles_usuario' => $rolesDelUsuario
                ]);
                
                $detalleAnterior = EppBodegaDetalle::where('numero_pedido', $validated['numero_pedido'])
                    ->where('talla', $validated['talla'])
                    ->where('prenda_nombre', $validated['prenda_nombre'] ?? null)
                    ->where('cantidad', $validated['cantidad'] ?? 0)
                    ->first();
                
                $estadoAnterior = $detalleAnterior?->estado_bodega;
                $estadoNuevo = $validated['estado_bodega'] ?? null;
                
                \Log::info('[EPP-BODEGA GUARDAR] Estados', [
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $estadoNuevo
                ]);
                
                // Guardar estado específico de EPP-Bodega PRIMERO
                $datosEppEstado = [
                    'pedido_produccion_id' => $pedido->id,
                    'numero_pedido' => $validated['numero_pedido'],
                    'talla' => $validated['talla'],
                    'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                    'asesor' => $validated['asesor'] ?? null,
                    'empresa' => $validated['empresa'] ?? null,
                    'cantidad' => $validated['cantidad'] ?? 0,
                    'pendientes' => $validated['pendientes'] ?? null,
                    'observaciones_bodega' => $validated['observaciones_bodega'] ?? null,
                    'estado_bodega' => $estadoNuevo,
                    'usuario_bodega_id' => $usuario->id,
                    'usuario_bodega_nombre' => $usuario->name,
                ];

                $detalle = EppBodegaDetalle::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'numero_pedido' => $validated['numero_pedido'],
                        'talla' => $validated['talla'],
                        'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                        'cantidad' => $validated['cantidad'] ?? 0,
                    ],
                    $datosEppEstado
                );

                \Log::info('[EPP-BODEGA GUARDAR] Registro guardado', [
                    'detalle_id' => $detalle->id,
                    'estado_guardado' => $detalle->estado_bodega,
                    'datos_guardados' => $datosEppEstado
                ]);

                // AHORA crear la auditoría con el ID del registro guardado
                if ($estadoAnterior !== $estadoNuevo && !is_null($estadoNuevo)) {
                    EppBodegaAuditoria::create([
                        'epp_bodega_detalle_id' => $detalle->id,
                        'numero_pedido' => $validated['numero_pedido'],
                        'talla' => $validated['talla'],
                        'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $estadoNuevo,
                        'usuario_id' => $usuario->id,
                        'usuario_nombre' => $usuario->name,
                        'descripcion_cambio' => "Cambio de " . ($estadoAnterior ?? 'Nuevo') . " a " . $estadoNuevo,
                    ]);
                }
            } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
                // GUARDAR ESTADO EN costura_bodega_detalles
                $detalleAnterior = CosturaBodegaDetalle::where('numero_pedido', $validated['numero_pedido'])
                    ->where('talla', $validated['talla'])
                    ->where('prenda_nombre', $validated['prenda_nombre'] ?? null)
                    ->where('cantidad', $validated['cantidad'] ?? 0)
                    ->first();
                
                $estadoAnterior = $detalleAnterior?->estado_bodega;
                $estadoNuevo = $validated['estado_bodega'] ?? null;
                
                // Guardar estado específico de Costura-Bodega PRIMERO
                $datosCosturaEstado = [
                    'pedido_produccion_id' => $pedido->id,
                    'numero_pedido' => $validated['numero_pedido'],
                    'talla' => $validated['talla'],
                    'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                    'asesor' => $validated['asesor'] ?? null,
                    'empresa' => $validated['empresa'] ?? null,
                    'cantidad' => $validated['cantidad'] ?? 0,
                    'pendientes' => $validated['pendientes'] ?? null,
                    'observaciones_bodega' => $validated['observaciones_bodega'] ?? null,
                    'estado_bodega' => $estadoNuevo,
                    'usuario_bodega_id' => $usuario->id,
                    'usuario_bodega_nombre' => $usuario->name,
                ];

                $detalle = CosturaBodegaDetalle::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'numero_pedido' => $validated['numero_pedido'],
                        'talla' => $validated['talla'],
                        'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                        'cantidad' => $validated['cantidad'] ?? 0,
                    ],
                    $datosCosturaEstado
                );

                // AHORA crear la auditoría con el ID del registro guardado
                if ($estadoAnterior !== $estadoNuevo && !is_null($estadoNuevo)) {
                    CosturaBodegaAuditoria::create([
                        'costura_bodega_detalle_id' => $detalle->id,
                        'numero_pedido' => $validated['numero_pedido'],
                        'talla' => $validated['talla'],
                        'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $estadoNuevo,
                        'usuario_id' => $usuario->id,
                        'usuario_nombre' => $usuario->name,
                        'descripcion_cambio' => "Cambio de " . ($estadoAnterior ?? 'Nuevo') . " a " . $estadoNuevo,
                    ]);
                }
            } else {
                // BODEGUERO: guarda estado en bodega_detalles_talla (ya guardado arriba)
                $datosActualizarBasicos['area'] = $validated['area'] ?? null;
                $datosActualizarBasicos['estado_bodega'] = $validated['estado_bodega'] ?? null;
                
                $detalle = \App\Models\BodegaDetallesTalla::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'numero_pedido' => $validated['numero_pedido'],
                        'talla' => $validated['talla'],
                    ],
                    $datosActualizarBasicos
                );
            }

            // Disparar evento para tiempo real
            // Emitir evento de WebSocket (sin bloquear si falla)
            try {
                BodegaDetallesActualizados::dispatch(
                    $validated['numero_pedido'],
                    $validated['talla'],
                    [
                        'pendientes' => $validated['pendientes'] ?? null,
                        'observaciones_bodega' => $validated['observaciones_bodega'] ?? null,
                        'fecha_entrega' => $validated['fecha_entrega'] ?? null,
                        'fecha_pedido' => $validated['fecha_pedido'] ?? null,
                        'estado_bodega' => $validated['estado_bodega'] ?? null,
                        'area' => $validated['area'] ?? null,
                    ]
                );
            } catch (\Exception $websocketError) {
                // No bloquear el guardado si WebSocket falla
                \Log::warning('WebSocket no disponible para tiempo real, pero datos guardados correctamente', [
                    'websocket_error' => $websocketError->getMessage(),
                    'numero_pedido' => $validated['numero_pedido'],
                    'talla' => $validated['talla']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detalle guardado correctamente',
                'data' => $detalle
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en guardarDetallesTalla: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar todos los detalles de un pedido
     */
    public function guardarPedidoCompleto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'detalles' => 'required|array',
                'detalles.*.talla' => 'nullable|string',  // nullable para permitir EPPs sin talla
                'detalles.*.asesor' => 'nullable|string',  // Guardar asesor
                'detalles.*.empresa' => 'nullable|string',  // Guardar empresa
                'detalles.*.cantidad' => 'nullable|integer',  // Guardar cantidad
                'detalles.*.prenda_nombre' => 'nullable|string',  // Guardar nombre de la prenda
                'detalles.*.pendientes' => 'nullable|string',
                'detalles.*.observaciones_bodega' => 'nullable|string',
                'detalles.*.fecha_entrega' => 'nullable|date',
                'detalles.*.area' => 'nullable|string|in:Costura,EPP,Otro',
                'detalles.*.estado_bodega' => 'nullable|string|in:Pendiente,Entregado,Anulado',
            ]);

            $usuario = auth()->user();
            $pedido = PedidoProduccion::where('numero_pedido', $validated['numero_pedido'])->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $guardados = 0;
            $camposAuditar = ['asesor', 'empresa', 'cantidad', 'prenda_nombre', 'pendientes', 'observaciones_bodega', 'fecha_entrega', 'area', 'estado_bodega'];
            
            foreach ($validated['detalles'] as $detalle) {
                // La talla puede ser:
                // - Talla real para prendas (S, M, L, etc)
                // - Hash único para EPPs (md5 de nombre+cantidad)
                $talla = $detalle['talla'];
                $nombrePrenda = $detalle['prenda_nombre'] ?? null;
                $cantidad = $detalle['cantidad'] ?? 0;
                
                // Obtener registro anterior para auditoría
                // Búsqueda única: número_pedido + prenda_nombre + talla + cantidad
                $detalleAnterior = \App\Models\BodegaDetallesTalla::where('pedido_produccion_id', $pedido->id)
                    ->where('numero_pedido', $validated['numero_pedido'])
                    ->where('talla', $talla)
                    ->where('prenda_nombre', $nombrePrenda)
                    ->where('cantidad', $cantidad)
                    ->first();

                // Guardar/actualizar registro
                // La clave única es: numero_pedido + prenda_nombre + talla + cantidad
                $detalleGuardado = \App\Models\BodegaDetallesTalla::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'numero_pedido' => $validated['numero_pedido'],
                        'prenda_nombre' => $nombrePrenda,
                        'talla' => $talla,
                        'cantidad' => $cantidad,
                    ],
                    [
                        'asesor' => $detalle['asesor'] ?? null,  // Guardar asesor
                        'empresa' => $detalle['empresa'] ?? null,  // Guardar empresa
                        'pendientes' => $detalle['pendientes'] ?? null,
                        'observaciones_bodega' => $detalle['observaciones_bodega'] ?? null,
                        'fecha_entrega' => $detalle['fecha_entrega'] ?? null,
                        'area' => $detalle['area'] ?? null,
                        'estado_bodega' => $detalle['estado_bodega'] ?? null,
                        'usuario_bodega_id' => $usuario->id,
                        'usuario_bodega_nombre' => $usuario->name,
                    ]
                );

                // Registrar cambios en auditoría
                foreach ($camposAuditar as $campo) {
                    $valorAnterior = $detalleAnterior ? $detalleAnterior->{$campo} : null;
                    $valorNuevo = $detalle[$campo] ?? null;
                    
                    // Convertir null y strings vacíos a representación consistente
                    $valorAnteriorDisplay = ($valorAnterior === null || $valorAnterior === '') ? '' : $valorAnterior;
                    $valorNuevoDisplay = ($valorNuevo === null || $valorNuevo === '') ? '' : $valorNuevo;
                    
                    // Solo registrar si realmente cambió
                    if ($valorAnteriorDisplay !== $valorNuevoDisplay) {
                        \App\Models\BodegaAuditoria::create([
                            'bodega_detalles_talla_id' => $detalleGuardado->id,
                            'numero_pedido' => $validated['numero_pedido'],
                            'talla' => $talla,  // Usar talla tal como es (hash único para EPPs)
                            'campo_modificado' => $campo,
                            'valor_anterior' => $valorAnteriorDisplay,
                            'valor_nuevo' => $valorNuevoDisplay,
                            'usuario_id' => $usuario->id,
                            'usuario_nombre' => $usuario->name,
                            'ip_address' => $request->ip(),
                            'accion' => $detalleAnterior ? 'update' : 'create',
                            'descripcion' => ucfirst($campo) . ' cambió de "' . ($valorAnteriorDisplay ?: 'vacío') . '" a "' . ($valorNuevoDisplay ?: 'vacío') . '"',
                        ]);
                    }
                }
                
                $guardados++;
            }

            return response()->json([
                'success' => true,
                'message' => "$guardados registro(s) guardado(s) correctamente"
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en guardarPedidoCompleto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una nota de bodega
     */
    public function guardarNota(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'contenido' => 'required|string|max:5000',
            ]);

            $usuario = auth()->user();
            $roleNames = $usuario->getRoleNames()->toArray();
            
            // Obtener el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $validated['numero_pedido'])->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // Determinar el rol actual del usuario
            $rolActual = 'Bodeguero';
            if (in_array('Costura-Bodega', $roleNames)) {
                $rolActual = 'Costura-Bodega';
            } elseif (in_array('EPP-Bodega', $roleNames)) {
                $rolActual = 'EPP-Bodega';
            }

            // Guardar la nota
            $nota = BodegaNota::create([
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validated['numero_pedido'],
                'talla' => $validated['talla'],
                'contenido' => $validated['contenido'],
                'usuario_id' => $usuario->id,
                'usuario_nombre' => $usuario->name,
                'usuario_rol' => $rolActual,
                'ip_address' => $request->ip(),
            ]);

            // Disparar evento para tiempo real (temporalmente deshabilitado hasta solucionar Reverb)
            // BodegaNotasGuardada::dispatch(
            //     $validated['numero_pedido'],
            //     $validated['talla'],
            //     [
            //         'id' => $nota->id,
            //         'contenido' => $nota->contenido,
            //         'usuario_nombre' => $nota->usuario_nombre,
            //         'usuario_rol' => $nota->usuario_rol,
            //         'fecha' => $nota->created_at->format('d/m/Y'),
            //         'hora' => $nota->created_at->format('H:i:s'),
            //         'fecha_completa' => $nota->created_at->format('d/m/Y H:i:s'),
            //     ]
            // );

            return response()->json([
                'success' => true,
                'message' => 'Nota guardada exitosamente',
                'data' => $nota
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en guardarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la nota: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de notas para un pedido y talla
     */
    public function obtenerNotas(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
            ]);

            // Obtener notas ordenadas por fecha más reciente
            $notas = BodegaNota::where('numero_pedido', $validated['numero_pedido'])
                ->where('talla', $validated['talla'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($nota) {
                    return [
                        'id' => $nota->id,
                        'usuario_id' => $nota->usuario_id,
                        'contenido' => $nota->contenido,
                        'usuario_nombre' => $nota->usuario_nombre,
                        'usuario_rol' => $nota->usuario_rol,
                        'fecha' => $nota->created_at->format('d/m/Y'),
                        'hora' => $nota->created_at->format('H:i:s'),
                        'fecha_completa' => $nota->created_at->format('d/m/Y H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $notas
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNotas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas'
            ], 500);
        }
    }

    /**
     * Actualizar una nota existente
     */
    public function actualizarNota(Request $request, $notaId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            $usuario = auth()->user();
            $nota = BodegaNota::findOrFail($notaId);

            // Verificar que el usuario sea el dueño de la nota o tenga permisos
            if ($nota->usuario_id !== $usuario->id && !$usuario->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta nota'
                ], 403);
            }

            // Actualizar la nota
            $nota->update([
                'contenido' => $validated['contenido'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente',
                'data' => $nota
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en actualizarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota'
            ], 500);
        }
    }

    /**
     * Eliminar una nota
     */
    public function eliminarNota(Request $request, $notaId): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $nota = BodegaNota::findOrFail($notaId);

            // Verificar que el usuario sea el dueño de la nota o tenga permisos
            if ($nota->usuario_id !== $usuario->id && !$usuario->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar esta nota'
                ], 403);
            }

            // Eliminar la nota
            $nota->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en eliminarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota'
            ], 500);
        }
    }

}
