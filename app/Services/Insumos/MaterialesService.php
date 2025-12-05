<?php

namespace App\Services\Insumos;

use App\Repositories\Insumos\MaterialesRepository;
use App\Models\PedidoProduccion;
use Illuminate\Support\Collection;

/**
 * Service para gestión de lógica de negocio de materiales
 * Implementa principios SOLID
 */
class MaterialesService
{
    protected $repository;

    public function __construct(MaterialesRepository $repository)
    {
        $this->repository = $repository;
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
     * Obtener materiales con filtros y paginación
     */
    public function obtenerMaterialesFiltrados($filtros = [], $perPage = 25)
    {
        // Aplicar filtros por defecto
        $filtrosAplicados = $this->aplicarFiltrosDefecto($filtros);

        return $this->repository->obtenerConFiltros($filtrosAplicados, $perPage);
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
            'area' => ['Corte', 'Creación de orden', 'Creación']
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
            'area' => ['Corte', 'Creación de orden', 'Creación']
        ];

        // Mezclar filtros del usuario con los defectos
        if (empty($filtros['estado'])) {
            $filtros['estado'] = $filtrosDefecto['estado'];
        }

        if (empty($filtros['area'])) {
            $filtros['area'] = $filtrosDefecto['area'];
        }

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
}
