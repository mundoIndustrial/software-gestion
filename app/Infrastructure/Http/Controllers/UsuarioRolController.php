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
     * Obtener usuarios por área/proceso
     */
    public function getUsuariosPorArea(Request $request)
    {
        try {
            $area = strtolower(trim((string) $request->query('area', '')));

            // Mapeo de áreas a roles
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
                return response()->json([
                    'success' => false,
                    'message' => 'Área no válida: ' . $area
                ], 400);
            }

            $rolesNecesarios = $rolesMap[$area];
            
            // Buscar los roles
            $roles = Role::whereIn('name', $rolesNecesarios)->get();
            
            if ($roles->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'usuarios' => []
                ]);
            }

            // Obtener usuarios con cualquiera de los roles
            $query = User::select('id', 'name', 'email')
                ->orderBy('name');

            foreach ($roles as $role) {
                $query->orWhereJsonContains('roles_ids', $role->id);
            }

            // Evitar duplicados
            $usuarios = $query->distinct()->get();

            return response()->json([
                'success' => true,
                'usuarios' => $usuarios
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios por área: ' . $e->getMessage()
            ], 500);
        }
    }
}
