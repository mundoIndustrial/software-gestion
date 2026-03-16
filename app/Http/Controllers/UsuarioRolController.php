<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

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
            $rolCosturero = Role::where('name', 'costurero')->first();
            $rolCosturaReflectivo = Role::where('name', 'costura-reflectivo')->first();
            $rolConfeccionSobremedida = Role::where('name', 'confeccion-sobremedida')->first();
            
            if (!$rolCosturero && !$rolCosturaReflectivo && !$rolConfeccionSobremedida) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron los roles "costurero", "costura-reflectivo" o "confeccion-sobremedida" en el sistema'
                ], 404);
            }
            
            // Obtener usuarios que tienen cualquiera de los tres roles
            $query = User::select('id', 'name', 'email')
                ->orderBy('name');

            // Si el recibo es REFLECTIVO, el selector debe mostrar SOLO costura-reflectivo
            if ($tipoRecibo === 'REFLECTIVO') {
                if ($rolCosturaReflectivo) {
                    $query->whereJsonContains('roles_ids', $rolCosturaReflectivo->id);
                } else {
                    $query->whereRaw('1 = 0');
                }

                $usuarios = $query->distinct()->get();

                return response()->json([
                    'success' => true,
                    'usuarios' => $usuarios
                ]);
            }
            
            // Si existe rol costurero, incluir usuarios con ese rol
            if ($rolCosturero) {
                $query->orWhereJsonContains('roles_ids', $rolCosturero->id);
            }
            
            // Si existe rol costura-reflectivo, incluir usuarios con ese rol
            if ($rolCosturaReflectivo) {
                $query->orWhereJsonContains('roles_ids', $rolCosturaReflectivo->id);
            }
            
            // Si existe rol confeccion-sobremedida, incluir usuarios con ese rol
            if ($rolConfeccionSobremedida) {
                $query->orWhereJsonContains('roles_ids', $rolConfeccionSobremedida->id);
            }
            
            // Evitar duplicados si un usuario tiene múltiples roles
            $usuarios = $query->distinct()->get();

            return response()->json([
                'success' => true,
                'usuarios' => $usuarios
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios de costura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuarios con rol 'corte'
     */
    public function getUsuariosCorte(Request $request)
    {
        try {
            // Buscar el rol corte
            $rolCorte = Role::where('name', 'corte')->first();
            
            if (!$rolCorte) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el rol "corte" en el sistema'
                ], 404);
            }
            
            // Obtener usuarios con rol corte
            $usuarios = User::select('id', 'name', 'email')
                ->whereJsonContains('roles_ids', $rolCorte->id)
                ->orderBy('name')
                ->distinct()
                ->get();

            return response()->json([
                'success' => true,
                'usuarios' => $usuarios
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios de corte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuarios por múltiples roles (genérico)
     * Query params: roles (comma-separated) ej: /api/usuarios/por-roles?roles=costurero,corte
     */
    public function getUsuariosPorRoles(Request $request)
    {
        try {
            $rolesStr = $request->query('roles', '');
            
            if (empty($rolesStr)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar al menos un rol'
                ], 400);
            }

            $rolesArray = array_map('trim', explode(',', $rolesStr));
            
            // Buscar los roles
            $roles = Role::whereIn('name', $rolesArray)->get();
            
            if ($roles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron los roles especificados'
                ], 404);
            }

            $roleIds = $roles->pluck('id')->toArray();
            
            // Obtener usuarios que tienen cualquiera de los roles especificados
            $usuarios = User::select('id', 'name', 'email')
                ->orderBy('name');

            // Añadir condiciones OR para cada rol
            foreach ($roleIds as $roleId) {
                $usuarios->orWhereJsonContains('roles_ids', $roleId);
            }

            $usuarios = $usuarios->distinct()->get();

            return response()->json([
                'success' => true,
                'usuarios' => $usuarios
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }
}
