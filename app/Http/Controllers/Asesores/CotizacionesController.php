<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaCotizacionFriendly;
use App\Models\ProcesoPrenda;
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
            
            $datos = [
                'user_id' => Auth::id(),
                'numero_cotizacion' => $request->input('numero_cotizacion'),
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

            \Log::info('Cotización guardada exitosamente', ['id' => $cotizacion->id]);

            // Guardar prendas en tabla prendas_cotizaciones
            \Log::info('Productos a guardar en prendas_cotizaciones', [
                'cantidad' => count($productos),
                'productos' => $productos
            ]);
            
            if (!empty($productos)) {
                foreach ($productos as $index => $producto) {
                    $tallas = is_array($producto['tallas'] ?? []) ? $producto['tallas'] : [];
                    
                    \Log::info('Guardando prenda individual', [
                        'index' => $index,
                        'nombre' => $producto['nombre_producto'] ?? null,
                        'descripcion' => $producto['descripcion'] ?? null,
                        'tallas' => $tallas
                    ]);
                    
                    // Guardar prenda SIN fotos ni tela (se subirán después)
                    $prenda = \App\Models\PrendaCotizacionFriendly::create([
                        'cotizacion_id' => $cotizacion->id,
                        'nombre_producto' => $producto['nombre_producto'] ?? null,
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
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        // Obtener datos de logo/bordado/estampado
        $logo = $cotizacion->logoCotizacion;

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
                    
                    $prenda = \App\Models\PrendaCotizacionFriendly::create([
                        'cotizacion_id' => $cotizacion->id,
                        'nombre_producto' => $producto['nombre_producto'] ?? null,
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
                    // Generar nombre con extensión WebP
                    $nombreRenombrado = "{$id}_{$tipo}_{$index}.webp";

                    // Asegurar que la carpeta existe
                    if (!Storage::disk('public')->exists($carpeta)) {
                        Storage::disk('public')->makeDirectory($carpeta);
                    }

                    $rutaTemporal = storage_path("app/public/{$carpeta}/{$nombreRenombrado}");
                    $rutaOriginal = $archivo->getRealPath();

                    // Intentar usar cwebp si está disponible
                    $usoCwebp = false;
                    if (shell_exec('where cwebp 2>nul') || shell_exec('which cwebp 2>/dev/null')) {
                        $comando = "cwebp -q 80 \"{$rutaOriginal}\" -o \"{$rutaTemporal}\"";
                        shell_exec($comando . " 2>&1");
                        if (file_exists($rutaTemporal)) {
                            $usoCwebp = true;
                        }
                    }

                    // Si cwebp no funcionó, intentar con GD
                    if (!$usoCwebp && extension_loaded('gd')) {
                        $contenidoOriginal = file_get_contents($rutaOriginal);
                        $imagen = imagecreatefromstring($contenidoOriginal);

                        if ($imagen !== false) {
                            imagewebp($imagen, $rutaTemporal, 80);
                            imagedestroy($imagen);
                            $usoCwebp = true;
                        }
                    }

                    // Si nada funcionó, guardar como está (comprimido por el servidor)
                    if (!$usoCwebp) {
                        $archivo->storeAs($carpeta, $nombreRenombrado, 'public');
                    }

                    $rutaCompleta = '/storage/' . $carpeta . '/' . $nombreRenombrado;
                    $rutasGuardadas[] = $rutaCompleta;

                    $tamanoOriginal = $archivo->getSize();
                    $tamanoFinal = filesize($rutaTemporal);
                    $reduccion = round((1 - $tamanoFinal / $tamanoOriginal) * 100, 2);

                    \Log::info("Imagen guardada", [
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'nombre_renombrado' => $nombreRenombrado,
                        'ruta' => $rutaCompleta,
                        'tamano_original_kb' => round($tamanoOriginal / 1024, 2),
                        'tamano_final_kb' => round($tamanoFinal / 1024, 2),
                        'reduccion_porcentaje' => $reduccion . '%',
                        'metodo' => $usoCwebp ? 'WebP' : 'Original'
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
                    // Para fotos de prenda, asignar secuencialmente
                    $prendaIndex = 0;
                    foreach ($prendas as $prenda) {
                        if (isset($rutasGuardadas[$prendaIndex])) {
                            $fotos = $prenda->fotos ?? [];
                            if (!is_array($fotos)) {
                                $fotos = [];
                            }
                            $fotos[] = $rutasGuardadas[$prendaIndex];
                            $prenda->update(['fotos' => $fotos]);

                            \Log::info("Prenda actualizada con foto", [
                                'prenda_id' => $prenda->id,
                                'foto_ruta' => $rutasGuardadas[$prendaIndex]
                            ]);
                            $prendaIndex++;
                        }
                    }
                } elseif ($tipo === 'tela') {
                    // Para telas, usar el índice enviado desde el frontend
                    $prendaIndexes = $request->input('prendaIndex', []);
                    
                    foreach ($rutasGuardadas as $index => $rutaTela) {
                        // Obtener el índice de prenda para esta tela
                        $prendaIndex = isset($prendaIndexes[$index]) ? intval($prendaIndexes[$index]) : $index;
                        
                        // Obtener la prenda en ese índice
                        $prendas_array = $prendas->toArray();
                        if (isset($prendas_array[$prendaIndex])) {
                            $prenda = $prendas[$prendaIndex];
                            $prenda->update(['imagen_tela' => $rutaTela]);

                            \Log::info("Prenda actualizada con tela (índice correcto)", [
                                'prenda_id' => $prenda->id,
                                'prenda_index' => $prendaIndex,
                                'tela_ruta' => $rutaTela
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
                foreach ($cotizacion->productos as $producto) {
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
     * Generar número de pedido único
     */
    private function generarNumeroPedido()
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 0;
        return $ultimoPedido + 1;
    }
}
