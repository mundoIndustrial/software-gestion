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
        $tipos = TipoPrenda::where('activo', true)
            ->get();

        return response()->json($tipos);
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
            'tipo' => $tipo
        ]);
    }
}
