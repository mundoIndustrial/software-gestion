<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\TipoPrenda;

/**
 * CatalogoController
 *
 * Gestión de catálogos de telas, colores, tipos de manga y tipos de broche/botón.
 * Separado de PedidoController siguiendo SRP (Single Responsibility Principle).
 */
class CatalogoController extends Controller
{
    /**
     * GET /asesores/api/tipos-broche-boton
     */
    public function obtenerTiposBrocheBoton(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoBrocheBoton::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[CatalogoController] Error obtener tipos broche/botón: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de broche/botón',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/tipos-manga
     */
    public function obtenerTiposManga(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoManga::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[CatalogoController] Error obtener tipos manga: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/tipos-manga
     *
     * Crear o obtener un tipo de manga por nombre.
     * Si no existe, lo crea automáticamente.
     */
    public function crearObtenerTipoManga(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));

            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del tipo de manga es requerido'
                ], 400);
            }

            $tipo = \App\Models\TipoManga::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])->first();

            if (!$tipo) {
                $tipo = \App\Models\TipoManga::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'activo' => true
                ]);

                \Log::info('[CatalogoController] Nuevo tipo de manga creado', [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tipo,
                'mensaje' => $tipo->wasRecentlyCreated ? 'Tipo creado' : 'Tipo existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[CatalogoController] Error crear/obtener tipo manga: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tipo de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/telas
     */
    public function obtenerTelas(): JsonResponse
    {
        try {
            $telas = \App\Models\TelaPrenda::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $telas
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[CatalogoController] Error obtener telas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener telas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/telas
     *
     * Crear o obtener una tela por nombre.
     * Si no existe, la crea automáticamente.
     */
    public function crearObtenerTela(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $referencia = trim($request->input('referencia', ''));

            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre de la tela es requerido'
                ], 400);
            }

            // Búsqueda exacta — debe coincidir exactamente con lo que escribe el usuario
            $tela = \App\Models\TelaPrenda::where('nombre', $nombre)->first();

            if (!$tela) {
                $tela = \App\Models\TelaPrenda::create([
                    'nombre' => $nombre,
                    'referencia' => $referencia,
                    'activo' => true
                ]);

                \Log::info('[CatalogoController] Nueva tela creada', [
                    'id' => $tela->id,
                    'nombre' => $tela->nombre,
                    'referencia' => $tela->referencia
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tela,
                'mensaje' => $tela->wasRecentlyCreated ? 'Tela creada' : 'Tela existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[CatalogoController] Error crear/obtener tela: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tela',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/colores
     */
    public function obtenerColores(): JsonResponse
    {
        try {
            $colores = \App\Models\ColorPrenda::where('activo', true)
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $colores
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[CatalogoController] Error obtener colores: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener colores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/colores
     *
     * Crear o obtener un color por nombre.
     * Si no existe, lo crea automáticamente.
     */
    public function crearObtenerColor(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $codigo = trim($request->input('codigo', ''));

            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del color es requerido'
                ], 400);
            }

            // Búsqueda exacta — debe coincidir exactamente con lo que escribe el usuario
            $color = \App\Models\ColorPrenda::where('nombre', $nombre)->first();

            if (!$color) {
                $color = \App\Models\ColorPrenda::create([
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                    'activo' => true
                ]);

                \Log::info('[CatalogoController] Nuevo color creado', [
                    'id' => $color->id,
                    'nombre' => $color->nombre,
                    'codigo' => $color->codigo
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $color,
                'mensaje' => $color->wasRecentlyCreated ? 'Color creado' : 'Color existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[CatalogoController] Error crear/obtener color: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener color',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/tipos-prenda
     */
    public function tiposPrenda(): JsonResponse
    {
        $tipos = TipoPrenda::where('activo', true)->get();

        return response()->json($tipos);
    }

    /**
     * POST /api/prenda/reconocer
     */
    public function reconocerPrenda(Request $request): JsonResponse
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
