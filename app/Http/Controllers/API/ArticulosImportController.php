<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Epp;
use App\Models\EppCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticulosImportController extends Controller
{
    /**
     * Guardar artículos procesados en la BD
     * POST /api/articulos/guardar
     */
    public function guardarArticulos(Request $request)
    {
        try {
            $articulos = $request->json('articulos', []);
            
            if (empty($articulos)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay artículos para guardar'
                ], 400);
            }

            // PASO 1: Procesar todas las categorías únicas PRIMERO para evitar race conditions
            // PASO 1: Procesar todas las categorías únicas PRIMERO para evitar race conditions
            $categoriasMap = $this->procesarCategorias($articulos);

            // PASO 2: Preparar artículos para INSERT masivo
            $articulosParaInsertar = [];
            $errores = [];
            $guardados = 0;

            foreach ($articulos as $articulo) {
                try {
                    // Validar datos básicos
                    if (empty($articulo['nombre_completo'])) {
                        throw new \Exception('nombre_completo es requerido');
                    }

                    // Obtener categoría del mapa
                    $nombreCategoria = !empty($articulo['categoria']) ? $articulo['categoria'] : 'OTROS';
                    $categoriaId = $categoriasMap[$nombreCategoria] ?? null;
                    
                    if (!$categoriaId) {
                        throw new \Exception("No se pudo resolver categoría: $nombreCategoria");
                    }

                    // Generar código único si no existe
                    $codigo = $articulo['codigo'] ?? $this->generarCodigo($articulo['nombre_completo']);

                    // Sanitizar datos
                    $nombre_completo = substr(trim($articulo['nombre_completo']), 0, 500);
                    $marca = !empty($articulo['marca']) ? substr(trim($articulo['marca']), 0, 100) : null;
                    $tipo = in_array($articulo['tipo'] ?? '', ['PRODUCTO', 'SERVICIO']) ? $articulo['tipo'] : 'PRODUCTO';
                    $talla = !empty($articulo['talla']) ? substr(trim($articulo['talla']), 0, 100) : null;
                    $color = !empty($articulo['color']) ? substr(trim($articulo['color']), 0, 100) : null;
                    $descripcion = !empty($articulo['descripcion']) ? substr(trim($articulo['descripcion']), 0, 1000) : null;

                    // Agregar a array para insertar
                    $articulosParaInsertar[] = [
                        'codigo' => substr($codigo, 0, 50),
                        'nombre_completo' => $nombre_completo,
                        'marca' => $marca,
                        'categoria_id' => $categoriaId,
                        'tipo' => $tipo,
                        'talla' => $talla,
                        'color' => $color,
                        'descripcion' => $descripcion,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                } catch (\Exception $e) {
                    $errores[] = [
                        'articulo' => $articulo['nombre_completo'] ?? 'Desconocido',
                        'error' => $e->getMessage(),
                        'trace' => config('app.debug') ? $e->getTraceAsString() : null
                    ];
                    \Log::error('Error preparando artículo', [
                        'articulo' => $articulo,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // PASO 3: Insertar todos en una sola query
            if (!empty($articulosParaInsertar)) {
                try {
                    // Dividir en chunks de 500 para evitar límites de query
                    $chunks = array_chunk($articulosParaInsertar, 500);
                    foreach ($chunks as $chunk) {
                        DB::table('epps')->insertOrIgnore($chunk);
                        $guardados += count($chunk);
                    }
                    \Log::info("INSERT masivo completado: $guardados artículos insertados");
                } catch (\Exception $e) {
                    \Log::error('Error en INSERT masivo', [
                        'error' => $e->getMessage(),
                        'cantidad' => count($articulosParaInsertar)
                    ]);
                    $errores[] = [
                        'articulo' => 'INSERT MASIVO',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'guardados' => $guardados,
                'total' => count($articulos),
                'errores' => $errores,
                'mensaje' => "$guardados artículos guardados exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar código único para un artículo
     */
    private function generarCodigo($nombre)
    {
        if (empty($nombre)) {
            return 'ART-' . time() . '-' . rand(1000, 9999);
        }

        // Limpiar nombre y tomar primeros caracteres
        $codigo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $nombre));
        $codigo = substr($codigo, 0, 20);
        
        if (empty($codigo)) {
            $codigo = 'ART';
        }

        $base = $codigo;
        $contador = 1;
        
        // Evitar duplicados con timestamp para garantizar unicidad
        $codigoFinal = $base;
        while (Epp::where('codigo', $codigoFinal)->exists()) {
            $codigoFinal = $base . '-' . $contador;
            $contador++;
            
            // Fallback si hay muchos duplicados
            if ($contador > 100) {
                $codigoFinal = $base . '-' . time() . '-' . rand(1000, 9999);
                break;
            }
        }
        
        return $codigoFinal;
    }

    /**
     * Obtener artículos guardados
     * GET /api/articulos
     */
    public function listar()
    {
        $articulos = Epp::with('categoria')
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();

        return response()->json([
            'success' => true,
            'articulos' => $articulos,
            'total' => $articulos->count()
        ]);
    }

    /**
     * Obtener un artículo específico
     * GET /api/articulos/{id}
     */
    public function obtener($id)
    {
        $articulo = Epp::with('categoria')->find($id);

        if (!$articulo) {
            return response()->json([
                'success' => false,
                'error' => 'Artículo no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'articulo' => $articulo
        ]);
    }

    /**
     * Procesar todas las categorías únicas de una lista de artículos
     * Busca en BD POR NOMBRE Y CÓDIGO, si no existe la CREA automáticamente
     * ARREGLO: Buscar por nombre O código para evitar duplicados
     */
    private function procesarCategorias($articulos)
    {
        $categoriasMap = [];
        
        // Extraer categorías únicas
        $categoriasUnicas = array_unique(array_map(function($art) {
            return !empty($art['categoria']) ? $art['categoria'] : 'OTROS';
        }, $articulos));
        
        \Log::info('Procesando categorías', [
            'categorias_unicas' => array_values($categoriasUnicas),
            'total' => count($categoriasUnicas)
        ]);
        
        // Para cada categoría: buscar o crear
        foreach ($categoriasUnicas as $nombreCategoria) {
            $nombreLimpio = trim($nombreCategoria);
            $codigoNormalizado = strtoupper(preg_replace('/[^A-Z0-9_]/', '', str_replace(' ', '_', substr($nombreLimpio, 0, 50))));
            
            try {
                // ARREGLO 1: Buscar por nombre O por código normalizado
                $categoria = DB::table('epp_categorias')
                    ->where('nombre', '=', $nombreLimpio)
                    ->orWhere('codigo', '=', $codigoNormalizado)
                    ->first();
                
                if ($categoria) {
                    $categoriasMap[$nombreCategoria] = $categoria->id;
                    \Log::debug("✓ Categoría encontrada en BD: '$nombreLimpio' -> ID: {$categoria->id}");
                } else {
                    // No existe, crear
                    $codigo = $codigoNormalizado;
                    
                    // Generar código único (evitar suffix _1, _2, etc.)
                    $codigoBase = $codigo;
                    $contador = 1;
                    while (DB::table('epp_categorias')->where('codigo', $codigo)->exists()) {
                        $codigo = $codigoBase . '_' . $contador;
                        $contador++;
                    }
                    
                    $nuevoId = DB::table('epp_categorias')->insertGetId([
                        'codigo' => $codigo,
                        'nombre' => substr($nombreLimpio, 0, 255),
                        'descripcion' => 'Categoría auto-creada',
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $categoriasMap[$nombreCategoria] = $nuevoId;
                    \Log::info("✓ Categoría CREADA: '$nombreLimpio' -> ID: $nuevoId (codigo: $codigo)");
                }
            } catch (\Exception $e) {
                \Log::error("✗ Error procesando categoría: '$nombreLimpio'", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        \Log::info('Mapa final de categorías', [
            'total_procesadas' => count($categoriasMap),
            'total_buscadas' => count($categoriasUnicas),
            'mapa' => $categoriasMap
        ]);
        
        return $categoriasMap;
    }
}

