<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Services\CotizacionService;
use App\Services\PrendaService;
use App\Services\ImagenService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CotizacionPrendaController extends Controller
{
    private CotizacionService $cotizacionService;
    private PrendaService $prendaService;
    private ImagenService $imagenService;

    /**
     * Constructor: Verificar que el usuario sea Asesor e inyectar servicios
     */
    public function __construct(
        CotizacionService $cotizacionService,
        PrendaService $prendaService,
        ImagenService $imagenService
    )
    {
        $this->cotizacionService = $cotizacionService;
        $this->prendaService = $prendaService;
        $this->imagenService = $imagenService;
        
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $rol = is_object($user->role) ? $user->role->name : $user->role;
            
            if ($rol !== 'asesor') {
                abort(403, 'Solo asesores pueden crear cotizaciones de prenda');
            }
            return $next($request);
        });
    }

    /**
     * Mostrar formulario para crear cotización de prenda
     */
    public function create(): View
    {
        return view('cotizaciones.prenda.create');
    }

    /**
     * Guardar cotización de prenda
     */
    public function store(Request $request)
    {
        try {
            // Registrar TODO lo que viene en el request
            \Log::info('🔍 REQUEST COMPLETO RECIBIDO', [
                'all' => $request->all(),
            ]);
            
            // Validación simple
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'required|string|max:255',
                'fecha' => 'nullable|date',
                'action' => 'required|in:borrador,enviar',
                'tipo_cotizacion' => 'nullable|string',
            ]);

            $tipo = $validated['action'];
            
            // Convertir 'enviar' a 'enviada' para el servicio
            $tipoServicio = ($tipo === 'enviar') ? 'enviada' : 'borrador';
            
            // Obtener productos del formulario
            $productosRaw = $request->input('productos_prenda', []);
            
            \Log::info('📥 Productos RAW recibidos del formulario', [
                'cantidad' => count($productosRaw),
                'estructura' => json_encode($productosRaw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ]);
            
            // También verificar archivos
            \Log::info('📁 Archivos recibidos en request', [
                'todos_los_archivos' => $request->allFiles(),
                'fotos_arquivos' => $request->file('productos_prenda.*.fotos') ?? [],
                'telas_archivos' => $request->file('productos_prenda.*.telas') ?? []
            ]);
            
            // Obtener especificaciones del formulario
            $especificaciones = $request->input('especificaciones', []);
            
            \Log::info('📋 Especificaciones recibidas del formulario', [
                'especificaciones' => $especificaciones,
                'tipo' => gettype($especificaciones),
                'vacías' => empty($especificaciones)
            ]);
            
            // ===== DETECTAR TIPO DE COTIZACIÓN AUTOMÁTICAMENTE =====
            // Verificar si hay prendas (productos_prenda no vacío)
            $tienePrendas = !empty($productosRaw) && count($productosRaw) > 0;
            
            // Verificar si hay especificaciones que indiquen bordado/técnicas
            $tieneBordado = false;
            if (!empty($especificaciones)) {
                // Si hay especificaciones (técnicas, ubicaciones, etc.), hay bordado
                $tieneBordado = true;
            }
            
            // Determinar el código de tipo de cotización (P, B, PB)
            $codigoTipoCotizacion = null;
            if ($tienePrendas && $tieneBordado) {
                $codigoTipoCotizacion = 'PB'; // Prenda + Bordado
            } elseif ($tienePrendas) {
                $codigoTipoCotizacion = 'P';  // Solo Prenda
            } elseif ($tieneBordado) {
                $codigoTipoCotizacion = 'B';  // Solo Bordado
            }
            
            \Log::info('🔍 Tipo de cotización detectado automáticamente', [
                'tienePrendas' => $tienePrendas,
                'tieneBordado' => $tieneBordado,
                'codigoTipoCotizacion' => $codigoTipoCotizacion
            ]);
            
            // Primero, crear la cotización para obtener su ID
            $datosFormulario = [
                'cliente' => $validated['cliente'],
                'asesora' => $validated['asesora'],
                'tipo_venta' => $validated['tipo_cotizacion'] ?? null, // M, D, X
                'tipo_cotizacion_codigo' => $codigoTipoCotizacion, // P, B, PB
                'productos' => [], // Se llenarán después
                'especificaciones' => $especificaciones, // ← AGREGAR ESPECIFICACIONES
            ];

            // Crear cotización usando el servicio
            $cotizacion = $this->cotizacionService->crear(
                $datosFormulario,
                $tipoServicio,
                $datosFormulario['tipo_cotizacion']
            );

            \Log::info('✅ Cotización creada', ['id' => $cotizacion->id]);

            // Ahora procesar productos con la cotización ID disponible
            $productos = [];
            foreach ($productosRaw as $index => $producto) {
                \Log::info("🔹 Procesando producto $index", [
                    'producto' => json_encode($producto, JSON_UNESCAPED_UNICODE)
                ]);
                
                // Procesar nombre
                $nombre = $producto['nombre_producto'] ?? '';
                if (empty($nombre)) {
                    \Log::warning("⚠️ Producto $index sin nombre, saltando");
                    continue;
                }
                
                // Procesar descripción
                $descripcion = $producto['descripcion'] ?? null;
                
                // Procesar tallas
                $tallas = $producto['tallas'] ?? [];
                if (is_string($tallas)) {
                    $tallas = array_filter(array_map('trim', explode(',', $tallas)));
                } elseif (!is_array($tallas)) {
                    $tallas = [];
                }
                
                \Log::info("  📏 Tallas procesadas", ['tallas' => $tallas]);
                
                // Procesar fotos (GUARDAR EN DISCO)
                $fotosGuardadas = [];
                
                // Acceder a los archivos de fotos usando $request->file()
                // La ruta es: productos_prenda[$index][fotos]
                $fotosFiles = $request->file("productos_prenda.$index.fotos") ?? [];
                
                \Log::info("  📸 Fotos FILES tipo y contenido", [
                    'tipo' => gettype($fotosFiles),
                    'es_array' => is_array($fotosFiles),
                    'count' => is_array($fotosFiles) ? count($fotosFiles) : 0
                ]);
                
                if (is_array($fotosFiles)) {
                    foreach ($fotosFiles as $idx => $foto) {
                        \Log::info("    📸 Foto FILE $idx", [
                            'tipo' => gettype($foto),
                            'class' => is_object($foto) ? get_class($foto) : 'N/A',
                            'es_uploaded_file' => $foto instanceof \Illuminate\Http\UploadedFile,
                            'nombre_original' => $foto instanceof \Illuminate\Http\UploadedFile ? $foto->getClientOriginalName() : 'N/A'
                        ]);
                        
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            try {
                                $nombreArchivo = $this->imagenService->guardarImagenPrenda($foto, $cotizacion->id);
                                $fotosGuardadas[] = $nombreArchivo;
                                \Log::info("      ✅ Foto guardada: $nombreArchivo");
                            } catch (\Exception $e) {
                                \Log::error("      ❌ Error al guardar foto prenda", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }
                    }
                }
                
                \Log::info("  📸 Fotos procesadas y guardadas", ['fotos' => $fotosGuardadas]);
                
                // Procesar telas (GUARDAR EN DISCO)
                $telasGuardadas = [];
                
                // Acceder a los archivos de telas usando $request->file()
                // La ruta es: productos_prenda[$index][telas]
                $telasFiles = $request->file("productos_prenda.$index.telas") ?? [];
                
                \Log::info("  🧵 Telas FILES tipo y contenido", [
                    'tipo' => gettype($telasFiles),
                    'es_array' => is_array($telasFiles),
                    'count' => is_array($telasFiles) ? count($telasFiles) : 0
                ]);
                
                if (is_array($telasFiles)) {
                    foreach ($telasFiles as $idx => $tela) {
                        \Log::info("    🧵 Tela FILE $idx", [
                            'tipo' => gettype($tela),
                            'class' => is_object($tela) ? get_class($tela) : 'N/A',
                            'es_uploaded_file' => $tela instanceof \Illuminate\Http\UploadedFile,
                            'nombre_original' => $tela instanceof \Illuminate\Http\UploadedFile ? $tela->getClientOriginalName() : 'N/A'
                        ]);
                        
                        if ($tela instanceof \Illuminate\Http\UploadedFile) {
                            try {
                                $nombreArchivo = $this->imagenService->guardarImagenTela($tela, $cotizacion->id);
                                $telasGuardadas[] = $nombreArchivo;
                                \Log::info("      ✅ Tela guardada: $nombreArchivo");
                            } catch (\Exception $e) {
                                \Log::error("      ❌ Error al guardar foto tela", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }
                    }
                }
                
                \Log::info("  🧵 Telas procesadas y guardadas", ['telas' => $telasGuardadas]);
                
                // Procesar variantes - convertir al formato esperado por la DB
                $variantes = $producto['variantes'] ?? [];
                if (!is_array($variantes)) {
                    $variantes = [];
                }
                
                // Transformar variantes del formulario al formato de DB
                $variantesTransformadas = [];
                $observaciones = [];
                
                // Procesar checkboxes y observaciones
                if ($variantes['aplica_manga'] ?? false) {
                    $variantesTransformadas['tiene_manga'] = true;
                    if ($variantes['obs_manga'] ?? false) {
                        $observaciones[] = "Manga: " . $variantes['obs_manga'];
                    }
                }
                
                if ($variantes['aplica_bolsillos'] ?? false) {
                    $variantesTransformadas['tiene_bolsillos'] = true;
                    if ($variantes['obs_bolsillos'] ?? false) {
                        $observaciones[] = "Bolsillos: " . $variantes['obs_bolsillos'];
                    }
                }
                
                if ($variantes['aplica_broche'] ?? false) {
                    $variantesTransformadas['tiene_broche'] = true;
                    if ($variantes['obs_broche'] ?? false) {
                        $observaciones[] = "Broche: " . $variantes['obs_broche'];
                    }
                }
                
                if ($variantes['aplica_reflectivo'] ?? false) {
                    $variantesTransformadas['tiene_reflectivo'] = true;
                    if ($variantes['obs_reflectivo'] ?? false) {
                        $observaciones[] = "Reflectivo: " . $variantes['obs_reflectivo'];
                    }
                }
                
                // GUARDAR COLOR, TELA Y REFERENCIA
                if ($variantes['color'] ?? false) {
                    $variantesTransformadas['color'] = $variantes['color'];
                }
                
                if ($variantes['tela'] ?? false) {
                    $variantesTransformadas['tela'] = $variantes['tela'];
                }
                
                if ($variantes['referencia'] ?? false) {
                    $variantesTransformadas['referencia'] = $variantes['referencia'];
                }
                
                // Agregar referencias y datos existentes (legado)
                if ($variantes['tela_referencia'] ?? false) {
                    $variantesTransformadas['tela_referencia'] = $variantes['tela_referencia'];
                }
                
                if ($variantes['color_id'] ?? false) {
                    $variantesTransformadas['color_id'] = $variantes['color_id'];
                }
                
                if ($variantes['tela_id'] ?? false) {
                    $variantesTransformadas['tela_id'] = $variantes['tela_id'];
                }
                
                if ($variantes['tipo_manga_id'] ?? false) {
                    $variantesTransformadas['tipo_manga_id'] = $variantes['tipo_manga_id'];
                }
                
                // Agregar descripción adicional
                if (!empty($observaciones)) {
                    $variantesTransformadas['descripcion_adicional'] = implode(' | ', $observaciones);
                }
                
                \Log::info("  ✨ Variantes transformadas", ['variantes' => json_encode($variantesTransformadas, JSON_UNESCAPED_UNICODE)]);
                
                // Construir producto normalizado
                $productoNormalizado = [
                    'nombre_producto' => $nombre,
                    'descripcion' => $descripcion,
                    'tallas' => $tallas,
                    'fotos' => $fotosGuardadas,  // ← Nombres de archivos guardados
                    'telas' => $telasGuardadas,  // ← Nombres de archivos guardados
                    'variantes' => $variantesTransformadas,
                ];
                
                $productos[] = $productoNormalizado;
                
                \Log::info("✅ Producto normalizado", [
                    'nombre' => $nombre,
                    'tallas_count' => count($tallas),
                    'fotos_count' => count($fotosGuardadas),
                    'telas_count' => count($telasGuardadas),
                    'variantes_count' => count($variantesTransformadas)
                ]);
            }
            
            \Log::info('✅ Productos normalizados finales', [
                'cantidad' => count($productos),
                'productos_resumen' => collect($productos)->map(fn($p) => [
                    'nombre' => $p['nombre_producto'],
                    'tallas' => $p['tallas'],
                    'fotos' => $p['fotos'],
                    'telas' => $p['telas']
                ])->toArray()
            ]);
            
            // Crear prendas asociadas
            if (!empty($productos)) {
                $this->prendaService->crearPrendasCotizacion($cotizacion, $productos);
            } else {
                \Log::warning("⚠️ No hay productos válidos para guardar");
            }

            $mensaje = ($tipo === 'borrador')
                ? 'Cotización de prenda guardada en borrador'
                : 'Cotización de prenda enviada correctamente';

            $redirect = ($tipo === 'borrador')
                ? route('asesores.cotizaciones-prenda.edit', $cotizacion->id)
                : route('asesores.cotizaciones.index');

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'cotizacion_id' => $cotizacion->id,
                'redirect' => $redirect
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error al guardar cotización de prenda', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la cotización: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar cotización de prenda para edición
     */
    public function edit($id): View
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        return view('cotizaciones.prenda.edit', compact('cotizacion'));
    }

    /**
     * Actualizar cotización de prenda
     */
    public function update(Request $request, $id)
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'required|string|max:255',
                'action' => 'required|in:borrador,enviar',
                'tipo_cotizacion' => 'nullable|string',
            ]);

            $tipo = $validated['action'];
            
            $datosFormulario = [
                'cliente' => $validated['cliente'],
                'asesora' => $validated['asesora'],
                'tipo_cotizacion' => $validated['tipo_cotizacion'] ?? null,
                'productos' => $request->input('productos_prenda', []),
            ];

            $this->cotizacionService->actualizarBorrador($cotizacion, $datosFormulario);

            $mensaje = ($tipo === 'borrador')
                ? 'Cotización actualizada en borrador'
                : 'Cotización actualizada y enviada';

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'redirect' => route('asesores.cotizaciones.index')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar cotizaciones de prenda
     */
    public function lista()
    {
        $cotizaciones = Cotizacion::where('user_id', auth()->id())
            ->where('tipo_cotizacion_id', 3) // Tipo Prenda
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cotizaciones.prenda.lista', compact('cotizaciones'));
    }

    /**
     * Enviar cotización de prenda
     */
    public function enviar(Request $request, $id)
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $this->cotizacionService->cambiarEstado($cotizacion, 'enviada');

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada correctamente',
                'redirect' => route('asesores.cotizaciones.index')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cotización de prenda
     */
    public function destroy($id)
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $cotizacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
}
