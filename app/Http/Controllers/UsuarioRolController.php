<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class UsuarioRolController extends Controller
{
    /**
     * Obtener usuarios con rol 'costurero'
     */
    public function getUsuariosCostura(Request $request)
    {
        try {
            // Primero buscar el rol 'costurero'
            $rolCosturero = Role::where('name', 'costurero')->first();
            
            if (!$rolCosturero) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el rol "costurero" en el sistema'
                ], 404);
            }
            
            // Obtener usuarios que tienen el rol 'costurero' en roles_ids (JSON)
            $usuarios = User::whereJsonContains('roles_ids', $rolCosturero->id)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

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
