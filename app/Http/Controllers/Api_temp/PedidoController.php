<?php

namespace App\Http\Controllers\Api_temp;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontrado;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalido;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;

/**
 * PedidoController
 * 
 * Controlador para gestionar pedidos usando DDD (Fase 3)
 * 
 * Endpoints:
 * - POST /api/pedidos → Crear pedido (CrearPedidoUseCase)
 * - PATCH /api/pedidos/{id}/confirmar → Confirmar pedido (ConfirmarPedidoUseCase)
 * - GET /api/pedidos/{id} → Obtener pedido (Lectura directa)
 */
class PedidoController extends Controller
{
    public function __construct(
        private CrearPedidoUseCase $crearPedidoUseCase,
        private ConfirmarPedidoUseCase $confirmarPedidoUseCase,
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ListarPedidosPorClienteUseCase $listarPedidosPorClienteUseCase,
        private CancelarPedidoUseCase $cancelarPedidoUseCase,
        private PedidoRepository $pedidoRepository
    ) {}

    /**
     * POST /api/pedidos
     * 
     * Crear un nuevo pedido usando DDD
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar entrada básica
            $request->validate([
                'cliente_id' => 'required|integer',
                'descripcion' => 'required|string|max:1000',
                'observaciones' => 'nullable|string|max:1000',
                'prendas' => 'required|array|min:1',
                'prendas.*.prenda_id' => 'required|integer',
                'prendas.*.descripcion' => 'required|string',
                'prendas.*.cantidad' => 'required|integer|min:1',
                'prendas.*.tallas' => 'required|array',
            ]);

            // Crear DTO desde request
            $dto = CrearPedidoDTO::fromRequest($request->all());

            // Ejecutar Use Case
            $response = $this->crearPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => $response->mensaje,
                'data' => $response->toArray()
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/confirmar
     * 
     * Confirmar un pedido existente
     */
    public function confirmar(int $id): JsonResponse
    {
        try {
            // Ejecutar Use Case
            $response = $this->confirmarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido confirmado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (PedidoNoEncontrado $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);

        } catch (EstadoPedidoInvalido $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede confirmar el pedido: ' . $e->getMessage(),
            ], 422);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/{id}/cancelar
     * 
     * Cancelar un pedido
     */
    public function cancelar(int $id): JsonResponse
    {
        try {
            $response = $this->cancelarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/actualizar-descripcion
     * 
     * Actualizar descripción de un pedido con justificación
     */
    public function actualizarDescripcion(Request $request, int $id): JsonResponse
    {
        try {
            \Log::info('[actualizarDescripcion] Iniciando', [
                'pedido_id' => $id,
                'metodo' => $request->method(),
                'ruta' => $request->path(),
            ]);
            
            $request->validate([
                'descripcion' => 'nullable|string|max:2000',
                'cliente' => 'nullable|string|max:500',
                'forma_de_pago' => 'nullable|string|max:500',
                'novedades' => 'nullable|string|max:2000',
                'justificacion' => 'nullable|string|max:1000'
            ]);

            // Obtener directamente del modelo (no usar repository que podría cachear)
            $pedido = \App\Models\PedidoProduccion::findOrFail($id);
            
            // Actualizar cliente si viene
            if ($request->has('cliente') && !is_null($request->input('cliente'))) {
                $pedido->cliente = $request->input('cliente');
            }
            
            // Actualizar forma_de_pago si viene
            if ($request->has('forma_de_pago') && !is_null($request->input('forma_de_pago'))) {
                $pedido->forma_de_pago = $request->input('forma_de_pago');
            }
            
            // Actualizar novedades - PRIMERO
            if ($request->has('novedades') && !is_null($request->input('novedades'))) {
                $pedido->novedades = $request->input('novedades');
            }
            
            // DESPUÉS agregar la justificación a novedades existentes
            if ($request->has('justificacion') && !is_null($request->input('justificacion')) && !empty($request->input('justificacion'))) {
                $justificacion = $request->input('justificacion');
                $novedadesActuales = $pedido->novedades ?: '';
                
                // Obtener información del usuario
                $usuario = auth()->user();
                
                \Log::info('[actualizarDescripcion] Usuario autenticado:', [
                    'usuario' => $usuario ? $usuario->toArray() : null,
                    'auth_check' => auth()->check(),
                    'usuario_id' => auth()->id(),
                ]);
                
                $nombreUsuario = 'Sistema';
                $rolUsuario = 'Sin rol';
                
                if ($usuario) {
                    $nombreUsuario = $usuario->name ?: 'Usuario';
                    \Log::info('[actualizarDescripcion] Nombre del usuario:', ['nombre' => $nombreUsuario]);
                    
                    // Obtener el rol principal
                    $rolesUsuario = $usuario->roles();
                    \Log::info('[actualizarDescripcion] Roles del usuario:', [
                        'roles_ids' => $usuario->roles_ids,
                        'roles_count' => $rolesUsuario->count(),
                        'roles_data' => $rolesUsuario->get()->toArray(),
                    ]);
                    
                    if ($rolesUsuario && $rolesUsuario->count() > 0) {
                        $rolUsuario = $rolesUsuario->first()->name ?? 'Sin rol';
                    }
                }
                
                \Log::info('[actualizarDescripcion] Registro de novedad:', [
                    'usuario_final' => $nombreUsuario,
                    'rol_final' => $rolUsuario,
                ]);
                
                $fechaActual = now()->format('d/m/Y H:i');
                
                // Construir registro con información completa
                $registroNovedad = "[{$nombreUsuario} - {$rolUsuario} - {$fechaActual}]\n{$justificacion}";
                
                // Si ya hay novedades, agregar con separador
                if (!empty($novedadesActuales)) {
                    $pedido->novedades = $novedadesActuales . "\n\n" . $registroNovedad;
                } else {
                    $pedido->novedades = $registroNovedad;
                }
            }

            // Guardar directamente en BD
            $pedido->save();

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados exitosamente',
                'data' => $pedido->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('[actualizarDescripcion] Error:', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar cambios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/actualizar-estado
     * 
     * Actualizar estado de un pedido
     */
    public function actualizarEstado(Request $request, int $id): JsonResponse
    {
        try {
            \Log::info('[actualizarEstado] Iniciando', [
                'pedido_id' => $id,
                'nuevo_estado' => $request->input('estado'),
                'metodo' => $request->method(),
                'ruta' => $request->path(),
                'usuario_id' => auth()->id()
            ]);

            // Validar entrada
            $request->validate([
                'estado' => 'required|string|in:Pendiente,No iniciado,En Ejecución,Entregado,Anulada,PENDIENTE_SUPERVISOR,PENDIENTE_INSUMOS,pendiente_cartera,RECHAZADO_CARTERA,DEVUELTO_A_ASESORA'
            ]);

            $nuevoEstado = $request->input('estado');

            // Buscar el pedido
            $pedido = \App\Models\PedidoProduccion::find($id);
            if (!$pedido) {
                \Log::warning('[actualizarEstado] Pedido no encontrado', ['pedido_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $estadoAnterior = $pedido->estado;

            // Si el estado es el mismo, no hacer nada
            if ($estadoAnterior === $nuevoEstado) {
                \Log::info('[actualizarEstado] El estado es el mismo, no se actualiza', [
                    'pedido_id' => $id,
                    'estado' => $nuevoEstado
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'El estado ya es el mismo',
                    'data' => [
                        'id' => $pedido->id,
                        'estado' => $nuevoEstado,
                        'estado_anterior' => $estadoAnterior
                    ]
                ]);
            }

            // Actualizar estado
            $pedido->estado = $nuevoEstado;
            $pedido->save();

            // Registrar en novedades si hay cambio
            if ($estadoAnterior !== $nuevoEstado) {
                $usuario = auth()->user();
                $nombreUsuario = $usuario ? $usuario->name : 'Sistema';
                
                $novedad = "Estado cambiado de '{$estadoAnterior}' a '{$nuevoEstado}' por {$nombreUsuario}";
                
                if (!empty($pedido->novedades)) {
                    $pedido->novedades .= "\n\n" . $novedad;
                } else {
                    $pedido->novedades = $novedad;
                }
                
                $pedido->save();
                
                \Log::info('[actualizarEstado] Novedad registrada', [
                    'pedido_id' => $id,
                    'novedad' => $novedad
                ]);
            }

            \Log::info('[actualizarEstado] Estado actualizado exitosamente', [
                'pedido_id' => $id,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => [
                    'id' => $pedido->id,
                    'estado' => $nuevoEstado,
                    'estado_anterior' => $estadoAnterior,
                    'novedades' => $pedido->novedades
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('[actualizarEstado] Error:', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{id}
     * 
     * Obtener un pedido (lectura - CQRS read side)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $response = $this->obtenerPedidoUseCase->ejecutar($id);
            
            // Transformar datos a array
            $datos = $response->toArray();
            
            // Enriquecer procesos con tallas_transformadas para que el modal pueda accederlas
            if (isset($datos['prendas']) && is_array($datos['prendas'])) {
                foreach ($datos['prendas'] as &$prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        foreach ($prenda['procesos'] as &$proceso) {
                            if (isset($proceso['id'])) {
                                // Obtener tallas del proceso
                                $tallas = \DB::table('pedidos_procesos_prenda_tallas')
                                    ->where('proceso_prenda_detalle_id', $proceso['id'])
                                    ->get();
                                
                                \Log::info('[PedidoController-PROCESOS-TALLAS] Tallas obtenidas', [
                                    'proceso_id' => $proceso['id'],
                                    'tallas_count' => $tallas->count(),
                                    'tallas' => $tallas->toArray()
                                ]);
                                
                                // Transformar a formato por género CON COLORES
                                $talasTransformadas = [
                                    'dama' => [],
                                    'caballero' => [],
                                    'unisex' => []
                                ];
                                
                                foreach ($tallas as $talla) {
                                    $genero = strtolower($talla->genero ?? 'caballero');
                                    if ($genero === 'dama') $genero = 'dama';
                                    else if ($genero === 'caballero') $genero = 'caballero';
                                    else $genero = 'unisex';
                                    
                                    // Obtener colores para esta talla
                                    $colores = \DB::table('pedidos_procesos_prenda_talla_colores')
                                        ->where('pedidos_procesos_prenda_talla_id', $talla->id)
                                        ->get();
                                    
                                    \Log::info('[PedidoController-TALLAS-COLORES] DEBUG', [
                                        'pedido_id' => $id,
                                        'proceso_id' => $proceso['id'],
                                        'talla_id' => $talla->id,
                                        'talla_valor' => $talla->talla,
                                        'genero' => $genero,
                                        'cantidad_talla' => $talla->cantidad,
                                        'colores_encontrados' => $colores->count(),
                                        'colores_data' => $colores->map(function($c) { return ['color' => $c->color_nombre, 'cant' => $c->cantidad]; })->toArray()
                                    ]);
                                    
                                    if ($colores->count() > 0) {
                                        // Si hay colores, usar estructura con colores
                                        $talasTransformadas[$genero][$talla->talla] = $colores->map(function($color) {
                                            return [
                                                'color' => $color->color_nombre,
                                                'cantidad' => $color->cantidad
                                            ];
                                        })->toArray();
                                    } else {
                                        // Sin colores, usar cantidad simple
                                        $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                                    }
                                }
                                
                                \Log::info('[PedidoController-PROCESOS-FINAL] Transformadas', [
                                    'proceso_id' => $proceso['id'],
                                    'tallas_transformadas' => $talasTransformadas
                                ]);
                                
                                // Agregar al proceso con nombre 'tallas' para que el renderer lo encuentre
                                $proceso['tallas'] = $talasTransformadas;
                            }
                        }
                        unset($proceso);
                    }
                }
                unset($prenda);
            }
            
            // Agregar fecha de creación si no existe
            if (!isset($datos['fecha_creacion'])) {
                $pedido = \App\Models\PedidoProduccion::find($id);
                if ($pedido) {
                    $fechaCreacion = $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at;
                    $datos['fecha_creacion'] = $fechaCreacion 
                        ? (is_string($fechaCreacion) ? $fechaCreacion : $fechaCreacion->format('d/m/Y'))
                        : date('d/m/Y');
                }
            }
            
            // Cargar estado de entrega de cada prenda
            if (isset($datos['prendas']) && is_array($datos['prendas'])) {
                foreach ($datos['prendas'] as &$prenda) {
                    if (isset($prenda['id'])) {
                        try {
                            $tallasPorGenero = [
                                'DAMA' => [],
                                'CABALLERO' => [],
                                'UNISEX' => []
                            ];

                            $tallasColores = \DB::table('prenda_pedido_talla_colores as pptc')
                                ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                                ->where('ppt.prenda_pedido_id', $prenda['id'])
                                ->select([
                                    'ppt.genero',
                                    'ppt.talla',
                                    'pptc.color_nombre',
                                    'pptc.cantidad'
                                ])
                                ->get();

                            if ($tallasColores->count() > 0) {
                                foreach ($tallasColores as $tallaColor) {
                                    $genero = strtoupper((string)($tallaColor->genero ?? ''));
                                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                                        $genero = 'CABALLERO';
                                    }
                                    $talla = (string)($tallaColor->talla ?? '');
                                    $color = (string)($tallaColor->color_nombre ?? '');
                                    $cantidad = (int)($tallaColor->cantidad ?? 0);

                                    if ($talla === '' || $cantidad <= 0) {
                                        continue;
                                    }

                                    if (!isset($tallasPorGenero[$genero][$talla])) {
                                        $tallasPorGenero[$genero][$talla] = [];
                                    }

                                    $tallasPorGenero[$genero][$talla][] = [
                                        'cantidad' => $cantidad,
                                        'color' => $color !== '' ? $color : null,
                                    ];
                                }

                                $prenda['tallas'] = $tallasPorGenero;
                            }
                        } catch (\Exception $e) {
                            \Log::warning('[PedidoController::show] Error cargando tallas con color para prenda', [
                                'pedido_id' => $id,
                                'prenda_id' => $prenda['id'],
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $entrega = \App\Models\PrendaEntrega::where('prenda_pedido_id', $prenda['id'])->first();
                        $prenda['entrega'] = $entrega ? [
                            'entregado' => $entrega->entregado,
                            'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                            'usuario' => $entrega->usuario?->name,
                        ] : null;
                        
                        // Agregar recibos parciales (ANEXOS) a cada prenda
                        try {
                            $recibosParciales = \DB::table('pedidos_parciales')
                                ->where('pedido_produccion_id', $id)
                                ->where('prenda_pedido_id', $prenda['id'])
                                ->orderBy('tipo_recibo', 'asc')
                                ->orderBy('id', 'asc')
                                ->get();
                            
                            if ($recibosParciales->count() > 0) {
                                // Agrupar por tipo_recibo para asignar números de anexo
                                $anexosPorTipo = [];
                                $procesosAdicionales = [];
                                
                                foreach ($recibosParciales as $reciboParcial) {
                                    $tipoRecibo = $reciboParcial->tipo_recibo;
                                    
                                    // Contar anexos del mismo tipo
                                    if (!isset($anexosPorTipo[$tipoRecibo])) {
                                        $anexosPorTipo[$tipoRecibo] = 0;
                                    }
                                    $anexosPorTipo[$tipoRecibo]++;
                                    
                                    // El consecutivo real del anexo se guarda en consecutivo_actual.
                                    // Algunos flujos antiguos pudieron haber usado numero_recibo.
                                    $numeroReciboAnexo = $reciboParcial->consecutivo_actual ?? $reciboParcial->numero_recibo ?? null;
                                    
                                    // Obtener las tallas del recibo parcial
                                    $tallas = \DB::table('pedidos_parciales_tallas')
                                        ->where('pedido_parcial_id', $reciboParcial->id)
                                        ->get();
                                    
                                    $tallasList = [];
                                    $talasTransformadas = [
                                        'dama' => [],
                                        'caballero' => [],
                                        'unisex' => []
                                    ];
                                    
                                    foreach ($tallas as $talla) {
                                        $tallasList[] = [
                                            'talla' => $talla->talla,
                                            'cantidad' => $talla->cantidad,
                                            'genero' => $talla->genero ?? 'General'
                                        ];
                                        
                                        // Transformar a formato por género para el modal
                                        $genero = strtolower($talla->genero ?? 'caballero');
                                        if ($genero === 'dama') $genero = 'dama';
                                        else if ($genero === 'caballero') $genero = 'caballero';
                                        else $genero = 'unisex';
                                        
                                        $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                                    }
                                    
                                    $procesosAdicionales[] = [
                                        'tipo_proceso' => $tipoRecibo,  // Mantener el tipo real para el API
                                        'nombre_proceso' => $tipoRecibo . ' ANEXO ' . $anexosPorTipo[$tipoRecibo],  // Solo para mostrar
                                        'estado' => $reciboParcial->estado ?? 'PENDIENTE',
                                        'numero_recibo' => $numeroReciboAnexo,
                                        'es_parcial' => true,
                                        'numero_anexo' => $anexosPorTipo[$tipoRecibo],
                                        'pedido_parcial_id' => $reciboParcial->id,
                                        'tallas' => $tallasList,
                                        'tallas_transformadas' => $talasTransformadas,  // Para que el modal lo encuentre
                                        'created_at' => $reciboParcial->created_at,
                                    ];
                                }
                                
                                // Agregar los procesos adicionales al array de procesos de la prenda
                                if (!isset($prenda['procesos'])) {
                                    $prenda['procesos'] = [];
                                }
                                $prenda['procesos'] = array_merge($prenda['procesos'], $procesosAdicionales);
                            }
                        } catch (\Exception $e) {
                            \Log::error('[PedidoController::show] Error cargando recibos parciales', [
                                'prenda_id' => $prenda['id'],
                                'pedido_id' => $id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
                unset($prenda); // Romper referencia
            }
            
            // Agregar EPPs transformados con imágenes
            $eppsList = [];
            try {
                $pedido = \App\Models\PedidoProduccion::find($id);
                \Log::info('[PedidoController::show] Buscando EPPs', [
                    'pedido_id' => $id,
                    'tiene_epps' => $pedido && $pedido->epps ? $pedido->epps->count() : 0,
                ]);
                
                if ($pedido && $pedido->epps) {
                    foreach ($pedido->epps as $pedidoEpp) {
                        $epp = $pedidoEpp->epp;
                        
                        if (!$epp) {
                            \Log::warning('[PedidoController::show] EPP sin relación válida', [
                                'pedido_epp_id' => $pedidoEpp->id,
                            ]);
                            continue;
                        }
                        
                        // Obtener imágenes del EPP
                        $imagenes = [];
                        try {
                            $imagenesData = \DB::table('pedido_epp_imagenes')
                                ->where('pedido_epp_id', $pedidoEpp->id)
                                ->orderBy('orden', 'asc')
                                ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                            
                            \Log::info('[PedidoController::show] Buscando imágenes de EPP', [
                                'pedido_epp_id' => $pedidoEpp->id,
                                'imagenes_encontradas' => $imagenesData->count(),
                            ]);
                            
                            if ($imagenesData->count() > 0) {
                                foreach ($imagenesData as $img) {
                                    $ruta = $img->ruta_web ?? $img->ruta_original;
                                    
                                    // Saltar si la ruta está vacía
                                    if (empty($ruta)) {
                                        \Log::warning('[PedidoController::show] Imagen sin ruta', [
                                            'pedido_epp_id' => $pedidoEpp->id,
                                        ]);
                                        continue;
                                    }
                                    
                                    \Log::debug('[PedidoController::show] Procesando imagen', [
                                        'ruta_original' => $ruta,
                                    ]);
                                    
                                    // Normalizar ruta
                                    if (!str_starts_with($ruta, '/storage/')) {
                                        if (str_starts_with($ruta, 'storage/')) {
                                            $ruta = '/' . $ruta;
                                        } else {
                                            $ruta = '/storage/' . $ruta;
                                        }
                                    }
                                    
                                    $imagenes[] = [
                                        'ruta_webp' => $ruta,
                                        'ruta_original' => $ruta,
                                        'ruta_web' => $ruta,
                                        'principal' => $img->principal ?? false,
                                        'orden' => $img->orden ?? 0,
                                    ];
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::error('[PedidoController::show] Error obtener imágenes de EPP', [
                                'pedido_epp_id' => $pedidoEpp->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                        
                        $eppsList[] = [
                            'id' => $pedidoEpp->id,
                            'epp_id' => $pedidoEpp->epp_id,
                            'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'cantidad' => $pedidoEpp->cantidad ?? 0,
                            'observaciones' => $pedidoEpp->observaciones ?? '',
                            'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                            'imagenes' => $imagenes,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('[PedidoController::show] Error procesando EPPs', [
                    'pedido_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            \Log::info('[PedidoController::show] EPPs transformados', [
                'pedido_id' => $id,
                'epps_count' => count($eppsList),
                'primer_epp_imagenes' => !empty($eppsList) ? count($eppsList[0]['imagenes']) : 0,
            ]);
            
            // Agregar EPPs transformados
            $datos['epps_transformados'] = $eppsList;

            return response()->json([
                'success' => true,
                'data' => $datos
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/cliente/{clienteId}
     * 
     * Listar pedidos de un cliente
     */
    public function listarPorCliente(int $clienteId): JsonResponse
    {
        try {
            $response = $this->listarPedidosPorClienteUseCase->ejecutar($clienteId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($dto) => $dto->toArray(), $response)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/recibos-datos
     * 
     * Obtener datos completos del pedido (para recibos)
     * Método de compatibilidad con rutas de asesores
     * 
     * Filtrado especial para bodeguero: solo muestra COSTURA-BODEGA
     */
    public function obtenerDetalleCompleto(int $id, bool $filtrarProcesosPendientes = false): JsonResponse
    {
        try {
            // Buscar el pedido por ID o por numero_pedido
            $pedido = \App\Models\PedidoProduccion::find($id);
            
            // Si no encuentra por ID, intenta buscar por numero_pedido
            if (!$pedido) {
                $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $id)->first();
            }
            
            // Si aún no encuentra el pedido, devolver error
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => "Pedido {$id} no encontrado"
                ], 404);
            }
            
            // Verificar si es bodeguero
            $esBodyguero = auth()->check() && auth()->user()->hasRole('bodeguero');
            
            // VALIDACIÓN BODEGUERO: No puede ver recibos si pedido está en pendiente_cartera o RECHAZADO_CARTERA
            if ($esBodyguero) {
                $estadoPedido = strtolower($pedido->estado ?? '');
                \Log::info('[PedidoController] Estado del pedido para bodeguero', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_raw' => $pedido->estado,
                    'estado_lower' => $estadoPedido,
                    'es_pendiente_cartera' => $estadoPedido === 'pendiente_cartera',
                    'es_rechazado_cartera' => $estadoPedido === 'rechazado_cartera'
                ]);
                
                if ($estadoPedido === 'pendiente_cartera' || $estadoPedido === 'rechazado_cartera') {
                    \Log::warning('[PedidoController] 🔐 Bodeguero bloqueado - Pedido en estado: ' . $pedido->estado, [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id(),
                        'estado' => $pedido->estado
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes ver recibos de pedidos en estado ' . $pedido->estado
                    ], 403);
                }
            }
            
            // Usar el ID real del pedido para obtener los detalles
            // $filtrarSoloAprobados indica si debemos filtrar procesos APROBADOS
            // Por defecto: mostrar todos los procesos (como en /recibos-costura)
            $response = $this->obtenerPedidoUseCase->ejecutar($pedido->id, $filtrarProcesosPendientes);
            
            // Convertir a array para modificar
            $responseData = $response->toArray();
            
            // FILTRO BODEGUERO: Si es bodeguero, filtrar procesos para mostrar SOLO 'costura-bodega'
            if ($esBodyguero && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoController] 🔐 FILTRO BODEGUERO: Filtrando procesos - Solo COSTURA-BODEGA', [
                    'pedido_id' => $pedido->id,
                    'usuario_id' => auth()->id(),
                    'total_prendas' => count($responseData['prendas'])
                ]);
                
                // DEBUG: Loguear estructura de procesos
                foreach ($responseData['prendas'] as $prendaIdx => $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        \Log::debug('[PedidoController] Estructura de procesos para prenda', [
                            'prenda_idx' => $prendaIdx,
                            'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                            'total_procesos' => count($prenda['procesos']),
                            'primer_proceso' => isset($prenda['procesos'][0]) ? $prenda['procesos'][0] : 'vacío',
                            'claves_primer_proceso' => isset($prenda['procesos'][0]) ? array_keys($prenda['procesos'][0]) : []
                        ]);
                    }
                }
                
                foreach ($responseData['prendas'] as &$prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        // Filtrar: solo mantener procesos 'costura-bodega'
                        $procesosFiltrados = array_filter($prenda['procesos'], function($proceso) {
                            // Intentar obtener el nombre del proceso desde varias claves posibles
                            $tipoProceso = $proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? $proceso['nombre'] ?? $proceso['proceso'] ?? '';
                            $tipoLower = strtolower(trim($tipoProceso));
                            
                            \Log::debug('[PedidoController] Verificando proceso para bodeguero', [
                                'tipo_proceso' => $tipoProceso,
                                'tipo_lower' => $tipoLower,
                                'proceso_keys' => array_keys($proceso),
                                'es_costura_bodega' => $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega'
                            ]);
                            
                            return $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega';
                        });
                        
                        $prenda['procesos'] = array_values($procesosFiltrados); // Reindexar array
                        
                        \Log::info('[PedidoController] 🔐 Procesos filtrados para bodeguero', [
                            'prenda_id' => $prenda['id'] ?? 'N/A',
                            'procesos_antes' => count($prenda['procesos'] ?? []),
                            'procesos_despues' => count($procesosFiltrados)
                        ]);
                    }
                }
                unset($prenda); // CRITICAL: Romper referencia del foreach para evitar corrupción de datos
                
                // Validar que bodeguero tenga al menos UN proceso costura-bodega después del filtrado
                $tieneProcesoCosturaBodega = false;
                foreach ($responseData['prendas'] as $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos']) && !empty($prenda['procesos'])) {
                        $tieneProcesoCosturaBodega = true;
                        break;
                    }
                }
                
                if (!$tieneProcesoCosturaBodega) {
                    \Log::warning('[PedidoController] 🔐 Bodeguero intenta ver pedido sin procesos costura-bodega', [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Este pedido no tiene procesos de COSTURA-BODEGA disponibles'
                    ], 403);
                }
            }
            
            // FILTRO INSUMOS: Si es insumos, filtrar prendas para mostrar SOLO de_bodega = false
            // PERO: si viene del endpoint de registros o de insumos/materiales, no aplicar filtro
            $esInsumos = auth()->check() && auth()->user()->hasRole('insumos');
            $referer = request()->headers->get('referer', '');
            $vieneDeRegistros = str_contains($referer, '/registros/');
            $vieneDeInsumos = str_contains($referer, '/insumos/materiales');
            
            $aplicarFiltroInsumos = $esInsumos && !$vieneDeRegistros && !$vieneDeInsumos;
            
            if ($aplicarFiltroInsumos && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoController]  FILTRO INSUMOS: Mostrando solo prendas con de_bodega = false', [
                    'pedido_id' => $pedido->id,
                    'usuario_id' => auth()->id(),
                    'total_prendas_antes' => count($responseData['prendas']),
                    'referer' => $referer,
                    'viene_de_registros' => $vieneDeRegistros,
                    'viene_de_insumos' => $vieneDeInsumos,
                    'aplicar_filtro' => $aplicarFiltroInsumos
                ]);
                
                // Filtrar: solo mantener prendas con de_bodega = false
                $prendasFiltradas = array_filter($responseData['prendas'], function($prenda) {
                    $deBodega = $prenda['de_bodega'] ?? false;
                    // Si de_bodega es string (tinyint from DB puede ser "0" o "1")
                    if (is_string($deBodega)) {
                        $deBodega = (bool)intval($deBodega);
                    }
                    return !$deBodega; // Mostrar solo si NO es de_bodega
                });
                
                $responseData['prendas'] = array_values($prendasFiltradas); // Reindexar array
                
                \Log::info('[PedidoController]  Prendas filtradas para insumos', [
                    'pedido_id' => $pedido->id,
                    'total_prendas_antes' => count($responseData['prendas'] ?? []) + count($prendasFiltradas),
                    'total_prendas_despues' => count($prendasFiltradas),
                    'prendas_filtradas' => array_map(function($p) {
                        return [
                            'nombre' => $p['nombre'] ?? 'N/A',
                            'de_bodega' => $p['de_bodega'] ?? 'N/A'
                        ];
                    }, $prendasFiltradas)
                ]);
                
                // Validar que insumos tenga al menos UNA prenda después del filtrado
                if (empty($prendasFiltradas)) {
                    \Log::warning('[PedidoController]  Insumos intenta ver pedido sin prendas de_bodega=false', [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Este pedido no tiene prendas disponibles para insumos (todas son de bodega)'
                    ], 403);
                }
            }
            
            // Agregar ancho y metraje a cada prenda individual
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoController] Agregando ancho/metraje y consecutivos a prendas', [
                    'pedido_id' => $pedido->id,
                    'total_prendas' => count($responseData['prendas'])
                ]);
                
                foreach ($responseData['prendas'] as $index => &$prenda) {
                    $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;
                    
                    \Log::info('[PedidoController] Procesando prenda para datos adicionales', [
                        'index' => $index,
                        'prenda_id' => $prendaId,
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A'
                    ]);
                    
                    if ($prendaId) {
                        // Buscar ancho en tabla pedido_ancho_general
                        $anchoGeneral = PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                            ->where('prenda_pedido_id', $prendaId)
                            ->first();
                        
                        // Buscar metrajes en tabla pedido_metraje_color
                        $metrajesPorColor = PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                            ->where('prenda_pedido_id', $prendaId)
                            ->get();
                        
                        if ($anchoGeneral || $metrajesPorColor->isNotEmpty()) {
                            $ancho_metraje_data = [
                                'prenda_id' => $prendaId,
                                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                                'metrajes_por_color' => []
                            ];
                            
                            // Agregar metrajes por color si existen
                            foreach ($metrajesPorColor as $metraje) {
                                $ancho_metraje_data['metrajes_por_color'][] = [
                                    'color' => $metraje->color,
                                    'metraje' => $metraje->metraje
                                ];
                            }
                            
                            $prenda['ancho_metraje'] = $ancho_metraje_data;
                            
                            \Log::info('[PedidoController] Ancho/Metraje encontrado para prenda', [
                                'pedido_id' => $pedido->id,
                                'prenda_id' => $prendaId,
                                'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                                'ancho' => $ancho_metraje_data['ancho'],
                                'metrajes_count' => count($ancho_metraje_data['metrajes_por_color'])
                            ]);
                        } else {
                            $prenda['ancho_metraje'] = null;
                            
                            \Log::info('[PedidoController] No hay ancho/metraje para prenda', [
                                'pedido_id' => $pedido->id,
                                'prenda_id' => $prendaId,
                                'prenda_nombre' => $prenda['nombre'] ?? 'N/A'
                            ]);
                        }
                        
                        // Agregar consecutivos para esta prenda
                        $consecutivos = $this->obtenerConsecutivosPrenda($pedido->id, $prendaId);
                        $prenda['recibos'] = $consecutivos;
                        $prenda['consecutivos'] = $consecutivos; // Agregar también para consistencia
                        
                        \Log::info('[PedidoController] Consecutivos agregados a prenda', [
                            'pedido_id' => $pedido->id,
                            'prenda_id' => $prendaId,
                            'consecutivos_devueltos' => $consecutivos,
                            'consecutivos_es_null' => is_null($consecutivos)
                        ]);
                        
                    } else {
                        $prenda['ancho_metraje'] = null;
                        $prenda['recibos'] = null;
                        \Log::warning('[PedidoController] Prenda sin ID válido', [
                            'index' => $index,
                            'prenda' => $prenda
                        ]);
                    }
                }
                unset($prenda); // CRITICAL: Romper referencia del foreach para evitar corrupción de datos
            }
            
            // Mantener el ancho/metraje general por compatibilidad (opcional)
            $anchoMetrajeGeneral = null;
            try {
                $pedido = \App\Models\PedidoProduccion::find($id);
                if ($pedido) {
                    $anchoMetrajeGeneral = [
                        'ancho' => $pedido->ancho ?? null,
                        'metraje' => $pedido->metraje ?? null,
                        'fecha_actualizacion' => $pedido->updated_at ?? null
                    ];
                }
            } catch (\Exception $e) {
                \Log::debug('[PedidoController] Error obteniendo ancho/metraje general', ['error' => $e->getMessage()]);
                $anchoMetrajeGeneral = null;
            }
            
            $responseData['ancho_metraje'] = $anchoMetrajeGeneral;

            // DEBUG: Loguear estructura final de tallas antes de enviar
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                foreach ($responseData['prendas'] as $prendaIndex => $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        foreach ($prenda['procesos'] as $procIndex => $proceso) {
                            if (isset($proceso['tallas'])) {
                                \Log::info('[PedidoController] ESTRUCTURA FINAL DE TALLAS ANTES DE ENVIAR', [
                                    'prenda_index' => $prendaIndex,
                                    'proceso_index' => $procIndex,
                                    'proceso_id' => $proceso['id'] ?? 'N/A',
                                    'tallas_keys' => array_keys($proceso['tallas']),
                                    'tallas_data' => $proceso['tallas'],
                                    'caballero_data' => $proceso['tallas']['caballero'] ?? 'NO ENCONTRADO',
                                    'caballero_type' => gettype($proceso['tallas']['caballero'] ?? null),
                                    'caballero_is_array' => is_array($proceso['tallas']['caballero'] ?? null)
                                ]);
                            }
                        }
                    }
                }
            }

            // Agregar datos adicionales del pedido
            if ($pedido) {
                // Agregar fecha estimada de entrega si no está presente
                if (!isset($responseData['fecha_estimada_de_entrega'])) {
                    $responseData['fecha_estimada_de_entrega'] = $pedido->fecha_estimada_de_entrega;
                }
                
                // Agregar otros campos importantes si no están presentes
                if (!isset($responseData['area'])) {
                    $responseData['area'] = $pedido->area;
                }
                
                if (!isset($responseData['dia_de_entrega'])) {
                    $responseData['dia_de_entrega'] = $pedido->dia_de_entrega;
                }
                
                \Log::info('[PedidoController] Datos del pedido agregados', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega,
                    'area' => $pedido->area,
                    'dia_de_entrega' => $pedido->dia_de_entrega
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener consecutivos de una prenda específica
     * 
     * @param int $pedidoId
     * @param int $prendaId
     * @return array|null
     */
    private function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            \Log::info('[PedidoController] Buscando consecutivos para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            // Obtener consecutivos para este pedido (incluyendo generales y específicos de prenda)
            $consecutivos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('activo', 1)
                ->where(function($query) use ($prendaId) {
                    $query->where('prenda_id', $prendaId)
                          ->orWhereNull('prenda_id');
                })
                ->get();

            \Log::info('[PedidoController] Consecutivos encontrados en BD', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'total_encontrados' => $consecutivos->count(),
                'datos_crudos' => $consecutivos->toArray()
            ]);

            if ($consecutivos->isEmpty()) {
                \Log::info('[PedidoController] No hay consecutivos para prenda', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId
                ]);
                return null;
            }

            // Estructurar los consecutivos por tipo
            $recibos = [
                'COSTURA' => null,
                'ESTAMPADO' => null,
                'BORDADO' => null,
                'DTF' => null,
                'SUBLIMADO' => null,
                'REFLECTIVO' => null,
                'COSTURA-BODEGA' => null
            ];

			// Cuando existen anexos (recibos parciales), también se insertan en consecutivos_recibos_pedidos
			// con el mismo tipo_recibo. Eso NO debe sobrescribir el consecutivo del recibo base.
			// Regla:
			// - Priorizar registro "base" (notas NO contiene 'parcial_id:')
			// - Si todos son anexos, usar el menor consecutivo_actual (más antiguo)
			$agrupados = [];
			foreach ($consecutivos as $c) {
				$tipo = $c->tipo_recibo;
				if (!isset($agrupados[$tipo])) {
					$agrupados[$tipo] = [];
				}
				$agrupados[$tipo][] = $c;
			}

			foreach ($agrupados as $tipo => $items) {
				if (!array_key_exists($tipo, $recibos)) {
					continue;
				}

				$base = collect($items)->first(function ($item) {
					$notas = (string) ($item->notas ?? '');
					return stripos($notas, 'parcial_id:') === false;
				});

				if ($base && !empty($base->consecutivo_actual)) {
					$recibos[$tipo] = $base->consecutivo_actual;
					continue;
				}

				$menor = collect($items)
					->filter(fn ($item) => !empty($item->consecutivo_actual))
					->sortBy(fn ($item) => (int) $item->consecutivo_actual)
					->first();
				$recibos[$tipo] = $menor ? $menor->consecutivo_actual : null;
			}

            \Log::info('[PedidoController] Consecutivos estructurados para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'recibos' => $recibos
            ]);

            return $recibos;

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obteniendo consecutivos de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * GET /asesores/pedidos/{id}/editar-datos
     * 
     * Obtener datos de un pedido para edición
     * Incluye prendas con variantes, telas, colores, procesos e imágenes
     * Usado por el formulario de edición de pedidos
     */
    public function obtenerDatosEdicion(int $id): JsonResponse
    {
        try {
            $pedido = \App\Models\PedidoProduccion::with([
                'prendas.variantes',
                'prendas.coloresTelas.fotos',
                'prendas.procesos.tipoProceso',
                'prendas.fotos',
                'prendas.telaFotos',
                'epps.epp',
                'asesor:id,name',
                'cliente:id,nombre'
            ])->findOrFail($id);

            // Transformar variantes para incluir nombres de tipos
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    if ($prenda->variantes) {
                        foreach ($prenda->variantes as $variante) {
                            // Obtener nombre de manga
                            if ($variante->tipo_manga_id) {
                                try {
                                    $manga = \App\Models\TipoManga::find($variante->tipo_manga_id);
                                    $variante->manga_nombre = $manga ? $manga->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoController] Error obtener manga', ['error' => $e->getMessage()]);
                                }
                            }
                            
                            // Obtener nombre de broche
                            if ($variante->tipo_broche_boton_id) {
                                try {
                                    $broche = \App\Models\TipoBrocheBoton::find($variante->tipo_broche_boton_id);
                                    $variante->broche_nombre = $broche ? $broche->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoController] Error obtener broche', ['error' => $e->getMessage()]);
                                }
                            }
                        }
                    }
                }
            }

            // Cargar talla_colores manualmente para cada prenda
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    $tallaColores = \DB::table('prenda_pedido_talla_colores as ptc')
                        ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                        ->where('pt.prenda_pedido_id', $prenda->id)
                        ->select([
                            'ptc.id',
                            'ptc.prenda_pedido_talla_id',
                            'pt.genero',
                            'pt.talla',
                            'ptc.tela_id',
                            'ptc.tela_nombre',
                            'ptc.color_id',
                            'ptc.color_nombre',
                            'ptc.cantidad'
                        ])
                        ->get()
                        ->toArray();
                    
                    $prenda->talla_colores = $tallaColores;
                    
                    \Log::info('[PedidoController] talla_colores cargados para prenda ' . $prenda->id, [
                        'cantidad' => count($tallaColores)
                    ]);
                }
            }

            // Transformar EPPs para incluir imágenes con rutas normalizadas
            $eppsList = [];
            if ($pedido->epps) {
                foreach ($pedido->epps as $pedidoEpp) {
                    $epp = $pedidoEpp->epp;
                    
                    if (!$epp) {
                        continue;
                    }
                    
                    // Obtener imágenes del EPP
                    $imagenes = [];
                    try {
                        $imagenesData = \DB::table('pedido_epp_imagenes')
                            ->where('pedido_epp_id', $pedidoEpp->id)
                            ->orderBy('orden', 'asc')
                            ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                        
                        if ($imagenesData->count() > 0) {
                            foreach ($imagenesData as $img) {
                                $ruta = $img->ruta_web ?? $img->ruta_original;
                                // Normalizar ruta
                                if (!str_starts_with($ruta, '/storage/')) {
                                    if (str_starts_with($ruta, 'storage/')) {
                                        $ruta = '/' . $ruta;
                                    } else {
                                        $ruta = '/storage/' . $ruta;
                                    }
                                }
                                
                                $imagenes[] = [
                                    'ruta_webp' => $ruta,
                                    'ruta_original' => $ruta,
                                    'ruta_web' => $ruta,
                                    'principal' => $img->principal ?? false,
                                    'orden' => $img->orden ?? 0,
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::debug('[PedidoController] Error obtener imágenes de EPP', ['error' => $e->getMessage()]);
                    }
                    
                    $eppsList[] = [
                        'id' => $pedidoEpp->id,
                        'epp_id' => $pedidoEpp->epp_id,
                        'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                        'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                        'cantidad' => $pedidoEpp->cantidad ?? 0,
                        'observaciones' => $pedidoEpp->observaciones ?? '',
                        'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                        'imagenes' => $imagenes,
                    ];
                }
            }

            // Agregar EPPs a los datos de respuesta
            $datosRespuesta = $pedido->toArray();
            $datosRespuesta['epps_transformados'] = $eppsList;
            
            // CRÍTICO: Verificar que procesos se cargan correctamente con tipoProceso
            if (!empty($datosRespuesta['prendas'])) {
                foreach ($datosRespuesta['prendas'] as $idx => $prenda) {
                    if (!empty($prenda['procesos'])) {
                        \Log::info('[obtenerDatosEdicion] Prenda ' . $idx . ' tiene procesos:', [
                            'prenda_id' => $prenda['id'],
                            'procesos_count' => count($prenda['procesos']),
                            'primer_proceso_keys' => array_keys($prenda['procesos'][0])
                        ]);
                        // Verificar que tipoProceso está en la estructura
                        if (isset($prenda['procesos'][0]['tipo_proceso'])) {
                            \Log::info('[obtenerDatosEdicion] tipoProceso encontrado:', $prenda['procesos'][0]['tipo_proceso']);
                        } elseif (isset($prenda['procesos'][0]['tipoProceso'])) {
                            \Log::info('[obtenerDatosEdicion] tipoProceso (camelCase) encontrado:', $prenda['procesos'][0]['tipoProceso']);
                        } else {
                            \Log::warning('[obtenerDatosEdicion] NO SE ENCONTRÓ tipoProceso en proceso');
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $datosRespuesta
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('[PedidoController] Pedido no encontrado para edición', ['pedido_id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener datos para edición: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/tipos-broche-boton
     * 
     * Obtener tipos de broche/botón disponibles
     * Array de tipos de broche/botón con su ID
     */
    public function obtenerTiposBrocheBoton(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoBrocheBoton::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener tipos broche/botón: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de broche/botón',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/tipos-manga
     * 
     * Obtener tipos de manga disponibles
     * Array de tipos de manga con su ID
     */
    public function obtenerTiposManga(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoManga::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener tipos manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/tipos-manga
     * 
     * Crear o obtener un tipo de manga por nombre
     * Si no existe, lo crea automáticamente
     */
    public function crearObtenerTipoManga(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del tipo de manga es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $tipo = \App\Models\TipoManga::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearlo
            if (!$tipo) {
                $tipo = \App\Models\TipoManga::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nuevo tipo de manga creado', [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tipo,
                'mensaje' => $tipo->wasRecentlyCreated ? 'Tipo creado' : 'Tipo existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener tipo manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tipo de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/telas
     * 
     * Obtener lista de telas activas
     * Array de { id, nombre }
     * NOTA: referencia ahora está en prenda_pedido_colores_telas, no en telas_prenda
     */
    public function obtenerTelas(): JsonResponse
    {
        try {
            $telas = \App\Models\TelaPrenda::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $telas
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener telas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener telas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/telas
     * 
     * Crear o obtener una tela por nombre
     * Si no existe, la crea automáticamente
     */
    public function crearObtenerTela(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $referencia = trim($request->input('referencia', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre de la tela es requerido'
                ], 400);
            }

            //  BÚSQUEDA EXACTA: Solo coincidencia perfecta
            // NO usar case-insensitive - debe ser EXACTAMENTE lo que el usuario escribe
            $tela = \App\Models\TelaPrenda::where('nombre', $nombre)
                ->first();

            // Si no existe, crearla
            if (!$tela) {
                $tela = \App\Models\TelaPrenda::create([
                    'nombre' => $nombre,  //  Guardar exactamente como enviadas
                    'referencia' => $referencia,
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nueva tela creada', [
                    'id' => $tela->id,
                    'nombre' => $tela->nombre,
                    'referencia' => $tela->referencia
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tela,
                'mensaje' => $tela->wasRecentlyCreated ? 'Tela creada' : 'Tela existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener tela: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tela',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/colores
     * 
     * Obtener lista de colores activos
     * Array de { id, nombre, codigo }
     */
    public function obtenerColores(): JsonResponse
    {
        try {
            $colores = \App\Models\ColorPrenda::where('activo', true)
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $colores
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener colores: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener colores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/colores
     * 
     * Crear o obtener un color por nombre
     * Si no existe, lo crea automáticamente
     */
    public function crearObtenerColor(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $codigo = trim($request->input('codigo', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del color es requerido'
                ], 400);
            }

            //  BÚSQUEDA EXACTA: Solo coincidencia perfecta
            // NO usar case-insensitive - debe ser EXACTAMENTE lo que el usuario escribe
            $color = \App\Models\ColorPrenda::where('nombre', $nombre)
                ->first();

            // Si no existe, crearlo
            if (!$color) {
                $color = \App\Models\ColorPrenda::create([
                    'nombre' => $nombre,  //  Guardar exactamente como enviadas
                    'codigo' => $codigo,
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nuevo color creado', [
                    'id' => $color->id,
                    'nombre' => $color->nombre,
                    'codigo' => $color->codigo
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $color,
                'mensaje' => $color->wasRecentlyCreated ? 'Color creado' : 'Color existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener color: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener color',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/prendas-pedido/{prendaPedidoId}/fotos
     * 
     * DEPRECADO: Obtener fotos de una prenda del pedido
     * Requiere refactorización a DDD
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta funcionalidad está siendo refactorizada a DDD'
        ], 501);
    }

    /**
     * POST /asesores/pedidos/confirm
     * 
     * DEPRECADO: Alias para confirmar pedido
     * Usa: PATCH /api/pedidos/{id}/confirmar
     */
    public function confirm(Request $request): JsonResponse
    {
        $id = $request->input('pedido_id') ?: $request->route('id');
        
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere el ID del pedido'
            ], 400);
        }

        return $this->confirmar($id);
    }

    /**
     * POST /asesores/pedidos/{id}/anular
     * 
     * DEPRECADO: Alias para cancelar pedido
     * Usa: DELETE /api/pedidos/{id}/cancelar
     */
    public function anularPedido(Request $request, $id): JsonResponse
    {
        return $this->cancelar($id);
    }

    /**
     * GET /pedidos-public/{pedidoId}/ancho-metraje-prenda/{prendaId}
     * 
     * Obtiene ancho y metraje de una prenda específica (endpoint público)
     * Utilizado en supervisor-pedidos para mostrar ancho/metraje en recibos
     */
    public function obtenerAnchoMetrajePrendaPublico($pedidoId, $prendaId)
    {
        try {
            // Obtener el pedido
            $pedido = \App\Models\PedidoProduccion::find($pedidoId);
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // Obtener ancho general
            $anchoGeneral = \App\Models\PedidoAnchoGeneral::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->first();

            // Obtener metrajes por color
            $metrajesPorColor = \App\Models\PedidoMetrajeColor::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->get();

            // Determinar modo según los datos disponibles
            $tipoModo = null;
            if ($anchoGeneral && $metrajesPorColor->isEmpty()) {
                $tipoModo = 'normal';
            } elseif (!$anchoGeneral && !$metrajesPorColor->isEmpty()) {
                $tipoModo = 'color';
            } elseif ($anchoGeneral && !$metrajesPorColor->isEmpty()) {
                $tipoModo = 'pieza';
            }

            // Construir respuesta
            $response = [
                'success' => true,
                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                'metraje' => $anchoGeneral ? $anchoGeneral->metraje : null,
                'tipo_modo' => $tipoModo,
                'data' => []
            ];

            // Agregar metrajes por color si existen
            if ($metrajesPorColor->isNotEmpty()) {
                $response['data'] = $metrajesPorColor->map(function ($item) {
                    return [
                        'color' => $item->color,
                        'metraje' => $item->metraje
                    ];
                })->toArray();
            }

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerAnchoMetrajePrendaPublico:', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ancho y metraje'
            ], 500);
        }
    }
}

