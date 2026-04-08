<?php

namespace App\Application\Pedidos\UseCases;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * ObtenerEncargadosPorAreaUseCase
 * Caso de uso para obtener usuarios (encargados) disponibles por área específica.
 * Responsabilidades:
 * - Obtener usuarios filtrados por área
 * - Retornar datos formateados para dropdown/select
 * - Cachear resultados si es necesario
 * Este UseCase permite que el frontend no tenga lógica de obtención de datos.
 */
class ObtenerEncargadosPorAreaUseCase
{
    /**
     * Ejecuta el caso de uso
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
     * @param string $area Nombre del área (normalizado)
     * @return array Array de usuarios formateado para select
     */
    private function obtenerEncargadosPorArea(string $area): array
    {
        $usuarios = [];
        $areaRoleMap = $this->obtenerMapeoAreaRoles();
        $rolesArea = $areaRoleMap[$area] ?? [];

        if (empty($rolesArea)) {
            Log::warning('[ObtenerEncargadosPorAreaUseCase] Área no configurada', ['area' => $area]);
            return $usuarios;
        }

        try {
            $roleIds = \App\Models\Role::whereIn('name', $rolesArea)
                ->pluck('id')
                ->toArray();

            if (!empty($roleIds)) {
                $usuarios = $this->filtrarUsuariosPorRoles($roleIds);
            } else {
                Log::warning('[ObtenerEncargadosPorAreaUseCase] No se encontraron roles para área', [
                    'area' => $area,
                    'roles' => $rolesArea
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[ObtenerEncargadosPorAreaUseCase::obtenerEncargadosPorArea] Error consultando usuarios', [
                'area' => $area,
                'roles' => $rolesArea,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $usuarios;
    }

    /**
     * Retorna el mapeo de áreas a roles/permisos
     * @return array Mapeo área => roles
     */
    private function obtenerMapeoAreaRoles(): array
    {
        return [
            'COSTURA' => ['costurero', 'supervisor_costura', 'confeccion-sobremedida', 'costura-reflectivo'],
            'CORTE' => ['cortador', 'supervisor_corte', 'confeccion-sobremedida'],
            'ESTAMPADO' => ['estampador', 'supervisor_estampado'],
            'BORDADO' => ['bordador', 'supervisor_bordado'],
            'DTF' => ['dtf', 'supervisor_dtf'],
            'SUBLIMADO' => ['sublimador', 'supervisor_sublimado'],
            'REFLECTIVO' => ['reflectivo', 'supervisor_reflectivo'],
            'INSUMOS' => ['insumos', 'supervisador_insumos', 'gestor-insumos'],
            'CONTROL DE CALIDAD' => ['control-calidad', 'supervisor_calidad'],
            'DESPACHO' => ['despacho', 'supervisor_despacho'],
            'TALLER' => ['tallista', 'supervisor_taller'],
            'ENTREGA' => ['repartidor', 'supervisor_entrega'],
            'LAVANDERÍA' => ['lavanderia', 'supervisor_lavanderia'],
        ];
    }

    /**
     * Filtra usuarios que tienen los roles especificados
     * @param array $roleIds IDs de roles a buscar
     * @return array Array de usuarios formateado
     */
    private function filtrarUsuariosPorRoles(array $roleIds): array
    {
        $usuarios = [];
        
        // Obtener usuarios que tienen al menos uno de los roles
        $usuariosRaw = User::select(['id', 'name', 'email', 'roles_ids'])
            ->orderBy('name')
            ->get();

        foreach ($usuariosRaw as $user) {
            $userRoleIds = $user->roles_ids ?? [];
            
            // Verificar si el usuario tiene al menos un rol de la área
            foreach ($roleIds as $roleId) {
                if (in_array($roleId, $userRoleIds, true)) {
                    $usuarios[] = [
                        'id' => $user->id,
                        'nombre' => $user->name,
                        'email' => $user->email
                    ];
                    break; // Evitar duplicados
                }
            }
        }

        return $usuarios;
    }
}
