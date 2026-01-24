<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoPrenda;
use Illuminate\Http\Request;

class PrendaController extends Controller
{
    /**
     * Obtener todos los tipos de prenda
     */
    public function tiposPrenda()
    {
        try {
            $tipos = TipoPrenda::where('activo', true)
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo tipos de prenda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de prenda'
            ], 500);
        }
    }

    /**
     * Reconocer tipo de prenda por nombre
     */
    public function reconocer(Request $request)
    {
        $nombre = $request->input('nombre');

        if (!$nombre) {
            return response()->json([
                'success' => false,
                'message' => 'Nombre de prenda requerido'
            ], 400);
        }

        try {
            $tipo = TipoPrenda::reconocerPorNombre($nombre);

            if (!$tipo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de prenda no reconocido',
                    'nombre' => $nombre
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tipo
            ]);
        } catch (\Exception $e) {
            \Log::error('Error reconociendo tipo de prenda', [
                'error' => $e->getMessage(),
                'nombre' => $nombre
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reconocer tipo de prenda'
            ], 500);
        }
    }
}
