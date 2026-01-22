<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Application\Services\Asesores\CrearPedidoService;
use App\Application\Services\Asesores\ObtenerFotosService;
use App\Application\Services\Asesores\AnularPedidoService;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

/**
 * AsesoresAPIController
 * 
 * Controlador DDD para APIs de asesores.
 * Delega toda la lógica de negocio a servicios de aplicación.
 * 
 * Responsabilidades:
 * - Validar entrada HTTP
 * - Llamar a servicios de aplicación
 * - Formatear respuesta HTTP
 */
class AsesoresAPIController extends Controller
{
    protected CrearPedidoService $crearPedidoService;
    protected ObtenerFotosService $obtenerFotosService;
    protected AnularPedidoService $anularPedidoService;
    protected PedidoProduccionRepository $pedidoProduccionRepository;

    public function __construct(
        CrearPedidoService $crearPedidoService,
        ObtenerFotosService $obtenerFotosService,
        AnularPedidoService $anularPedidoService,
        PedidoProduccionRepository $pedidoProduccionRepository
    ) {
        $this->crearPedidoService = $crearPedidoService;
        $this->obtenerFotosService = $obtenerFotosService;
        $this->anularPedidoService = $anularPedidoService;
        $this->pedidoProduccionRepository = $pedidoProduccionRepository;
        $this->middleware('auth');
    }

