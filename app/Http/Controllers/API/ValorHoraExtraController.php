<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ValorHoraExtra;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ValorHoraExtraController extends Controller
{
    /**
     * Obtener el valor de hora extra para una persona
     */
    public function obtener($codigoPersona): JsonResponse
    {
        try {
            $valorHoraExtra = ValorHoraExtra::where('codigo_persona', $codigoPersona)->first();
            
            if ($valorHoraExtra) {
                return response()->json([
                    'success' => true,
                    'valor' => $valorHoraExtra->valor
                ]);
            }
            
            return response()->json([
                'success' => true,
                'valor' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el valor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar o actualizar el valor de hora extra
     */
    public function guardar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'codigo_persona' => 'required|numeric',
                'valor' => 'required|numeric|min:0',
                'id_reporte' => 'nullable|integer|exists:reportes_personal,id'
            ]);

            // Convertir codigo_persona a integer
            $codigoPersona = (int) $request->codigo_persona;

            // Verificar que la persona existe
            $personal = Personal::where('codigo_persona', $codigoPersona)->first();
            if (!$personal) {
                return response()->json([
                    'success' => false,
                    'message' => 'La persona no existe'
                ], 404);
            }

            // Preparar datos para actualizar o crear
            $valor = (float) $request->valor;
            // Si el valor es un número entero, guardarlo sin decimales
            if ($valor == (int) $valor) {
                $valor = (int) $valor;
            }
            
            $data = ['valor' => $valor];
            if ($request->id_reporte) {
                $data['id_reporte'] = $request->id_reporte;
            }

            // Crear o actualizar el registro
            $valorHoraExtra = ValorHoraExtra::updateOrCreate(
                ['codigo_persona' => $codigoPersona],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => 'Valor guardado exitosamente',
                'data' => $valorHoraExtra
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el valor: ' . $e->getMessage()
            ], 500);
        }
    }
}
