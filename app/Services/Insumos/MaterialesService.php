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
}
