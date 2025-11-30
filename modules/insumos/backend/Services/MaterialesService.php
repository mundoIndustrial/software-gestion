<?php

namespace Modules\Insumos\Backend\Services;

use Modules\Insumos\Backend\Repositories\MaterialesRepository;
use Illuminate\Support\Facades\Auth;

class MaterialesService
{
    protected $materialesRepository;

    public function __construct(MaterialesRepository $materialesRepository)
    {
        $this->materialesRepository = $materialesRepository;
    }

    /**
     * Obtener dashboard con estadísticas
     */
    public function obtenerDashboard()
    {
        return [
            'total' => $this->materialesRepository->contar(),
            'por_area' => $this->materialesRepository->obtenerEstadisticasPorArea(),
            'estados' => [
                'no_iniciado' => $this->materialesRepository->contar(['estado' => 'No iniciado']),
                'en_ejecucion' => $this->materialesRepository->contar(['estado' => 'En Ejecución']),
                'anulada' => $this->materialesRepository->contar(['estado' => 'Anulada']),
            ],
        ];
    }

    /**
     * Obtener materiales filtrados
     */
    public function obtenerMaterialesFiltrados($filtros = [])
    {
        $filtros = $this->aplicarFiltrosDefecto($filtros);
        return $this->materialesRepository->obtenerConFiltros($filtros);
    }

    /**
     * Guardar materiales
     */
    public function guardarMateriales($datos)
    {
        $datosPreparados = $this->prepararDatos($datos);
        
        foreach ($datosPreparados as $material) {
            $this->validarAccesoOrden($material['numero_pedido'] ?? null);
            $this->materialesRepository->createOrUpdate($material);
        }

        return true;
    }

    /**
     * Eliminar material
     */
    public function eliminarMaterial($id)
    {
        $material = $this->materialesRepository->obtenerPorId($id);
        
        if ($material) {
            $this->validarAccesoOrden($material->numero_pedido);
            return $this->materialesRepository->delete($id);
        }

        throw new \Exception('Material no encontrado');
    }

    /**
     * Obtener opciones de filtro
     */
    public function obtenerOpcionesFiltro($column)
    {
        $permitidos = config('insumos.allowed_filter_columns', []);
        
        if (!in_array($column, $permitidos)) {
            throw new \Exception('Columna no permitida para filtro');
        }

        return $this->materialesRepository->obtenerValoresUnicos($column);
    }

    /**
     * Validar acceso a orden
     */
    public function validarAccesoOrden($numeroPedido = null)
    {
        if (!Auth::check()) {
            throw new \Exception('Usuario no autenticado');
        }

        $user = Auth::user();
        $userRole = $user->role ?? null;

        if (!in_array($userRole, ['admin', 'supervisor'])) {
            throw new \Exception('No tienes permisos para acceder a esta orden');
        }

        return true;
    }

    /**
     * Aplicar filtros por defecto
     */
    protected function aplicarFiltrosDefecto($filtros)
    {
        // Los filtros por defecto van aquí
        return $filtros;
    }

    /**
     * Preparar datos para guardar
     */
    protected function prepararDatos($datos)
    {
        $preparados = [];

        foreach ((array)$datos as $item) {
            $preparados[] = [
                'numero_pedido' => $item['numero_pedido'] ?? null,
                'nombre_insumo' => trim($item['nombre_insumo'] ?? ''),
                'cantidad' => $item['cantidad'] ?? 0,
                'estado' => $item['estado'] ?? 'No iniciado',
                'area' => $item['area'] ?? null,
                'observaciones' => $item['observaciones'] ?? null,
                'asignado_a' => $item['asignado_a'] ?? Auth::id(),
            ];
        }

        return $preparados;
    }
}
