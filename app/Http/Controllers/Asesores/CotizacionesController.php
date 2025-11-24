<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaCotizacionFriendly;
use App\Models\ProcesoPrenda;
use App\Models\VariantePrenda;
use App\Models\TipoPrenda;
use App\Services\ImagenCotizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CotizacionesController extends Controller
{
    /**
     * Mostrar lista de cotizaciones y borradores
     */
    public function index()
    {
        $cotizaciones = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', false)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        $borradores = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('asesores.cotizaciones.index', compact('cotizaciones', 'borradores'));
    }

    /**
     * Guardar cotización o borrador (nueva o actualización)
     */
    public function guardar(Request $request)
    {
        try {
            \Log::info('🚀 MÉTODO GUARDAR LLAMADO');
            
            $tipo = $request->input('tipo', 'borrador'); // 'borrador' o 'enviada'
            $cliente = $request->input('cliente');
            $cotizacionId = $request->input('cotizacion_id'); // ID si es actualización

            // Log para debugging
            \Log::info('Guardando cotización', [
                'tipo' => $tipo,
                'cliente' => $cliente,
                'cotizacion_id' => $cotizacionId,
                'user_id' => Auth::id()
            ]);

            // Si tiene cotizacion_id, es una actualización de borrador
            if ($cotizacionId) {
                return $this->actualizarBorrador($request, $cotizacionId);
            }

            // Recopilar datos del formulario para NUEVA cotización
            // NOTA: productos, especificaciones, imagenes, tecnicas, observaciones_tecnicas, 
            // ubicaciones, observaciones_generales se guardan como JSON
            
            // Obtener el tipo de cotización (M, D, X)
            $tipoCodigo = $request->input('tipo_cotizacion');
            \Log::info('Tipo de cotización recibido', ['tipo_codigo' => $tipoCodigo]);
            
            $tipoCotizacion = null;
            if ($tipoCodigo) {
                $tipoCotizacion = \App\Models\TipoCotizacion::where('codigo', $tipoCodigo)->first();
                \Log::info('Tipo de cotización encontrado', [
                    'tipo_codigo' => $tipoCodigo,
                    'tipo_id' => $tipoCotizacion ? $tipoCotizacion->id : null
                ]);
            }
            
            // Procesar productos, técnicas, ubicaciones, observaciones ANTES de crear la cotización
            $productos = $request->input('productos', []);
            $tecnicas = $request->input('tecnicas', []);
            $ubicacionesRaw = $request->input('ubicaciones', []);
            $imagenes = $request->input('imagenes', []);
            $especificacionesGenerales = $request->input('especificaciones', []);
            $observacionesTexto = $request->input('observaciones_generales', []);
            $observacionesCheck = $request->input('observaciones_check', []);
            $observacionesValor = $request->input('observaciones_valor', []);
            
            // Procesar observaciones generales con su tipo
            $observacionesGenerales = [];
            foreach ($observacionesTexto as $index => $obs) {
                if (!empty($obs)) {
                    $checkValue = $observacionesCheck[$index] ?? null;
                    $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
                    $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
                    
                    $observacionesGenerales[] = [
                        'texto' => $obs,
                        'tipo' => $tipo,
                        'valor' => $valor
                    ];
                }
            }
            
            // Procesar ubicaciones
            $ubicaciones = [];
            if (is_array($ubicacionesRaw)) {
                foreach ($ubicacionesRaw as $item) {
                    if (is_array($item) && isset($item['seccion'])) {
                        $ubicaciones[] = $item;
                    } else {
                        $ubicaciones[] = [
                            'seccion' => 'GENERAL',
                            'ubicaciones_seleccionadas' => [$item]
                        ];
                    }
                }
            }
            
            // Convertir especificaciones a array si es necesario
            if (!is_array($especificacionesGenerales)) {
                $especificacionesGenerales = (array) $especificacionesGenerales;
            }
            
            // Generar numero_cotizacion SOLO si se envía (no si es borrador)
            $numeroCotizacion = null;
            
            if ($tipo === 'enviada') {
                // Obtener el último código enviado
                $ultimaCotizacion = Cotizacion::where('es_borrador', false)
                    ->whereNotNull('numero_cotizacion')
                    ->orderBy('id', 'desc')
                    ->first();
                
                // Extraer el número del último código (COT-00001 -> 1)
                $ultimoNumero = 0;
                if ($ultimaCotizacion && $ultimaCotizacion->numero_cotizacion) {
                    preg_match('/\d+/', $ultimaCotizacion->numero_cotizacion, $matches);
                    $ultimoNumero = isset($matches[0]) ? (int)$matches[0] : 0;
                }
                
                // Generar siguiente código
                $nuevoNumero = $ultimoNumero + 1;
                $numeroCotizacion = 'COT-' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
                
                \Log::info('✅ Generando código de cotización', [
                    'tipo' => $tipo,
                    'ultimo_numero' => $ultimoNumero,
                    'nuevo_numero' => $nuevoNumero,
                    'numero_cotizacion' => $numeroCotizacion
                ]);
            } else {
                \Log::info('⚠️ NO se genera código (es borrador)', ['tipo' => $tipo]);
            }

            $datos = [
                'user_id' => Auth::id(),
                'numero_cotizacion' => $numeroCotizacion,
                'tipo_cotizacion_id' => $tipoCotizacion ? $tipoCotizacion->id : null,
                'fecha_inicio' => now(),
                'cliente' => $cliente,
                'asesora' => auth()->user()?->name ?? 'Sin nombre',
                'es_borrador' => ($tipo === 'borrador'),
                'estado' => 'enviada',
                'fecha_envio' => ($tipo === 'enviada') ? now() : null,
                // Guardar datos de PASO 2 y PASO 3 directamente en cotizaciones
                'productos' => !empty($productos) ? $productos : null,
                'especificaciones' => !empty($especificacionesGenerales) ? $especificacionesGenerales : null,
                'imagenes' => !empty($imagenes) ? $imagenes : null,
                'tecnicas' => !empty($tecnicas) ? $tecnicas : null,
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas'),
                'ubicaciones' => !empty($ubicaciones) ? $ubicaciones : null,
                'observaciones_generales' => !empty($observacionesGenerales) ? $observacionesGenerales : null
            ];

            \Log::info('Datos a guardar (nueva cotización)', $datos);

            $cotizacion = Cotizacion::create($datos);

            \Log::info('Cotización guardada exitosamente', [
                'id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'es_borrador' => $cotizacion->es_borrador
            ]);

            // Guardar prendas en tabla prendas_cotizaciones
            \Log::info('Productos a guardar en prendas_cotizaciones', [
                'cantidad' => count($productos),
                'productos' => $productos
            ]);
            
            if (!empty($productos)) {
                foreach ($productos as $index => $producto) {
                    $tallas = is_array($producto['tallas'] ?? []) ? $producto['tallas'] : [];
                    $nombrePrenda = $producto['nombre_producto'] ?? '';
                    
                    // Detectar si es JEAN o PANTALÓN
                    $nombreUpper = strtoupper(trim($nombrePrenda));
                    $palabraPrincipal = explode(' ', $nombreUpper)[0];
                    $esJeanPantalon = preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal) === 1;
                    
                    // Obtener tipo de JEAN/PANTALÓN de forma segura
                    $tipoJeanPantalon = null;
                    if ($esJeanPantalon && is_array($producto['variantes'] ?? null)) {
                        $tipoJeanPantalon = $producto['variantes']['tipo'] ?? null;
                    }
                    
                    \Log::info('Guardando prenda individual', [
                        'index' => $index,
                        'nombre' => $nombrePrenda,
                        'descripcion' => $producto['descripcion'] ?? null,
                        'tallas' => $tallas,
                        'es_jean_pantalon' => $esJeanPantalon,
                        'tipo_jean_pantalon' => $tipoJeanPantalon
                    ]);
                    
                    // Obtener género de las variantes
                    $genero = null;
                    if (is_array($producto['variantes'] ?? null) && isset($producto['variantes']['genero'])) {
                        $genero = $producto['variantes']['genero'];
                    }
                    
                    // Guardar prenda SIN fotos ni tela (se subirán después)
                    $prenda = \App\Models\PrendaCotizacionFriendly::create([
                        'cotizacion_id' => $cotizacion->id,
                        'nombre_producto' => $nombrePrenda,
                        'genero' => $genero,
                        'es_jean_pantalon' => $esJeanPantalon,
                        'tipo_jean_pantalon' => $tipoJeanPantalon,
                        'descripcion' => $producto['descripcion'] ?? null,
                        'tallas' => $tallas,
                        'fotos' => [], // Array vacío, se llenará después
                        'imagen_tela' => null, // Se llenará después
                        'estado' => 'Pendiente'
                    ]);
                    
                    \Log::info('Prenda guardada exitosamente', [
                        'id' => $prenda->id,
                        'nombre' => $prenda->nombre_producto,
                        'tallas_guardadas' => $prenda->tallas
                    ]);
                    
                    // GUARDAR VARIANTES DE LA PRENDA
                    $this->guardarVariantesPrenda($prenda, $producto);
                }
                \Log::info('Prendas guardadas exitosamente', ['cantidad' => count($productos)]);
            } else {
                \Log::warning('No hay productos para guardar en prendas_cotizaciones');
            }

            // Guardar datos de PASO 3 (Bordado/Estampado) en tabla logo_cotizaciones
            $observacionesValor = $request->input('observaciones_valor', []);
            $observacionesValor = $request->input('observaciones_valor', []);
            
            \Log::info('🔍 DATOS RECIBIDOS DEL CLIENTE:', [
                'observaciones_generales' => $observacionesTexto,
                'observaciones_check' => $observacionesCheck,
                'observaciones_check_type' => gettype($observacionesCheck),
                'observaciones_check_count' => count($observacionesCheck),
                'observaciones_valor' => $observacionesValor
            ]);
            
            // Debug: mostrar cada elemento
            foreach ($observacionesCheck as $idx => $val) {
                \Log::info("Check[$idx] = " . json_encode($val) . " (type: " . gettype($val) . ")");
            }
            
            foreach ($observacionesTexto as $index => $obs) {
                if (!empty($obs)) {
                    // Determinar si es checkbox o texto
                    // Si observaciones_check[$index] es 'on', es checkbox; si es null, es texto
                    $checkValue = $observacionesCheck[$index] ?? null;
                    $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
                    $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
                    
                    \Log::info('📝 Procesando observación:', [
                        'texto' => $obs,
                        'checkValue' => $checkValue,
                        'tipo' => $tipo,
                        'valor' => $valor
                    ]);
                    
                    $observacionesGenerales[] = [
                        'texto' => $obs,
                        'tipo' => $tipo,
                        'valor' => $valor
                    ];
                }
            }
            
            $tecnicas = $request->input('tecnicas', []);
            $ubicacionesRaw = $request->input('ubicaciones', []);
            $imagenes = $request->input('imagenes', []);
            
            // Procesar ubicaciones: si es array de objetos, mantener estructura; si es array simple, convertir
            $ubicaciones = [];
            if (is_array($ubicacionesRaw)) {
                foreach ($ubicacionesRaw as $item) {
                    if (is_array($item) && isset($item['seccion'])) {
                        // Ya es estructura correcta
                        $ubicaciones[] = $item;
                    } else {
                        // Es string simple, agregar como ubicación sin sección
                        $ubicaciones[] = [
                            'seccion' => 'GENERAL',
                            'ubicaciones_seleccionadas' => [$item]
                        ];
                    }
                }
            }
            
            \Log::info('📝 Datos de PASO 3 recibidos:', [
                'tecnicas' => $tecnicas,
                'ubicaciones' => $ubicaciones,
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas'),
                'observaciones_generales' => $observacionesGenerales,
                'imagenes_count' => count($imagenes)
            ]);
            
            $logoCotizacionData = [
                'cotizacion_id' => $cotizacion->id,
                'imagenes' => $imagenes,
                'tecnicas' => $tecnicas,
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas'),
                'ubicaciones' => $ubicaciones,
                'observaciones_generales' => $observacionesGenerales
            ];
            
            \Log::info('💾 Guardando LogoCotizacion:', $logoCotizacionData);
            
            \App\Models\LogoCotizacion::create($logoCotizacionData);

            // Registrar en historial
            \App\Models\HistorialCotizacion::create([
                'cotizacion_id' => $cotizacion->id,
                'tipo_cambio' => 'creacion',
                'descripcion' => 'Cotización creada',
                'usuario_id' => Auth::id(),
                'usuario_nombre' => auth()->user()?->name ?? 'Sin nombre',
                'ip_address' => request()->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => ($tipo === 'borrador') ? 'Cotización guardada en borradores' : 'Cotización enviada correctamente',
                'cotizacion_id' => $cotizacion->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar cotización', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Ver detalle de cotización
     */
    public function show($id)
    {
        $cotizacion = Cotizacion::with([
            'prendasCotizaciones.variantes.color',
            'prendasCotizaciones.variantes.tela',
            'prendasCotizaciones.variantes.tipoManga',
            'prendasCotizaciones.variantes.tipoBroche'
        ])->findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        // Obtener datos de logo/bordado/estampado
        $logo = $cotizacion->logoCotizacion;

        // Si es una petición AJAX, retornar JSON
        if (request()->wantsJson()) {
            return response()->json([
                'id' => $cotizacion->id,
                'cliente' => $cotizacion->cliente,
                'asesora' => $cotizacion->asesora,
                'prendas' => $cotizacion->prendasCotizaciones->map(function($prenda) {
                    $variante = $prenda->variantes->first();
                    
                    return [
                        'id' => $prenda->id,
                        'nombre_producto' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion,
                        'tallas' => $prenda->tallas ?? [],
                        'fotos' => $prenda->fotos ?? [],
                        'imagen_tela' => $prenda->imagen_tela,
                        // Variaciones
                        'variantes' => [
                            'color' => $variante && $variante->color ? $variante->color->nombre : null,
                            'tela' => $variante && $variante->tela ? $variante->tela->nombre : null,
                            'tela_referencia' => $variante && $variante->tela && $variante->tela->referencia ? $variante->tela->referencia : null,
                            'manga' => $variante && $variante->tipoManga ? $variante->tipoManga->nombre : null,
                            'broche' => $variante && $variante->tipoBroche ? $variante->tipoBroche->nombre : null,
                            'tiene_bolsillos' => $variante ? $variante->tiene_bolsillos : false,
                            'tiene_reflectivo' => $variante ? $variante->tiene_reflectivo : false,
                            'observaciones' => $variante ? $variante->descripcion_adicional : null
                        ]
                    ];
                })
            ]);
        }

        return view('asesores.cotizaciones.show', compact('cotizacion', 'logo'));
    }

    /**
     * Editar borrador
     */
    public function editarBorrador($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id() || !$cotizacion->es_borrador) {
            abort(403);
        }

        return view('asesores.pedidos.create-friendly', [
            'cotizacion' => $cotizacion,
            'esEdicion' => true
        ]);
    }

    /**
     * Actualizar borrador (sin cambiar fecha_inicio)
     */
    private function actualizarBorrador(Request $request, $cotizacionId)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($cotizacionId);
            
            if ($cotizacion->user_id !== Auth::id() || !$cotizacion->es_borrador) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para actualizar este borrador'
                ], 403);
            }

            \Log::info('Actualizando borrador', ['cotizacion_id' => $cotizacionId]);

            // Actualizar datos de la cotización (SIN cambiar fecha_inicio)
            $tipoCodigo = $request->input('tipo_cotizacion');
            \Log::info('Actualizando borrador - Tipo de cotización recibido', ['tipo_codigo' => $tipoCodigo]);
            
            $tipoCotizacion = null;
            if ($tipoCodigo) {
                $tipoCotizacion = \App\Models\TipoCotizacion::where('codigo', $tipoCodigo)->first();
                \Log::info('Actualizando borrador - Tipo de cotización encontrado', [
                    'tipo_codigo' => $tipoCodigo,
                    'tipo_id' => $tipoCotizacion ? $tipoCotizacion->id : null
                ]);
            }
            
            $datosActualizar = [
                'numero_cotizacion' => $request->input('numero_cotizacion'),
                'tipo_cotizacion_id' => $tipoCotizacion ? $tipoCotizacion->id : null,
                'cliente' => $request->input('cliente'),
                'asesora' => auth()->user()?->name ?? 'Sin nombre',
            ];

            $cotizacion->update($datosActualizar);

            // Eliminar prendas anteriores
            $cotizacion->prendasCotizaciones()->delete();

            // Guardar nuevas prendas
            $productos = $request->input('productos', []);
            $especificaciones = [];
            
            if (!empty($productos)) {
                foreach ($productos as $index => $producto) {
                    $tallas = is_array($producto['tallas'] ?? []) ? $producto['tallas'] : [];
                    $nombrePrenda = $producto['nombre_producto'] ?? '';
                    
                    // Detectar si es JEAN o PANTALÓN
                    $nombreUpper = strtoupper(trim($nombrePrenda));
                    $palabraPrincipal = explode(' ', $nombreUpper)[0];
                    $esJeanPantalon = preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal) === 1;
                    
                    // Obtener tipo de JEAN/PANTALÓN de forma segura
                    $tipoJeanPantalon = null;
                    if ($esJeanPantalon && is_array($producto['variantes'] ?? null)) {
                        $tipoJeanPantalon = $producto['variantes']['tipo'] ?? null;
                    }
                    
                    // Obtener género de las variantes
                    $genero = null;
                    if (is_array($producto['variantes'] ?? null) && isset($producto['variantes']['genero'])) {
                        $genero = $producto['variantes']['genero'];
                    }
                    
                    $prenda = \App\Models\PrendaCotizacionFriendly::create([
                        'cotizacion_id' => $cotizacion->id,
                        'nombre_producto' => $nombrePrenda,
                        'genero' => $genero,
                        'es_jean_pantalon' => $esJeanPantalon,
                        'tipo_jean_pantalon' => $tipoJeanPantalon,
                        'descripcion' => $producto['descripcion'] ?? null,
                        'tallas' => $tallas,
                        'fotos' => [],
                        'imagen_tela' => null,
                        'estado' => 'Pendiente'
                    ]);

                    $especificaciones[] = [
                        'nombre_producto' => $producto['nombre_producto'] ?? null,
                        'disponibilidad' => $producto['disponibilidad'] ?? null,
                        'forma_pago' => $producto['forma_pago'] ?? null,
                        'regimen' => $producto['regimen'] ?? null,
                        'se_ha_vendido' => $producto['se_ha_vendido'] ?? null,
                        'ultima_venta' => $producto['ultima_venta'] ?? null,
                        'observacion' => $producto['observacion'] ?? null
                    ];
                }
            }

            // Actualizar especificaciones
            if (!empty($especificaciones)) {
                $cotizacion->update(['especificaciones' => $especificaciones]);
            }

            // Actualizar logo_cotizaciones
            $logo = $cotizacion->logoCotizacion;
            if ($logo) {
                $logo->update([
                    'imagenes' => $request->input('imagenes', []),
                    'tecnicas' => $request->input('tecnicas', []),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas'),
                    'ubicaciones' => $request->input('ubicaciones', []),
                    'observaciones_generales' => $request->input('observaciones_generales', [])
                ]);
            }

            // Registrar en historial
            \App\Models\HistorialCotizacion::create([
                'cotizacion_id' => $cotizacionId,
                'tipo_cambio' => 'actualizacion',
                'descripcion' => 'Borrador actualizado',
                'usuario_id' => Auth::id(),
                'usuario_nombre' => auth()->user()?->name ?? 'Sin nombre',
                'ip_address' => request()->ip()
            ]);

            \Log::info('Borrador actualizado exitosamente', ['cotizacion_id' => $cotizacionId]);

            return response()->json([
                'success' => true,
                'message' => 'Borrador actualizado correctamente',
                'cotizacion_id' => $cotizacion->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar borrador', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Subir imágenes a una cotización y guardar rutas en prendas_cotizaciones
     */
    public function subirImagenes(Request $request, $id)
    {
        \Log::info('=== INICIO SUBIR IMAGENES ===', ['cotizacion_id' => $id]);

        $cotizacion = Cotizacion::findOrFail($id);

        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tipo' => 'required|in:bordado,estampado,tela,prenda,general'
        ]);

        try {
            $tipo = $request->input('tipo');
            $archivos = $request->file('imagenes', []);

            if (empty($archivos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay imágenes para subir'
                ], 400);
            }

            $rutasGuardadas = [];
            $carpeta = "cotizaciones/{$id}/{$tipo}";

            foreach ($archivos as $index => $archivo) {
                try {
                    // Obtener extensión original
                    $extensionOriginal = strtolower($archivo->getClientOriginalExtension());
                    
                    // Generar nombre - mantener extensión original si WebP falla
                    $nombreRenombrado = "{$id}_{$tipo}_{$index}.webp";
                    $nombreFallback = "{$id}_{$tipo}_{$index}.{$extensionOriginal}";

                    // Asegurar que la carpeta existe
                    if (!Storage::disk('public')->exists($carpeta)) {
                        Storage::disk('public')->makeDirectory($carpeta, 0755, true);
                    }

                    $rutaTemporal = storage_path("app/public/{$carpeta}/{$nombreRenombrado}");
                    $rutaOriginal = $archivo->getRealPath();
                    $archivoGuardado = false;
                    $nombreFinal = $nombreRenombrado;

                    // Intentar usar cwebp si está disponible
                    if (shell_exec('where cwebp 2>nul') || shell_exec('which cwebp 2>/dev/null')) {
                        $comando = "cwebp -q 80 \"{$rutaOriginal}\" -o \"{$rutaTemporal}\"";
                        @shell_exec($comando . " 2>&1");
                        if (file_exists($rutaTemporal) && filesize($rutaTemporal) > 0) {
                            $archivoGuardado = true;
                        }
                    }

                    // Si cwebp no funcionó, intentar con GD
                    if (!$archivoGuardado && extension_loaded('gd')) {
                        try {
                            $contenidoOriginal = file_get_contents($rutaOriginal);
                            $imagen = @imagecreatefromstring($contenidoOriginal);

                            if ($imagen !== false) {
                                @imagewebp($imagen, $rutaTemporal, 80);
                                @imagedestroy($imagen);
                                if (file_exists($rutaTemporal) && filesize($rutaTemporal) > 0) {
                                    $archivoGuardado = true;
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Error al convertir con GD: " . $e->getMessage());
                        }
                    }

                    // Si nada funcionó, guardar en formato original
                    if (!$archivoGuardado) {
                        $rutaTemporal = storage_path("app/public/{$carpeta}/{$nombreFallback}");
                        $archivo->storeAs($carpeta, $nombreFallback, 'public');
                        $nombreFinal = $nombreFallback;
                        $archivoGuardado = true;
                    }

                    $rutaCompleta = '/storage/' . $carpeta . '/' . $nombreFinal;
                    $rutasGuardadas[] = $rutaCompleta;

                    $tamanoOriginal = $archivo->getSize();
                    $tamanoFinal = file_exists($rutaTemporal) ? filesize($rutaTemporal) : 0;
                    $reduccion = $tamanoFinal > 0 ? round((1 - $tamanoFinal / $tamanoOriginal) * 100, 2) : 0;

                    \Log::info("Imagen guardada", [
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'nombre_renombrado' => $nombreFinal,
                        'ruta' => $rutaCompleta,
                        'tamano_original_kb' => round($tamanoOriginal / 1024, 2),
                        'tamano_final_kb' => round($tamanoFinal / 1024, 2),
                        'reduccion_porcentaje' => $reduccion . '%',
                        'metodo' => str_ends_with($nombreFinal, '.webp') ? 'WebP' : 'Original'
                    ]);

                } catch (\Exception $e) {
                    \Log::error("Error al procesar imagen", [
                        'archivo' => $archivo->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    // Continuar con la siguiente imagen
                    continue;
                }
            }

            // Actualizar prendas_cotizaciones o logo_cotizaciones con las rutas
            if ($tipo === 'prenda' || $tipo === 'tela') {
                $prendas = $cotizacion->prendasCotizaciones;
                
                if ($tipo === 'prenda') {
                    // Para fotos de prenda, usar el índice enviado desde el frontend
                    $prendaIndexes = $request->input('prendaIndex', []);
                    
                    \Log::info("Procesando fotos de prenda", [
                        'rutasGuardadas' => $rutasGuardadas,
                        'prendaIndexes' => $prendaIndexes,
                        'total_prendas' => count($prendas)
                    ]);
                    
                    // Agrupar fotos por índice de prenda
                    $fotosPorPrenda = [];
                    foreach ($rutasGuardadas as $index => $rutaFoto) {
                        $prendaIndex = isset($prendaIndexes[$index]) ? intval($prendaIndexes[$index]) : $index;
                        if (!isset($fotosPorPrenda[$prendaIndex])) {
                            $fotosPorPrenda[$prendaIndex] = [];
                        }
                        $fotosPorPrenda[$prendaIndex][] = $rutaFoto;
                        
                        \Log::info("Foto agrupada", [
                            'foto_index' => $index,
                            'ruta' => $rutaFoto,
                            'prenda_index' => $prendaIndex
                        ]);
                    }
                    
                    \Log::info("Fotos agrupadas por prenda", [
                        'fotosPorPrenda' => $fotosPorPrenda
                    ]);
                    
                    // Actualizar cada prenda con sus fotos
                    foreach ($fotosPorPrenda as $prendaIndex => $fotos) {
                        \Log::info("Actualizando prenda", [
                            'prenda_index' => $prendaIndex,
                            'existe_en_array' => isset($prendas[$prendaIndex]),
                            'total_prendas_array' => count($prendas)
                        ]);
                        
                        if (isset($prendas[$prendaIndex])) {
                            $prenda = $prendas[$prendaIndex];
                            $fotosActuales = $prenda->fotos ?? [];
                            if (!is_array($fotosActuales)) {
                                $fotosActuales = [];
                            }
                            
                            \Log::info("Fotos actuales en prenda", [
                                'prenda_id' => $prenda->id,
                                'fotos_actuales' => $fotosActuales,
                                'fotos_nuevas' => $fotos
                            ]);
                            
                            $fotosActuales = array_merge($fotosActuales, $fotos);
                            $prenda->update(['fotos' => $fotosActuales]);

                            \Log::info("Prenda actualizada con fotos", [
                                'prenda_id' => $prenda->id,
                                'cantidad_fotos' => count($fotos),
                                'fotos_rutas' => $fotos,
                                'fotos_totales' => count($fotosActuales)
                            ]);
                        }
                    }
                } elseif ($tipo === 'tela') {
                    // Para telas, usar el índice enviado desde el frontend
                    $prendaIndexes = $request->input('prendaIndex', []);
                    
                    // Agrupar telas por índice de prenda
                    $telasPorPrenda = [];
                    foreach ($rutasGuardadas as $index => $rutaTela) {
                        $prendaIndex = isset($prendaIndexes[$index]) ? intval($prendaIndexes[$index]) : $index;
                        if (!isset($telasPorPrenda[$prendaIndex])) {
                            $telasPorPrenda[$prendaIndex] = [];
                        }
                        $telasPorPrenda[$prendaIndex][] = $rutaTela;
                    }
                    
                    // Actualizar cada prenda con sus telas
                    foreach ($telasPorPrenda as $prendaIndex => $telas) {
                        if (isset($prendas[$prendaIndex])) {
                            $prenda = $prendas[$prendaIndex];
                            $telasActuales = $prenda->telas ?? [];
                            if (!is_array($telasActuales)) {
                                $telasActuales = [];
                            }
                            $telasActuales = array_merge($telasActuales, $telas);
                            $prenda->update(['telas' => $telasActuales]);

                            \Log::info("Prenda actualizada con telas", [
                                'prenda_id' => $prenda->id,
                                'cantidad_telas' => count($telas),
                                'telas_rutas' => $telas
                            ]);
                        }
                    }
                }
            } elseif ($tipo === 'general') {
                // Actualizar logo_cotizaciones con imágenes generales
                $logo = $cotizacion->logoCotizacion;
                if ($logo) {
                    $imagenes = $logo->imagenes ?? [];
                    if (!is_array($imagenes)) {
                        $imagenes = [];
                    }
                    $imagenes = array_merge($imagenes, $rutasGuardadas);
                    $logo->update(['imagenes' => $imagenes]);

                    \Log::info("Logo actualizado con imágenes", [
                        'logo_id' => $logo->id,
                        'cantidad_imagenes' => count($imagenes)
                    ]);
                }
            }

            \Log::info('Imágenes subidas exitosamente', [
                'cotizacion_id' => $id,
                'tipo' => $tipo,
                'cantidad' => count($rutasGuardadas)
            ]);

            return response()->json([
                'success' => true,
                'message' => count($rutasGuardadas) . " imágenes de tipo '{$tipo}' guardadas",
                'rutas' => $rutasGuardadas
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imágenes', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cotización (solo si es borrador)
     */
    public function destroy($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        // Solo permitir eliminar si es borrador
        if (!$cotizacion->es_borrador) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden eliminar cotizaciones enviadas. Solo se pueden eliminar borradores.'
            ], 403);
        }

        // Eliminar imágenes de almacenamiento
        $imagenService = new ImagenCotizacionService();
        $imagenService->eliminarTodasLasImagenes($cotizacion->id);

        $cotizacion->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Borrador eliminado'
        ]);
    }

    /**
     * Cambiar estado de cotización (borrador → enviada, enviada → aceptada, etc.)
     */
    public function cambiarEstado($id, $estado)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        $datosActualizar = [
            'estado' => $estado,
            'es_borrador' => false // Cuando cambia estado, ya no es borrador
        ];
        
        // Si se envía (estado = 'enviada'), guardar fecha_envio
        if ($estado === 'enviada' && !$cotizacion->fecha_envio) {
            $datosActualizar['fecha_envio'] = now();
        }
        
        $cotizacion->update($datosActualizar);
        
        // Registrar en historial
        $tipoHistorial = ($estado === 'enviada') ? 'envio' : $estado;
        \App\Models\HistorialCotizacion::create([
            'cotizacion_id' => $id,
            'tipo_cambio' => $tipoHistorial,
            'descripcion' => 'Estado cambiado a: ' . ucfirst($estado),
            'usuario_id' => Auth::id(),
            'usuario_nombre' => auth()->user()?->name ?? 'Sin nombre',
            'ip_address' => request()->ip()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado'
        ]);
    }

    /**
     * Aceptar cotización y crear pedido de producción
     */
    public function aceptarCotizacion($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            // Crear pedido de producción
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_pedido' => $this->generarNumeroPedido(),
                'cliente' => $cotizacion->cliente,
                'asesora' => auth()->user()?->name ?? 'Sin nombre',
                'forma_de_pago' => $cotizacion->especificaciones['forma_pago'] ?? null,
                'estado' => 'No iniciado',
                'fecha_de_creacion_de_orden' => now()->toDateString(),
            ]);

            // Crear prendas del pedido
            if ($cotizacion->productos) {
                foreach ($cotizacion->productos as $index => $producto) {
                    $prenda = PrendaPedido::create([
                        'pedido_produccion_id' => $pedido->id,
                        'nombre_prenda' => $producto['nombre_producto'] ?? 'Sin nombre',
                        'cantidad' => $producto['cantidad'] ?? 1,
                        'descripcion' => $producto['descripcion'] ?? null,
                    ]);

                    // Crear proceso inicial para cada prenda
                    ProcesoPrenda::create([
                        'prenda_pedido_id' => $prenda->id,
                        'proceso' => 'Creación Orden',
                        'estado_proceso' => 'Completado',
                        'fecha_inicio' => now()->toDateString(),
                        'fecha_fin' => now()->toDateString(),
                    ]);
                    
                    // HEREDAR VARIANTES DE LA COTIZACIÓN
                    $this->heredarVariantesDePrendaPedido($cotizacion, $prenda, $index);
                }
            }

            // Actualizar cotización
            $cotizacion->update([
                'estado' => 'aceptada',
                'es_borrador' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización aceptada y pedido creado',
                'pedido_id' => $pedido->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Guardar variantes de una prenda
     */
    private function guardarVariantesPrenda($prenda, $productoData)
    {
        try {
            // Obtener nombre de la prenda
            $nombrePrenda = $productoData['nombre_producto'] ?? '';
            
            // Reconocer tipo de prenda por nombre
            $tipoPrenda = TipoPrenda::reconocerPorNombre($nombrePrenda);
            
            if (!$tipoPrenda) {
                \Log::warning('⚠️ No se pudo reconocer tipo de prenda', [
                    'nombre' => $nombrePrenda
                ]);
                return;
            }
            
            \Log::info('✅ Tipo de prenda reconocido', [
                'nombre' => $nombrePrenda,
                'tipo_id' => $tipoPrenda->id,
                'tipo_nombre' => $tipoPrenda->nombre
            ]);
            
            // Obtener variaciones seleccionadas (si existen en los datos)
            $variantes = $productoData['variantes'] ?? [];
            
            \Log::info('📝 Variantes recibidas del formulario:', $variantes);
            
            // Crear registro de variante con todos los datos
            $datosVariante = [
                'prenda_cotizacion_id' => $prenda->id,
                'tipo_prenda_id' => $tipoPrenda->id,
                'cantidad_talla' => $prenda->tallas ? json_encode($prenda->tallas) : null
            ];
            
            // Procesar color (buscar o crear)
            if (isset($variantes['color']) && !empty($variantes['color'])) {
                $color = \App\Models\ColorPrenda::firstOrCreate(
                    ['nombre' => $variantes['color']],
                    ['nombre' => $variantes['color']]
                );
                $datosVariante['color_id'] = $color->id;
                \Log::info('✅ Color encontrado/creado', ['color_id' => $color->id, 'nombre' => $variantes['color']]);
            }
            
            // Procesar tela (buscar o crear)
            if (isset($variantes['tela']) && !empty($variantes['tela'])) {
                $tela = \App\Models\TelaPrenda::firstOrCreate(
                    ['nombre' => $variantes['tela']],
                    ['nombre' => $variantes['tela']]
                );
                $datosVariante['tela_id'] = $tela->id;
                \Log::info('✅ Tela encontrada/creada', ['tela_id' => $tela->id, 'nombre' => $variantes['tela']]);
            }
            
            // Procesar manga (si es ID, usarlo; si es nombre, buscar)
            if (isset($variantes['tipo_manga_id']) && !empty($variantes['tipo_manga_id'])) {
                $manga = \App\Models\TipoManga::where('nombre', $variantes['tipo_manga_id'])
                    ->orWhere('id', $variantes['tipo_manga_id'])
                    ->first();
                if ($manga) {
                    $datosVariante['tipo_manga_id'] = $manga->id;
                    \Log::info('✅ Manga encontrada', ['manga_id' => $manga->id]);
                }
            }
            
            // Procesar broche (si es ID, usarlo; si es nombre, buscar)
            if (isset($variantes['tipo_broche_id']) && !empty($variantes['tipo_broche_id'])) {
                $broche = \App\Models\TipoBroche::where('nombre', $variantes['tipo_broche_id'])
                    ->orWhere('id', $variantes['tipo_broche_id'])
                    ->first();
                if ($broche) {
                    $datosVariante['tipo_broche_id'] = $broche->id;
                    \Log::info('✅ Broche encontrado', ['broche_id' => $broche->id]);
                }
            }
            
            // Procesar bolsillos
            if (isset($variantes['tiene_bolsillos'])) {
                $datosVariante['tiene_bolsillos'] = (bool)$variantes['tiene_bolsillos'];
            }
            
            // Procesar reflectivo
            if (isset($variantes['tiene_reflectivo'])) {
                $datosVariante['tiene_reflectivo'] = (bool)$variantes['tiene_reflectivo'];
            }
            
            // Procesar observaciones/descripción adicional
            $observacionesArray = [];
            
            // Recopilar todas las observaciones
            if (isset($variantes['obs_bolsillos']) && !empty($variantes['obs_bolsillos'])) {
                $observacionesArray[] = "Bolsillos: {$variantes['obs_bolsillos']}";
            }
            if (isset($variantes['obs_broche']) && !empty($variantes['obs_broche'])) {
                $observacionesArray[] = "Broche: {$variantes['obs_broche']}";
            }
            if (isset($variantes['obs_reflectivo']) && !empty($variantes['obs_reflectivo'])) {
                $observacionesArray[] = "Reflectivo: {$variantes['obs_reflectivo']}";
            }
            
            // Si viene descripcion_adicional directamente, usarla
            if (isset($variantes['descripcion_adicional']) && !empty($variantes['descripcion_adicional'])) {
                $datosVariante['descripcion_adicional'] = $variantes['descripcion_adicional'];
            } elseif (!empty($observacionesArray)) {
                // Si no, combinar todas las observaciones
                $datosVariante['descripcion_adicional'] = implode(' | ', $observacionesArray);
            }
            
            \Log::info('📝 Observaciones procesadas:', [
                'obs_bolsillos' => $variantes['obs_bolsillos'] ?? null,
                'obs_broche' => $variantes['obs_broche'] ?? null,
                'obs_reflectivo' => $variantes['obs_reflectivo'] ?? null,
                'descripcion_adicional' => $datosVariante['descripcion_adicional'] ?? null
            ]);
            
            // Crear registro de variante
            $variante = VariantePrenda::create($datosVariante);
            
            \Log::info('✅ Variante guardada exitosamente', [
                'variante_id' => $variante->id,
                'prenda_id' => $prenda->id,
                'tipo_prenda_id' => $tipoPrenda->id,
                'datos' => $datosVariante
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error guardando variantes', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Generar número de pedido único
     */
    private function generarNumeroPedido()
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 0;
        return $ultimoPedido + 1;
    }
}
