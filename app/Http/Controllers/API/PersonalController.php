<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PersonalController extends Controller
{
    /**
     * Obtener lista de todo el personal con sus roles
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        try {
            $personal = Personal::with('rol')
                ->orderBy('nombre_persona')
                ->get()
                ->map(function ($person) {
                    return [
                        'id' => $person->id,
                        'codigo_persona' => $person->codigo_persona,
                        'nombre_persona' => $person->nombre_persona,
                        'id_rol' => $person->id_rol,
                        'rol' => $person->rol ? $person->rol->name : null,
                    ];
                });

            return response()->json($personal);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el personal',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el rol de una persona
     *
     * @param  int  $id
     * @param  Request  $request
     * @return JsonResponse
     */
    public function updateRol(int $id, Request $request): JsonResponse
    {
        try {
            $personal = Personal::find($id);

            if (!$personal) {
                return response()->json([
                    'error' => 'Personal no encontrado'
                ], 404);
            }

            // Validar que id_rol sea válido si se proporciona
            $request->validate([
                'id_rol' => 'nullable|integer|exists:roles,id'
            ]);

            $personal->update([
                'id_rol' => $request->input('id_rol')
            ]);

            // Recargar la relación para obtener el nombre del rol actualizado
            $personal->load('rol');

            return response()->json([
                'message' => 'Rol actualizado correctamente',
                'personal' => [
                    'id' => $personal->id,
                    'codigo_persona' => $personal->codigo_persona,
                    'nombre_persona' => $personal->nombre_persona,
                    'id_rol' => $personal->id_rol,
                    'rol' => $personal->rol ? $personal->rol->name : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el rol',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
