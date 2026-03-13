<?php

namespace App\Services\Insumos;

use App\Repositories\Insumos\MaterialesRepository;
use App\Models\PedidoProduccion;
use App\Domain\Insumos\Services\CalculadorDemoraService;
use Illuminate\Support\Collection;

/**
 * Service para gestión de lógica de negocio de materiales
 * Implementa principios SOLID con DDD
 * 
 * Delegación:
 * - CalculadorDemoraService: Cálculos de demora (Domain Service)
 */
class MaterialesService
{
    protected $repository;
    protected $calculadorDemora;

    public function __construct(
        MaterialesRepository $repository,
        CalculadorDemoraService $calculadorDemora = null
    ) {
        $this->repository = $repository;
        // Lazy load del servicio de demora si no se inyecta
        $this->calculadorDemora = $calculadorDemora ?? app(CalculadorDemoraService::class);
    }

    /**
     * Obtener dashboard de materiales
     */
    public function obtenerDashboard()
    {
        return [
            'total_materiales' => $this->repository->contar(),
            'materiales_recibidos' => $this->repository->contar([
                'recibido' => true
            ]),
            'materiales_pendientes' => $this->repository->contar([
                'recibido' => false
            ]),
        ];
    }

    /**
     * Obtener materiales con filtros y enriquecidos con información de demora
     * 
     * @param array $filtros
     * @param int $perPage
     * @param bool $conDemora
     * @return mixed
     */
    public function obtenerMaterialesFiltrados($filtros = [], $perPage = 25, $conDemora = true)
    {
        // Aplicar filtros por defecto
        $filtrosAplicados = $this->aplicarFiltrosDefecto($filtros);

        $materiales = $this->repository->obtenerConFiltros($filtrosAplicados, $perPage);

        // Enriquecer con información de demora si se solicita
        if ($conDemora && $materiales) {
            $materiales = $this->enriquecerMaterialesConDemora($materiales);
        }

        return $materiales;
    }

    /**
     * Enriquecer una colección de materiales con información de demora
     * Delegación al Domain Service
     * 
     * @param mixed $materiales Collection, Paginator, o array
     * @return mixed
     */
    protected function enriquecerMaterialesConDemora($materiales)
    {
        // Soportar Collection, LengthAwarePaginator, o array
        $items = $materiales instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $materiales->items()
            : (is_array($materiales) ? $materiales : $materiales->toArray());

        $enriquecidos = array_map(function ($material) {
            $materialArray = is_object($material) ? $material->toArray() : $material;
            
            // Si tiene fechas de pedido y llegada, calcular demora
            if (
                isset($materialArray['fecha_pedido']) && 
                isset($materialArray['fecha_llegada_estimada'])
            ) {
                try {
                    $demora = $this->calculadorDemora->calcularDemora(
                        $materialArray['fecha_pedido'],
                        $materialArray['fecha_llegada_estimada']
                    );
                    
                    $materialArray['demora'] = $demora->toArray();
                } catch (\Exception $e) {
                    \Log::warning("Error calculando demora para material: {$e->getMessage()}");
                    $materialArray['demora'] = null;
                }
            }
            
            return $materialArray;
        }, $items);

        // Si era paginador, retornar paginado
        if ($materiales instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return $materiales->setCollection(collect($enriquecidos));
        }

        // Si era Collection, retornar Collection
        if ($materiales instanceof \Illuminate\Support\Collection) {
            return collect($enriquecidos);
        }

        // Si era array, retornar array
        return $enriquecidos;
    }

    /**
     * Obtener resumen de demoras para materiales de un pedido
     * 
     * @param string $numeroPedido
     * @return array
     */
    public function obtenerResumenDemorasPorPedido($numeroPedido)
    {
        try {
            $materiales = $this->repository->obtenerConFiltros([
                'numero_pedido' => $numeroPedido,
                'tiene_numero_pedido' => true
            ], 1000); // Sin paginación para el resumen

            $materialesArray = collect($materiales)->toArray();
            
            return $this->calculadorDemora->resumirDemorasPorEstado($materialesArray);
        } catch (\Exception $e) {
            \Log::error("Error obteniendo resumen de demoras: {$e->getMessage()}");
            
            return [
                'rapido' => 0,
                'normal' => 0,
                'lento' => 0,
                'critico' => 0,
            ];
        }
    }

