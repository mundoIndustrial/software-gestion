<?php

namespace App\Http\Controllers\Api_temp;

use App\Http\Controllers\Controller;
use App\Models\HorarioPorRol;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HorarioController extends Controller
{
    /**
     * Obtener todos los horarios de los roles
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        try {
            $horarios = HorarioPorRol::with('rol')
                ->orderBy('id_rol')
                ->get()
                ->map(function ($horario) {
                    return [
                        'id' => $horario->id,
                        'id_rol' => $horario->id_rol,
                        'nombre_rol' => $horario->rol ? $horario->rol->name : null,
                        'entrada_manana' => $this->formatHora($horario->entrada_manana),
                        'salida_manana' => $this->formatHora($horario->salida_manana),
                        'entrada_tarde' => $this->formatHora($horario->entrada_tarde),
                        'salida_tarde' => $this->formatHora($horario->salida_tarde),
                        'entrada_sabado' => $this->formatHora($horario->entrada_sabado),
                        'salida_sabado' => $this->formatHora($horario->salida_sabado),
                    ];
                });

            return response()->json($horarios);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los horarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatear hora para que sea compatible con input type="time"
     */
    private function formatHora($hora)
    {
        if (!$hora) {
            return '';
        }
        
        // Si es un objeto Carbon o DateTime
        if (is_object($hora)) {
            return $hora->format('H:i');
        }
        
        // Si es una cadena, retorna los primeros 5 caracteres (HH:MM)
        if (is_string($hora)) {
            return substr($hora, 0, 5);
        }
        
        return '';
    }

    /**
     * Obtener los roles sin horario definido
     *
     * @return JsonResponse
     */
    public function rolesDisponibles(): JsonResponse
    {
        try {
            // Obtener roles que no tienen horario definido
            $rolesConHorario = HorarioPorRol::pluck('id_rol')->toArray();
            $rolesDisponibles = Role::whereNotIn('id', $rolesConHorario)->get();

            return response()->json($rolesDisponibles);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los roles disponibles',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el horario de un rol
     *
     * @param  int  $id
     * @param  Request  $request
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $horario = HorarioPorRol::find($id);

            if (!$horario) {
                return response()->json([
                    'error' => 'Horario no encontrado'
                ], 404);
            }

            // Validar que las horas sean en formato correcto (HH:MM:SS)
            $request->validate([
                'entrada_manana' => 'nullable|date_format:H:i:s',
                'salida_manana' => 'nullable|date_format:H:i:s',
                'entrada_tarde' => 'nullable|date_format:H:i:s',
                'salida_tarde' => 'nullable|date_format:H:i:s',
                'entrada_sabado' => 'nullable|date_format:H:i:s',
                'salida_sabado' => 'nullable|date_format:H:i:s',
            ]);

            $horario->update($request->only([
                'entrada_manana',
                'salida_manana',
                'entrada_tarde',
                'salida_tarde',
                'entrada_sabado',
                'salida_sabado',
            ]));

            $horario->load('rol');

            return response()->json([
                'message' => 'Horario actualizado correctamente',
                'horario' => [
                    'id' => $horario->id,
                    'id_rol' => $horario->id_rol,
                    'nombre_rol' => $horario->rol ? $horario->rol->name : null,
                    'entrada_manana' => $horario->entrada_manana,
                    'salida_manana' => $horario->salida_manana,
                    'entrada_tarde' => $horario->entrada_tarde,
                    'salida_tarde' => $horario->salida_tarde,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el horario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo horario para un rol
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id_rol' => 'required|integer|exists:roles,id|unique:horario_por_roles,id_rol',
                'entrada_manana' => 'nullable|date_format:H:i:s',
                'salida_manana' => 'nullable|date_format:H:i:s',
                'entrada_tarde' => 'nullable|date_format:H:i:s',
                'salida_tarde' => 'nullable|date_format:H:i:s',
                'entrada_sabado' => 'nullable|date_format:H:i:s',
                'salida_sabado' => 'nullable|date_format:H:i:s',
            ]);

            $horario = HorarioPorRol::create($request->all());
            $horario->load('rol');

            return response()->json([
                'message' => 'Horario creado correctamente',
                'horario' => [
                    'id' => $horario->id,
                    'id_rol' => $horario->id_rol,
                    'nombre_rol' => $horario->rol ? $horario->rol->name : null,
                    'entrada_manana' => $horario->entrada_manana,
                    'salida_manana' => $horario->salida_manana,
                    'entrada_tarde' => $horario->entrada_tarde,
                    'salida_tarde' => $horario->salida_tarde,
                    'entrada_sabado' => $horario->entrada_sabado,
                    'salida_sabado' => $horario->salida_sabado,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear el horario',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
