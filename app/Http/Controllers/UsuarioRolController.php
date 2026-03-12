<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class UsuarioRolController extends Controller
{
    /**
     * Obtener usuarios con rol 'costurero' y 'costura-reflectivo'
     */
    public function getUsuariosCostura(Request $request)
    {
        try {
            // Buscar ambos roles
            $rolCosturero = Role::where('name', 'costurero')->first();
            $rolCosturaReflectivo = Role::where('name', 'costura-reflectivo')->first();
            
            if (!$rolCosturero && !$rolCosturaReflectivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron los roles "costurero" o "costura-reflectivo" en el sistema'
                ], 404);
            }
            
            // Obtener usuarios que tienen cualquiera de los dos roles
            $query = User::select('id', 'name', 'email')
                ->orderBy('name');
            
            // Si existe rol costurero, incluir usuarios con ese rol
            if ($rolCosturero) {
                $query->orWhereJsonContains('roles_ids', $rolCosturero->id);
            }
            
            // Si existe rol costura-reflectivo, incluir usuarios con ese rol
            if ($rolCosturaReflectivo) {
                $query->orWhereJsonContains('roles_ids', $rolCosturaReflectivo->id);
            }
            
            // Evitar duplicados si un usuario tiene ambos roles
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
}