    /**
     * POST /asesores/pedidos
     * Crear nuevo pedido (prendas o logo)
     */
    public function store(Request $request)
    {
        $productosKey = $request->has('productos') ? 'productos' : 'productos_friendly';

        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:69',
            'area' => 'nullable|string',
            $productosKey => 'required|array|min:1',
            $productosKey.'.*.nombre_producto' => 'required|string',
            $productosKey.'.*.descripcion' => 'nullable|string',
            $productosKey.'.*.tella' => 'nullable|string',
            $productosKey.'.*.tipo_manga' => 'nullable|string',
            $productosKey.'.*.color' => 'nullable|string',
            $productosKey.'.*.talla' => 'nullable|string',
            $productosKey.'.*.genero' => 'nullable|string',
            $productosKey.'.*.cantidad' => 'required|integer|min:1',
            $productosKey.'.*.ref_hilo' => 'nullable|string',
            $productosKey.'.*.precio_unitario' => 'nullable|numeric|min:0',
            $productosKey.'.*.telas' => 'nullable|array',
            $productosKey.'.*.telas.*.tela_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.color_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.referencia' => 'nullable|string',
            'logo.descripcion' => 'nullable|string',
            'logo.observaciones_tecnicas' => 'nullable|string',
            'logo.tecnicas' => 'nullable|string',
            'logo.ubicaciones' => 'nullable|string',
            'logo.observaciones_generales' => 'nullable|string',
            'logo.imagenes' => 'nullable|array',
            'logo.imagenes.*' => 'nullable|file|image|max:5242880',
            'tipo_cotizacion' => 'nullable|string',
            'cotizacion_id' => 'nullable|integer',
        ]);

        try {
            $datosParaCrear = [
                'cliente' => $validated['cliente'],
                'forma_de_pago' => $validated['forma_de_pago'] ?? null,
                'area' => $validated['area'] ?? null,
                $productosKey => $validated[$productosKey],
                'logo' => [
                    'descripcion' => $validated['logo.descripcion'] ?? null,
                    'observaciones_tecnicas' => $validated['logo.observaciones_tecnicas'] ?? null,
                    'tecnicas' => $validated['logo.tecnicas'] ?? null,
                    'ubicaciones' => $validated['logo.ubicaciones'] ?? null,
                    'observaciones_generales' => json_decode($validated['logo.observaciones_generales'] ?? '[]', true),
                    'imagenes' => $request->file('logo.imagenes') ?? [],
                ],
                'cotizacion_id' => $validated['cotizacion_id'] ?? null,
                'archivos' => $request->allFiles(),
            ];

            $resultado = $this->crearPedidoService->crear($datosParaCrear, $validated['tipo_cotizacion'] ?? null);

            if (is_int($resultado)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pedido de logo guardado correctamente',
                    'logo_pedido_id' => $resultado,
                    'tipo' => 'logo'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado como borrador',
                'borrador_id' => $resultado->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creando pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/confirm
     * Confirmar pedido y asignar número
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'borrador_id' => 'required|integer|exists:pedidos_produccion,id',
            'numero_pedido' => 'required|integer|unique:pedidos_produccion,numero_pedido',
        ]);

        try {
            $pedido = PedidoProduccion::findOrFail($validated['borrador_id']);
            $pedido->update(['numero_pedido' => $validated['numero_pedido']]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente con ID: ' . $validated['numero_pedido'],
                'pedido' => $validated['numero_pedido']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/recibos-datos
     * Obtener datos de recibos dinámicos
     */
    public function obtenerDatosRecibos($id)
    {
        try {
            $pedidoId = $id;
            \Log::info('[RECIBOS] Obteniendo datos de recibos para pedido: ' . $pedidoId);

            $pedido = PedidoProduccion::find($pedidoId);

            if (!$pedido) {
                return response()->json(['error' => 'Pedido no encontrado'], 404);
            }

            if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
                return response()->json(['error' => 'No tienes permiso para ver este pedido'], 403);
            }

            $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos($pedidoId);

            \Log::info('[RECIBOS] Datos obtenidos correctamente', [
                'pedido_id' => $pedidoId,
                'prendas' => count($datos['prendas']),
            ]);

            return response()->json($datos);

        } catch (\Exception $e) {
            \Log::error('[RECIBOS] Error obteniendo datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo datos de los recibos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /asesores/prendas-pedido/{prendaPedidoId}/fotos
     * Obtener fotos de una prenda de pedido
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId)
    {
        try {
            $resultado = $this->obtenerFotosService->obtenerFotosPrendaPedido($prendaPedidoId);
            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('[FOTOS] Error obteniendo fotos', [
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo fotos: ' . $e->getMessage(),
                'fotos' => [],
            ], $this->getHttpStatusCode($e));
        }
    }

    /**
     * POST /asesores/pedidos/{id}/anular
     * Anular un pedido
     */
    public function anularPedido(Request $request, $id)
    {
        $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ], [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ]);

        try {
            $pedido = $this->anularPedidoService->anular($id, $request->novedad);

            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'pedido' => $pedido,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error anulando pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getHttpStatusCode($e));
        }
    }

    /**
     * GET /asesores/pedidos/{id}/editar-datos
     * Obtener datos completos del pedido para edición (formato JSON)
     */
    public function obtenerDatosEdicion($id)
    {
        try {
            $pedido = PedidoProduccion::with([
                'prendas' => function($query) {
                    $query->with([
                        'fotos',
                        'coloresTelas' => function($q) {
                            $q->with(['color', 'tela', 'fotos']);
                        },
                        'fotosTelas',
                        'variantes' => function($q) {
                            $q->with(['tipoManga', 'tipoBroche']);
                        },
                        'procesos' => function($q) {
                            $q->with(['imagenes']);
                        }
                    ]);
                },
                'epps' => function($query) {
                    $query->with(['epp', 'imagenes']);
                }
            ])->findOrFail($id);

            // Verificar permisos
            if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
                return response()->json(['error' => 'No tienes permiso para editar este pedido'], 403);
            }

            // Preparar datos de prendas
            $prendasData = $pedido->prendas->map(function($prenda) {
                // Convertir IDs de tallas a nombres
                $cantidadTallaConNombres = [];
                $cantidadTalla = $prenda->cantidad_talla;
                if (is_string($cantidadTalla)) {
                    $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
                }
                
                if ($cantidadTalla && is_array($cantidadTalla)) {
                    foreach ($cantidadTalla as $tallaId => $cantidad) {
                        if ($cantidad > 0) {
                            $talla = \App\Models\Talla::find($tallaId);
                            $nombreTalla = $talla ? $talla->nombre : $tallaId;
                            $cantidadTallaConNombres[$nombreTalla] = $cantidad;
                        }
                    }
                }

                // Preparar variantes
                $variantes = [];
                if ($prenda->variantes && count($prenda->variantes) > 0) {
                    foreach ($prenda->variantes as $variante) {
                        $variantes[] = [
                            'id' => $variante->id,
                            'tipo_manga_id' => $variante->tipo_manga_id,
                            'tipo_manga_nombre' => $variante->tipoManga?->nombre,
                            'manga_obs' => $variante->manga_obs,
                            'tipo_broche_id' => $variante->tipo_broche_boton_id,
                            'tipo_broche_nombre' => $variante->tipoBroche?->nombre,
                            'broche_boton_obs' => $variante->broche_boton_obs,
                            'tiene_bolsillos' => $variante->tiene_bolsillos,
                            'bolsillos_obs' => $variante->bolsillos_obs
                        ];
                    }
                }

                // Preparar procesos
                $procesos = [];
                if ($prenda->procesos && count($prenda->procesos) > 0) {
                    foreach ($prenda->procesos as $proceso) {
                        $ubicacionesData = [];
                        if ($proceso->ubicaciones) {
                            if (is_string($proceso->ubicaciones)) {
                                $ubicacionesData = json_decode($proceso->ubicaciones, true) ?? [];
                            } else if (is_array($proceso->ubicaciones)) {
                                $ubicacionesData = $proceso->ubicaciones;
                            }
                        }
                        
                        $tallasDama = [];
                        if ($proceso->tallas_dama) {
                            if (is_string($proceso->tallas_dama)) {
                                $tallasDama = json_decode($proceso->tallas_dama, true) ?? [];
                            } else if (is_array($proceso->tallas_dama)) {
                                $tallasDama = $proceso->tallas_dama;
                            }
                        }
                        
                        $tallasCalballero = [];
                        if ($proceso->tallas_caballero) {
                            if (is_string($proceso->tallas_caballero)) {
                                $tallasCalballero = json_decode($proceso->tallas_caballero, true) ?? [];
                            } else if (is_array($proceso->tallas_caballero)) {
                                $tallasCalballero = $proceso->tallas_caballero;
                            }
                        }
                        
                        $tipoProceso = 'Proceso';
                        if ($proceso->tipo_proceso_id) {
                            $tipoProcesoDB = \App\Models\TipoProceso::find($proceso->tipo_proceso_id);
                            if ($tipoProcesoDB) {
                                $tipoProceso = $tipoProcesoDB->nombre;
                            }
                        }
                        
                        $procesos[] = [
                            'id' => $proceso->id,
                            'tipo_proceso_id' => $proceso->tipo_proceso_id,
                            'tipo' => $tipoProceso,
                            'nombre' => $tipoProceso,
                            'observaciones' => $proceso->observaciones,
                            'ubicaciones' => is_array($ubicacionesData) ? $ubicacionesData : [],
                            'tallas_dama' => $tallasDama,
                            'tallas_caballero' => $tallasCalballero,
                            'estado' => $proceso->estado,
                            'imagenes' => $proceso->imagenes ? $proceso->imagenes->map(function($img) {
                                return [
                                    'id' => $img->id,
                                    'url' => $img->url,
                                    'ruta' => $img->ruta_webp ?? $img->ruta_original
                                ];
                            })->toArray() : []
                        ];
                    }
                }

                return [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'cantidad' => $prenda->cantidad,
                    'descripcion' => $prenda->descripcion,
                    'cantidad_talla' => $cantidadTallaConNombres,
                    'color_id' => $prenda->color_id,
                    'color_nombre' => $prenda->color?->nombre ?? null,
                    'tela_id' => $prenda->tela_id,
                    'tela_nombre' => $prenda->tela?->nombre ?? null,
                    'tipo_manga_id' => $prenda->tipo_manga_id,
                    'tipo_manga_nombre' => $prenda->tipoManga?->nombre ?? null,
                    'tipo_broche_id' => $prenda->tipo_broche_id,
                    'tipo_broche_nombre' => $prenda->tipoBrocheBoton?->nombre ?? null,
                    'tiene_bolsillos' => $prenda->tiene_bolsillos,
                    'tiene_reflectivo' => $prenda->tiene_reflectivo,
                    'fotos' => $prenda->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'ruta' => $foto->url,
                            'url' => $foto->url
                        ];
                    })->toArray(),
                    'fotos_tela' => $prenda->fotosTelas->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'ruta' => $foto->url,
                            'url' => $foto->url
                        ];
                    })->toArray(),
                    'procesos' => $procesos
                ];
            });

            // Preparar EPP
            $eppData = [];
            if ($pedido->epps && count($pedido->epps) > 0) {
                foreach ($pedido->epps as $epp) {
                    $eppData[] = [
                        'id' => $epp->id,
                        'epp_id' => $epp->epp_id,
                        'epp_nombre' => $epp->epp?->nombre ?? 'EPP sin nombre',
                        'epp_codigo' => $epp->epp?->codigo ?? '',
                        'cantidad' => $epp->cantidad,
                        'observaciones' => $epp->observaciones,
                        'imagenes' => $epp->imagenes ? $epp->imagenes->map(function($img) {
                            return [
                                'id' => $img->id,
                                'ruta' => $img->ruta_web ?? $img->ruta_original,
                                'url' => $img->ruta_web ?? $img->ruta_original,
                                'principal' => $img->principal ?? false
                            ];
                        })->toArray() : []
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'pedido' => [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'forma_de_pago' => $pedido->forma_de_pago,
                    'estado' => $pedido->estado,
                    'descripcion' => $pedido->descripcion,
                    'novedades' => $pedido->novedades,
                    'area' => $pedido->area,
                    'prendas' => $prendasData,
                    'epp' => $eppData
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('[EDICION-DATOS] Error obteniendo datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo datos del pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener código HTTP de excepción
     */
    protected function getHttpStatusCode(\Exception $e): int
    {
        if (str_contains($e->getMessage(), 'No tienes permiso')) {
            return 403;
        }
        if (str_contains($e->getMessage(), 'no encontrado')) {
            return 404;
        }
        return 500;
    }
}