    /**
     * Guardar o actualizar materiales
     */
    public function guardarMateriales($numeroPedido, $materiales)
    {
        $resultados = [];

        foreach ($materiales as $material) {
            try {
                $data = $this->prepararDatos($material, $numeroPedido);
                $resultado = $this->repository->createOrUpdate($data);
                $resultados[] = [
                    'success' => true,
                    'material_id' => $resultado->id,
                    'prenda_id' => $material['prenda_pedido_id'] ?? null,
                ];
            } catch (\Exception $e) {
                \Log::error('Error al guardar material: ' . $e->getMessage());
                $resultados[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $resultados;
    }

    /**
     * Eliminar un material
     */
    public function eliminarMaterial($numeroPedido, $prendaPedidoId)
    {
        try {
            $deleted = $this->repository->delete($numeroPedido, $prendaPedidoId);
            
            return [
                'success' => $deleted > 0,
                'message' => $deleted > 0 ? 'Material eliminado correctamente' : 'No se encontró el material',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al eliminar material: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al eliminar material: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener valores únicos para filtros
     */
    public function obtenerOpcionesFiltro($column)
    {
        $columnasPermitidas = [
            'numero_pedido',
            'cliente',
            'descripcion',
            'estado',
            'area',
            'fecha_de_creacion_de_orden'
        ];

        if (!in_array($column, $columnasPermitidas)) {
            throw new \InvalidArgumentException("Columna no permitida: {$column}");
        }

        $filtrosDefecto = [
            'estado' => ['No iniciado', 'En Ejecución', 'Anulada'],
            'area' => ['Corte', 'Creación de orden', 'Creación', 'Costura']
        ];

        return $this->repository->obtenerValoresUnicos($column, $filtrosDefecto);
    }

    /**
     * Aplicar filtros por defecto
     */
    protected function aplicarFiltrosDefecto($filtros)
    {
        // Estados permitidos
        $filtrosDefecto = [
            'estado' => ['No iniciado', 'En Ejecución', 'Anulada'],
            'area' => ['Corte', 'Creación de orden', 'Creación', 'Costura'],
            'tiene_numero_pedido' => true // Excluir pedidos sin número de pedido
        ];

        // Mezclar filtros del usuario con los defectos
        if (empty($filtros['estado'])) {
            $filtros['estado'] = $filtrosDefecto['estado'];
        }

        if (empty($filtros['area'])) {
            $filtros['area'] = $filtrosDefecto['area'];
        }

        // Siempre aplicar el filtro de número de pedido
        $filtros['tiene_numero_pedido'] = $filtrosDefecto['tiene_numero_pedido'];

        return $filtros;
    }

    /**
     * Preparar datos para guardar
     */
    protected function prepararDatos($material, $numeroPedido)
    {
        return [
            'numero_pedido' => $numeroPedido,
            'prenda_pedido_id' => $material['prenda_pedido_id'] ?? null,
            'talla' => $material['talla'] ?? null,
            'cantidad' => $material['cantidad'] ?? null,
            'observaciones' => $material['observaciones'] ?? null,
        ];
    }

    /**
     * Validar que una orden pertenece al usuario
     */
    public function validarAccesoOrden($numeroPedido, $user)
    {
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        if (!$orden) {
            return false;
        }

        // Validar que el usuario tenga acceso (rol insumos)
        return $user && $user->role && $user->role->name === 'insumos';
    }

    /**
     * Cambiar el estado de un pedido y crear procesos automáticos
     */
    public function cambiarEstadoPedido($numeroPedido, $nuevoEstado)
    {
        try {
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            if (!$orden) {
                return [
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ];
            }

            // Verificar que el pedido esté en estado Pendiente o PENDIENTE_INSUMOS
            if ($orden->estado !== 'Pendiente' && $orden->estado !== 'PENDIENTE_INSUMOS') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden enviar a producción pedidos en estado Pendiente o Pendiente Insumos'
                ];
            }

            // Actualizar el estado a "No iniciado" y área a "Corte"
            $orden->update([
                'estado' => 'No iniciado',
                'area' => 'Corte'
            ]);

            // Crear procesos automáticamente
            $procesoService = new ProcesoAutomaticoService();
            $resultadoProcesos = $procesoService->crearProcesosParaPedido($numeroPedido);

            \Log::info("Pedido #{$numeroPedido} enviado a producción", [
                'estado_anterior' => 'Pendiente',
                'estado_nuevo' => 'No iniciado',
                'area' => 'Corte',
                'usuario' => auth()->user()->name ?? 'Sistema',
                'procesos_creados' => $resultadoProcesos['procesos_creados'] ?? 0
            ]);

            $message = 'Pedido enviado a producción correctamente';
            if ($resultadoProcesos['success'] && $resultadoProcesos['procesos_creados'] > 0) {
                $message .= ". Se crearon {$resultadoProcesos['procesos_creados']} procesos automáticamente";
            }

            return [
                'success' => true,
                'message' => $message,
                'estado' => 'No iniciado',
                'area' => 'Corte',
                'procesos_creados' => $resultadoProcesos['procesos_creados'] ?? 0,
                'detalles_procesos' => $resultadoProcesos['detalles'] ?? []
            ];
        } catch (\Exception $e) {
            \Log::error('Error al cambiar estado del pedido: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ];
        }
    }

    /**
     * Actualizar el estado de marcado de un material
     */
    public function actualizarMarcado($materialId, $marcado)
    {
        return $this->repository->actualizarMarcado($materialId, $marcado);
    }

    /**
     * Obtener ancho y metraje de una prenda específica
     */
    public function obtenerAnchoMetrajePrenda($pedidoId, $prendaId)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            // Obtener ancho general (el más reciente)
            $anchoGeneral = \App\Models\PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->latest()
                ->first();
            
            // Obtener metrajes por color (los más recientes)
            $metrajesPorColor = \App\Models\PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->get();
            
            // Determinar tipo_modo guardado
            $tipoModoGuardado = null;
            if ($anchoGeneral && $anchoGeneral->tipo_modo) {
                $tipoModoGuardado = $anchoGeneral->tipo_modo;
            } elseif ($metrajesPorColor->isNotEmpty()) {
                $tipoModoGuardado = $metrajesPorColor->first()->tipo_modo;
            }
            
            // Mapear metrajes por color desde la tabla pedido_metraje_color
            // El campo 'ancho' está en PedidoAnchoGeneral, no en metrajes
            $data = [];
            foreach ($metrajesPorColor as $metraje) {
                $data[] = [
                    'color' => $metraje->color,
                    'metraje' => $metraje->metraje,
                    'talla' => null, // No hay talla en esta tabla
                ];
            }
            
            return [
                'success' => true,
                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                'ancho_general' => $anchoGeneral ? $anchoGeneral->ancho : null,
                'metraje' => $anchoGeneral ? $anchoGeneral->metraje : null,
                'contenido_mano' => $anchoGeneral ? $anchoGeneral->contenido_mano : null,
                'data' => $data,
                'tipo_modo' => $tipoModoGuardado,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al obtener ancho/metraje: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener ancho/metraje',
            ];
        }
    }

    /**
     * Guardar ancho general y/o metraje por color
     */
    public function guardarAnchoMetrajePrenda($pedidoId, $prendaId, $datos)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            $tipoModoNuevo = $datos['tipo_modo'] ?? 'normal';
            
            // Buscar datos existentes
            $anchoExistente = \App\Models\PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->first();
            
            $metrajeExistente = \App\Models\PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->first();
            
            $tipoModoExistente = null;
            if ($anchoExistente && $anchoExistente->tipo_modo) {
                $tipoModoExistente = $anchoExistente->tipo_modo;
            } elseif ($metrajeExistente && $metrajeExistente->tipo_modo) {
                $tipoModoExistente = $metrajeExistente->tipo_modo;
            }
            
            // Si cambió el tipo de modo, eliminar datos anteriores automáticamente
            if ($tipoModoExistente && $tipoModoNuevo !== $tipoModoExistente) {
                \App\Models\PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                    ->where('prenda_pedido_id', $prendaId)
                    ->delete();
                    
                \App\Models\PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                    ->where('prenda_pedido_id', $prendaId)
                    ->delete();
                    
                \Log::info("Límpieza automática de datos anteriores: pedido {$pedido->numero_pedido}, prenda {$prendaId}, cambio de {$tipoModoExistente} a {$tipoModoNuevo}");
            }
            
            // Guardar ancho general si aplica
            if (empty($datos['color'])) {
                \App\Models\PedidoAnchoGeneral::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'prenda_pedido_id' => $prendaId,
                    ],
                    [
                        'ancho' => $datos['ancho'] ?? null,
                        'metraje' => $datos['metraje'] ?? null,
                        'contenido_mano' => $datos['contenido_mano'] ?? null,
                        'tipo_modo' => $tipoModoNuevo,
                    ]
                );
            }
            
            // Guardar metraje por color
            if (!empty($datos['color']) && ($datos['metraje'] ?? null) !== null) {
                \App\Models\PedidoMetrajeColor::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'prenda_pedido_id' => $prendaId,
                        'color' => $datos['color'],
                    ],
                    [
                        'metraje' => $datos['metraje'],
                        'ancho' => $datos['ancho'] ?? null,
                        'tipo_modo' => $tipoModoNuevo,
                    ]
                );
            }
            
            return [
                'success' => true,
                'message' => 'Ancho y metraje guardados correctamente',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al guardar ancho/metraje: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al guardar ancho/metraje',
            ];
        }
    }

    /**
     * Eliminar ancho general y/o metraje por color
     */
    public function eliminarAnchoMetrajePrenda($pedidoId, $prendaId)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            \App\Models\PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->delete();
            
            \App\Models\PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->delete();
            
            return [
                'success' => true,
                'message' => 'Ancho y metraje eliminados correctamente',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al eliminar ancho/metraje: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar ancho/metraje',
            ];
        }
    }

    /**
     * Obtener colores/telas disponibles para una prenda
     */
    public function obtenerColoresPrenda($pedidoId, $prendaId)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            // Verificar MODO TALLA-COLOR
            $tallasConColores = \App\Models\PrendaPedidoTalla::where('prenda_pedido_id', $prendaId)
                ->with('coloresAsignados')
                ->get();
            
            $coloresUnicos = [];
            $tallasProcesadas = 0;
            
            foreach ($tallasConColores as $talla) {
                if ($talla->coloresAsignados && count($talla->coloresAsignados) > 0) {
                    $tallasProcesadas++;
                    
                    // Extraer colores únicos de esta talla
                    foreach ($talla->coloresAsignados as $colorAsignado) {
                        $colorNombre = $colorAsignado->color_nombre;
                        
                        // Evitar duplicados
                        $colorKey = strtolower($colorNombre);
                        if (!isset($coloresUnicos[$colorKey])) {
                            $coloresUnicos[$colorKey] = [
                                'nombre' => $colorNombre,
                                'color' => [
                                    'nombre' => $colorNombre
                                ]
                            ];
                        }
                    }
                }
            }
            
            if ($tallasProcesadas > 0 && count($coloresUnicos) > 0) {
                return [
                    'success' => true,
                    'tipo' => 'talla_color',
                    'data' => array_values($coloresUnicos), // Retornar array plano de colores
                    'esMatriz' => true,
                ];
            }
            
            // Verificar MODO PIEZAS
            $coloresTelas = \App\Models\PrendaPedidoColorTela::with(['color', 'tela'])
                ->where('prenda_pedido_id', $prendaId)
                ->get();
            
            if ($coloresTelas->count() > 0) {
                // Transformar a estructura de colores simples
                $coloresFormatted = $coloresTelas->map(function ($ct) {
                    return [
                        'nombre' => $ct->color ? $ct->color->nombre : $ct->color_id,
                        'color' => [
                            'nombre' => $ct->color ? $ct->color->nombre : $ct->color_id
                        ]
                    ];
                })->unique(function ($item) {
                    return $item['nombre'];
                })->values();
                
                return [
                    'success' => true,
                    'tipo' => 'piezas',
                    'data' => $coloresFormatted,
                    'esMatriz' => false,
                ];
            }
            
            // MODO NORMAL
            return [
                'success' => true,
                'tipo' => 'normal',
                'data' => [],
                'esMatriz' => false,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al obtener colores: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener colores',
            ];
        }
    }

    /**
     * Cambiar estado de un recibo individual
     */
    public function cambiarEstadoRecibo($reciboId, $nuevoEstado)
    {
        try {
            $recibo = \App\Models\ConsecutivoReciboPedido::findOrFail($reciboId);
            $estadoAnterior = $recibo->estado ?? 'PENDIENTE_INSUMOS';
            
            if ($estadoAnterior !== 'PENDIENTE_INSUMOS') {
                return [
                    'success' => false,
                    'message' => 'Este recibo ya ha sido aprobado',
                ];
            }
            
            $recibo->update([
                'estado' => $nuevoEstado,
                'area' => $this->determinarAreaPorEstado($nuevoEstado),
            ]);
            
            // Contar recibos pendientes
            $recibosPendientes = \App\Models\ConsecutivoReciboPedido::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->where('estado', 'PENDIENTE_INSUMOS')
                ->count();
            
            // Actualizar pedido padre
            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            if ($pedido && $pedido->estado === 'PENDIENTE_INSUMOS') {
                $pedido->update([
                    'estado' => $nuevoEstado,
                    'area' => $this->determinarAreaPorEstado($nuevoEstado),
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Recibo aprobado correctamente',
                'recibos_pendientes' => $recibosPendientes,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al cambiar estado del recibo: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del recibo',
            ];
        }
    }

    /**
     * Contar recibos costura pendientes
     */
    public function contarCosturaPendiente($userId)
    {
        try {
            $vistosIds = \App\Models\ReciboVistoInsumo::where('user_id', $userId)
                ->pluck('consecutivo_recibo_id')
                ->toArray();
            
            $query = \App\Models\ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
                ->where('estado', 'PENDIENTE_INSUMOS')
                ->where('activo', 1);
            
            if (!empty($vistosIds)) {
                $query->whereNotIn('id', $vistosIds);
            }
            
            $total = $query->count();
            $recibos = $query->with(['pedido:id,numero_pedido,cliente'])->get();
            
            return [
                'success' => true,
                'total' => $total,
                'recibos' => $recibos,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al contar recibos pendientes: ' . $e->getMessage());
            return [
                'success' => false,
                'total' => 0,
                'recibos' => [],
            ];
        }
    }

    /**
     * Marcar recibo como visto
     */
    public function marcarReciboVisto($reciboId, $userId)
    {
        try {
            \App\Models\ReciboVistoInsumo::firstOrCreate([
                'recibo_id' => $reciboId,
                'user_id' => $userId,
            ]);
            
            return [
                'success' => true,
                'message' => 'Recibo marcado como visto',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al marcar recibo como visto: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al marcar como visto',
            ];
        }
    }

    /**
     * Cambiar estado general del pedido
     */
    public function cambiarEstado($pedidoId, $nuevoEstado)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            $estadoActual = $pedido->estado;
            
            if ($estadoActual === $nuevoEstado) {
                return [
                    'success' => false,
                    'message' => 'El pedido ya está en ese estado',
                ];
            }
            
            if (!$this->esTransicionPermitida($estadoActual, $nuevoEstado)) {
                return [
                    'success' => false,
                    'message' => 'Transición de estado no permitida',
                ];
            }
            
            $pedido->update([
                'estado' => $nuevoEstado,
                'area' => $this->determinarAreaPorEstado($nuevoEstado),
            ]);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'estado_anterior' => $estadoActual,
                'nuevo_estado' => $nuevoEstado,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al cambiar estado: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cambiar estado',
            ];
        }
    }

    /**
     * Validar transición de estado
     */
    private function esTransicionPermitida($estadoActual, $nuevoEstado)
    {
        if ($estadoActual === 'PENDIENTE_INSUMOS') {
            return in_array($nuevoEstado, ['No iniciado', 'En Ejecución']);
        }
        
        if ($estadoActual === 'No iniciado') {
            return $nuevoEstado === 'En Ejecución';
        }
        
        if ($estadoActual === 'En Ejecución') {
            return in_array($nuevoEstado, ['No iniciado', 'PENDIENTE_INSUMOS']);
        }
        
        return false;
    }

    /**
     * Determinar área según estado
     */
    private function determinarAreaPorEstado($estado)
    {
        return match ($estado) {
            'No iniciado', 'En Ejecución' => 'Corte',
            'PENDIENTE_INSUMOS' => 'Insumos',
            default => 'Corte',
        };
    }
}
