<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Http\Controllers\Controller;

class UsuarioRolController extends Controller
{
    /**
     * Obtener usuarios con rol 'costurero', 'costura-reflectivo' y 'confeccion-sobremedida'
     */
    public function getUsuariosCostura(Request $request)
    {
        try {
            $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', '')));

            // Buscar los tres roles
            $roles = $this->obtenerRolesCostura();
            
            // Validar que exista al menos un rol
            $validacion = $this->validarRolesCostura($roles);
            if ($validacion['status'] !== 200) {
                return response()->json($validacion['response'], $validacion['status']);
            }
            
            // Construir query según tipo de recibo
            $usuarios = $this->construirQueryCostura($roles, $tipoRecibo);

            return response()->json([
                'success' => true,
                'usuarios' => $usuarios
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios de costura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener los tres roles de costura
     */
    private function obtenerRolesCostura(): array
    {
        return [
            'costurero' => Role::where('name', 'costurero')->first(),
            'costura-reflectivo' => Role::where('name', 'costura-reflectivo')->first(),
            'confeccion-sobremedida' => Role::where('name', 'confeccion-sobremedida')->first()
        ];
    }

    /**
     * Validar que exista al menos un rol de costura
     */
    private function validarRolesCostura(array $roles): array
    {
        $tieneAlgunRol = $roles['costurero'] || $roles['costura-reflectivo'] || $roles['confeccion-sobremedida'];
        
        if (!$tieneAlgunRol) {
            return [
                'status' => 404,
                'response' => [
                    'success' => false,
                    'message' => 'No se encontraron los roles "costurero", "costura-reflectivo" o "confeccion-sobremedida" en el sistema'
                ]
            ];
        }
        
        return ['status' => 200, 'response' => null];
    }

    /**
     * Construir query de usuarios según tipo de recibo
     */
    private function construirQueryCostura(array $roles, string $tipoRecibo)
    {
        $query = User::select('id', 'name', 'email')->orderBy('name');

        if ($tipoRecibo === 'REFLECTIVO') {
            $this->aplicarFiltroReflectivo($query, $roles['costura-reflectivo']);
        } else {
            $this->aplicarFiltrosMultiplesRoles($query, $roles);
        }

        return $query->distinct()->get();
    }

    /**
     * Aplicar filtro REFLECTIVO a la query
     */
    private function aplicarFiltroReflectivo($query, $rolReflectivo): void
    {
        if ($rolReflectivo) {
            $query->whereJsonContains('roles_ids', $rolReflectivo->id);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Aplicar filtros para múltiples roles (no REFLECTIVO)
     */
    private function aplicarFiltrosMultiplesRoles($query, array $roles): void
    {
        if ($roles['costurero']) {
            $query->orWhereJsonContains('roles_ids', $roles['costurero']->id);
        }
        
        if ($roles['costura-reflectivo']) {
            $query->orWhereJsonContains('roles_ids', $roles['costura-reflectivo']->id);
        }
        
        if ($roles['confeccion-sobremedida']) {
            $query->orWhereJsonContains('roles_ids', $roles['confeccion-sobremedida']->id);
        }
    }

    /**
     * Obtener usuarios por área/proceso
     */
    public function getUsuariosPorArea(Request $request)
    {
        try {
            $area = strtolower(trim((string) $request->query('area', '')));

            // Validar área y obtener roles necesarios
            $validacion = $this->validarArea($area);
            if ($validacion['status'] !== 200) {
                return response()->json($validacion['response'], $validacion['status']);
            }

            // Obtener usuarios
            $usuarios = $this->obtenerUsuariosPorRoles($validacion['roles']);

            return response()->json([
                'success' => true,
                'usuarios' => $usuarios
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios por área: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar área y retornar roles necesarios
     */
    private function validarArea(string $area): array
    {
        $rolesMap = [
            'corte' => ['cortador', 'confeccion-sobremedida'],
            'costura' => ['costurero', 'confeccion-sobremedida', 'costura-reflectivo'],
            'bordado' => ['bordador'],
            'estampado' => ['estampador'],
            'lavandería' => ['lavanderia'],
            'control de calidad' => ['control-calidad'],
            'despacho' => ['despacho'],
            'insumos' => ['gestor-insumos'],
            'taller' => ['tallista'],
            'entrega' => ['repartidor']
        ];

        if (!isset($rolesMap[$area])) {
            return [
                'status' => 400,
                'response' => [
                    'success' => false,
                    'message' => 'Área no válida: ' . $area
                ],
                'roles' => null
            ];
        }

        return [
            'status' => 200,
            'response' => null,
            'roles' => $rolesMap[$area]
        ];
    }

    /**
     * Obtener usuarios que tienen cualquiera de los roles especificados
     */
    private function obtenerUsuariosPorRoles(array $rolesNecesarios)
    {
        // Buscar los roles
        $roles = Role::whereIn('name', $rolesNecesarios)->get();
        
        if ($roles->isEmpty()) {
            return [];
        }

        // Obtener usuarios con cualquiera de los roles
        $query = User::select('id', 'name', 'email')
            ->orderBy('name');

        foreach ($roles as $role) {
            $query->orWhereJsonContains('roles_ids', $role->id);
        }

        // Evitar duplicados
        return $query->distinct()->get();
    }
}
