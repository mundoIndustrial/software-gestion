<?php

namespace App\Application\Pedidos\UseCases;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * ObtenerEncargadosPorAreaUseCase
 * 
 * Caso de uso para obtener usuarios (encargados) disponibles por área específica.
 * 
 * Responsabilidades:
 * - Obtener usuarios filtrados por área
 * - Retornar datos formateados para dropdown/select
 * - Cachear resultados si es necesario
 * 
 * Este UseCase permite que el frontend no tenga lógica de obtención de datos.
 */
class ObtenerEncargadosPorAreaUseCase
{
    /**
     * Ejecuta el caso de uso
     * 
     * @param string $area Nombre del área
     * @return array Array de encargados disponibles
     */
    public function ejecutar(string $area): array
    {
        try {
            // Normalizar área
            $areaNormalizada = trim(strtoupper($area));

            // Obtener usuarios por área
            $encargados = $this->obtenerEncargadosPorArea($areaNormalizada);

            Log::info('[ObtenerEncargadosPorAreaUseCase] Encargados obtenidos', [
                'area' => $areaNormalizada,
                'total' => count($encargados)
            ]);

            return [
                'success' => true,
                'area' => $areaNormalizada,
                'encargados' => $encargados,
                'total' => count($encargados)
            ];

        } catch (\Exception $e) {
            Log::error('[ObtenerEncargadosPorAreaUseCase] Error obteniendo encargados', [
                'area' => $area,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene usuarios filtrados por área
     * 
     * @param string $area Nombre del área (normalizado)
     * @return array Array de usuarios formateado para select
     */
    private function obtenerEncargadosPorArea(string $area): array
    {
        // Mapeo de áreas a roles/permisos
        $areaRoleMap = [
            'COSTURA' => ['costurero', 'supervisor_costura'],
            'ESTAMPADO' => ['estampador', 'supervisor_estampado'],
            'BORDADO' => ['bordador', 'supervisor_bordado'],
            'DTF' => ['dtf', 'supervisor_dtf'],
            'SUBLIMADO' => ['sublimador', 'supervisor_sublimado'],
            'REFLECTIVO' => ['reflectivo', 'supervisor_reflectivo'],
            'INSUMOS' => ['insumos', 'supervisador_insumos'],
        ];

        // Obtener roles asociados al área
        $rolesArea = $areaRoleMap[$area] ?? [];

        if (empty($rolesArea)) {
            return [];
        }

        // Obtener usuarios con esos roles
        $usuarios = User::whereHas('roles', function ($query) use ($rolesArea) {
            $query->whereIn('name', $rolesArea);
        })
        ->where('activo', true)
        ->select(['id', 'name', 'email'])
        ->orderBy('name')
        ->get();

        // Formatear para retornar
        return $usuarios->map(fn($user) => [
            'id' => $user->id,
            'nombre' => $user->name,
            'email' => $user->email
        ])->toArray();
    }
}
