<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use App\Models\CostoPrenda;
use App\Services\ImagenCotizacionService;
use App\Helpers\DescripcionPrendaHelper;
use App\Events\CotizacionEstadoCambiado;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ContadorController extends Controller
{
    /**
     * Mostrar el perfil del contador
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Por favor inicia sesión para ver tu perfil.');
            }
            
            return view('contador.profile', compact('user'));
            
        } catch (\Exception $e) {
            return redirect()->route('contador.index')->with('error', 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar el perfil del contador
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Validar los datos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'telefono' => 'nullable|string|max:20',
                'ciudad' => 'nullable|string|max:255',
                'departamento' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            // Actualizar información personal
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->telefono = $validated['telefono'] ?? $user->telefono;
            $user->ciudad = $validated['ciudad'] ?? $user->ciudad;
            $user->departamento = $validated['departamento'] ?? $user->departamento;
            $user->bio = $validated['bio'] ?? $user->bio;
            
            // Actualizar contraseña si se proporciona
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            
            // Actualizar avatar si se proporciona
            if ($request->hasFile('avatar')) {
                // Eliminar avatar anterior si existe
                if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                }
                
                // Guardar nuevo avatar
                $file = $request->file('avatar');
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('avatars', $filename, 'public');
                $user->avatar = $filename;
            }
            
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'avatar_url' => $user->avatar ? route('storage.serve', ['path' => 'avatars/' . $user->avatar]) : null
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar el dashboard del contador
     * Solo muestra cotizaciones PENDIENTES (estado ENVIADA_CONTADOR)
     * Excluye borradores
     */
    public function index(): View
    {
        // Obtener SOLO cotizaciones pendientes por aprobar (ENVIADA_CONTADOR)
        // Excluir borradores (es_borrador = 0 o false)
        $cotizaciones = Cotizacion::with('cliente', 'usuario')
            ->where('estado', 'ENVIADA_CONTADOR')
            ->where('es_borrador', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Obtener cotizaciones en corrección (Cotizaciones por Corregir y a Revisar)
        $cotizacionesPorCorregir = Cotizacion::with('cliente', 'usuario')
            ->where('estado', 'EN_CORRECCION')
            ->where('es_borrador', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Obtener cotizaciones rechazadas (mismo que por corregir para la sección a revisar)
        $cotizacionesRechazadas = Cotizacion::with('cliente', 'usuario')
            ->where('estado', 'EN_CORRECCION')
            ->where('es_borrador', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('contador.index', compact('cotizaciones', 'cotizacionesPorCorregir', 'cotizacionesRechazadas'));
    }

    /**
     * Mostrar todas las cotizaciones EXCEPTO las pendientes (ENVIADA_CONTADOR) y borradores
     */
    public function todas(): View
    {
        // Obtener todas las cotizaciones EXCEPTO las que están en estado ENVIADA_CONTADOR
        // También excluir borradores (es_borrador = 0)
        $todasLasCotizaciones = Cotizacion::with('cliente', 'usuario')
            ->where('estado', '!=', 'ENVIADA_CONTADOR')
            ->where('es_borrador', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('contador.todas', compact('todasLasCotizaciones'));
    }

    /**
     * Mostrar cotizaciones a revisar (en corrección)
     */
    public function porRevisar(): View
    {
        // Obtener cotizaciones en corrección
        $cotizacionesParaRevisar = Cotizacion::with('cliente', 'usuario')
            ->where('estado', 'EN_CORRECCION')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('contador.por-revisar', compact('cotizacionesParaRevisar'));
    }

    /**
     * Obtener detalle de una cotización para el modal
     * Devuelve toda la información completa de la cotización y sus prendas + logo si existe
     */
    public function getCotizacionDetail($id)
    {
        try {
            // Obtener la cotización con TODAS sus relaciones anidadas
            $cotizacionModelo = Cotizacion::with([
                'cliente',
                'asesor',
                'prendas' => function($query) {
                    $query->with([
                        'fotos',
                        'telas',
                        'telas.color',
                        'telas.tela',
                        'telaFotos',
                        'tallas',
                        'variantes' => function($q) {
                            $q->with(['manga', 'broche']);
                        }
                    ]);
                },
                'logoCotizacion' => function($query) {
                    $query->with('fotos');
                }
            ])->findOrFail($id);

            \Log::info('getCotizacionDetail - Cotización ID: ' . $id);
            \Log::info('getCotizacionDetail - Prendas encontradas: ' . $cotizacionModelo->prendas->count());

            // Preparar datos de la cotización
            $datos = [
                'cotizacion' => [
                    'id' => $cotizacionModelo->id,
                    'numero_cotizacion' => $cotizacionModelo->numero_cotizacion,
                    'asesora_nombre' => $cotizacionModelo->asesor ? $cotizacionModelo->asesor->name : 'N/A',
                    'empresa' => $cotizacionModelo->empresa_solicitante ?? 'N/A',
                    'nombre_cliente' => $cotizacionModelo->cliente ? $cotizacionModelo->cliente->nombre : 'N/A',
                    'created_at' => $cotizacionModelo->created_at,
                    'estado' => $cotizacionModelo->estado,
                    'tipo_venta' => $cotizacionModelo->tipo_venta ?? 'N/A',
                    'especificaciones' => $cotizacionModelo->especificaciones ?? [],
                ],
                'prendas_cotizaciones' => $cotizacionModelo->prendas->map(function($prenda, $index) {
                    // Generar descripción formateada usando el método del modelo
                    $descripcionFormateada = $prenda->generarDescripcionDetallada($index + 1);
                    
                    return [
                        'id' => $prenda->id,
                        'nombre_prenda' => $prenda->nombre_producto ?? 'Prenda sin nombre',
                        'cantidad' => $prenda->cantidad ?? 0,
                        'descripcion' => $prenda->descripcion ?? null,
                        'descripcion_formateada' => $descripcionFormateada,
                        'detalles_proceso' => $prenda->descripcion ?? null,
                        // Fotos de la prenda - URLs completas - excluir logos
                        'fotos' => $prenda->fotos ? $prenda->fotos
                            ->filter(function($foto) {
                                // Excluir fotos que contengan 'logo' o 'logos' en la ruta
                                $ruta = $foto->ruta_webp ?? $foto->ruta_original ?? '';
                                return !str_contains(strtolower($ruta), 'logo');
                            })
                            ->map(function($foto) {
                                return $foto->url; // Usar el accessor del modelo
                            })
                            ->values()
                            ->toArray() : [],
                        // Telas asociadas - URLs de imagen
                        'telas' => $prenda->telas ? $prenda->telas->map(function($tela) {
                            return [
                                'id' => $tela->id,
                                'color' => $tela->color ?? null,
                                'nombre_tela' => $tela->tela->nombre ?? null,
                                'referencia' => $tela->tela->referencia ?? null,
                                'url_imagen' => $tela->url_imagen ?? '', // Usar directamente el campo
                            ];
                        })->toArray() : [],
                        // Fotos de telas - URLs completas
                        'tela_fotos' => $prenda->telaFotos ? $prenda->telaFotos->map(function($foto) {
                            return $foto->url; // Usar el accessor del modelo
                        })->toArray() : [],
                        // Tallas
                        'tallas' => $prenda->tallas ? $prenda->tallas->map(function($talla) {
                            return [
                                'id' => $talla->id,
                                'talla' => $talla->talla,
                                'cantidad' => $talla->cantidad,
                            ];
                        })->toArray() : [],
                        // Texto personalizado de tallas
                        'texto_personalizado_tallas' => $prenda->texto_personalizado_tallas ?? null,
                        // Variantes
                        'variantes' => $prenda->variantes ? $prenda->variantes->map(function($variante) {
                            return [
                                'id' => $variante->id,
                                'tipo_prenda' => $variante->tipo_prenda ?? null,
                                'es_jean_pantalon' => $variante->es_jean_pantalon ?? null,
                                'tipo_jean_pantalon' => $variante->tipo_jean_pantalon ?? null,
                                'genero_id' => $variante->genero_id ?? null,
                                'color' => $variante->color ?? null,
                                'tiene_bolsillos' => $variante->tiene_bolsillos ?? null,
                                'aplica_manga' => $variante->aplica_manga ?? null,
                                'tipo_manga' => $variante->tipo_manga ?? null,
                                'aplica_broche' => $variante->aplica_broche ?? null,
                                'tipo_broche_id' => $variante->tipo_broche_id ?? null,
                                'tiene_reflectivo' => $variante->tiene_reflectivo ?? null,
                                'descripcion_adicional' => $variante->descripcion_adicional ?? null,
                            ];
                        })->toArray() : [],
                    ];
                })->toArray(),
            ];

            // Agregar datos del logo si existe
            $logoCotizacion = null;
            if ($cotizacionModelo->logoCotizacion) {
                // Fotos desde relación (logo_fotos_cot)
                $logoFotos = $cotizacionModelo->logoCotizacion->fotos ? $cotizacionModelo->logoCotizacion->fotos->map(function($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url,
                        'orden' => $foto->orden,
                    ];
                })->toArray() : [];

                // Fallback: campo imágenes (array de rutas) para cotizaciones guardadas en ese formato
                if (empty($logoFotos) && !empty($cotizacionModelo->logoCotizacion->imagenes)) {
                    $logoFotos = collect($cotizacionModelo->logoCotizacion->imagenes)
                        ->filter()
                        ->values()
                        ->map(function($ruta, $idx) {
                            $url = $ruta;
                            if (is_string($ruta) && !str_starts_with($ruta, 'http')) {
                                $url = str_starts_with($ruta, '/storage/') ? $ruta : '/storage/' . ltrim($ruta, '/');
                            }
                            return [
                                'id' => null,
                                'url' => $url,
                                'orden' => $idx + 1,
                            ];
                        })
                        ->toArray();
                }

                $logoCotizacion = [
                    'id' => $cotizacionModelo->logoCotizacion->id,
                    'descripcion' => $cotizacionModelo->logoCotizacion->descripcion ?? null,
                    'tecnicas' => $cotizacionModelo->logoCotizacion->tecnicas ?? [],
                    'secciones' => $cotizacionModelo->logoCotizacion->secciones ?? [],
                    'observaciones_tecnicas' => $cotizacionModelo->logoCotizacion->observaciones_tecnicas ?? null,
                    'observaciones_generales' => $cotizacionModelo->logoCotizacion->observaciones_generales ?? [],
                    'fotos' => $logoFotos,
                ];
            }

            $datos['logo_cotizacion'] = $logoCotizacion;
            $datos['tiene_logo'] = !is_null($logoCotizacion);
            $datos['tiene_prendas'] = count($datos['prendas_cotizaciones']) > 0;

            return response()->json($datos);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('getCotizacionDetail: Cotización no encontrada', [
                'cotizacion_id' => $id,
            ]);
            return response()->json(['error' => 'Cotización no encontrada'], 404);
        } catch (\Exception $e) {
            \Log::error('Error en getCotizacionDetail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una cotización completa con todas sus relaciones e imágenes
     */
    public function deleteCotizacion($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            \Log::info('🗑️ Iniciando eliminación de cotización', [
                'cotizacion_id' => $id,
                'cliente' => $cotizacion->cliente
            ]);
            
            // 1. Eliminar prendas relacionadas (prendasCotizaciones)
            if ($cotizacion->prendasCotizaciones()->exists()) {
                \Log::info('Eliminando prendas relacionadas', [
                    'cantidad' => $cotizacion->prendasCotizaciones()->count()
                ]);
                $cotizacion->prendasCotizaciones()->delete();
            }
            
            // 2. Eliminar logo/LOGO relacionado (logoCotizacion)
            if ($cotizacion->logoCotizacion()->exists()) {
                \Log::info('Eliminando logoCotizacion');
                $cotizacion->logoCotizacion()->delete();
            }
            
            // 3. Eliminar pedidos de producción relacionados (si existen)
            if ($cotizacion->pedidosProduccion()->exists()) {
                \Log::info('Eliminando pedidos de producción');
                $cotizacion->pedidosProduccion()->delete();
            }
            
            // 4. Eliminar historial de cambios relacionado (si existe)
            if ($cotizacion->historial()->exists()) {
                \Log::info('Eliminando historial de cambios', [
                    'cantidad' => $cotizacion->historial()->count()
                ]);
                $cotizacion->historial()->delete();
            }
            
            // 5. Eliminar carpeta completa de imágenes de la cotización
            \Log::info('Eliminando carpeta de imágenes', [
                'cotizacion_id' => $id,
                'ruta' => "cotizaciones/{$id}"
            ]);
            
            $imagenService = new ImagenCotizacionService();
            $imagenService->eliminarTodasLasImagenes($id);
            
            // Verificar que la carpeta se eliminó
            if (Storage::disk('public')->exists("cotizaciones/{$id}")) {
                \Log::warning('La carpeta aún existe después de eliminarTodasLasImagenes, intentando eliminar directamente');
                Storage::disk('public')->deleteDirectory("cotizaciones/{$id}");
            }
            
            // 6. Eliminar la cotización principal
            \Log::info('Eliminando registro de cotización de BD');
            $cotizacion->delete();
            
            \Log::info(' Cotización eliminada completamente', [
                'cotizacion_id' => $id,
                'cliente' => $cotizacion->cliente
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización, imágenes y todos sus registros relacionados han sido eliminados correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error(' Error al eliminar cotización', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar notas de tallas para una prenda
     */
    public function guardarNotasTallas($prendaId, Request $request)
    {
        try {
            $prenda = PrendaCotizacionFriendly::findOrFail($prendaId);
            
            // Validar que se envíe el texto de notas
            $request->validate([
                'notas' => 'required|string'
            ]);
            
            // Guardar las notas
            $prenda->notas_tallas = $request->input('notas');
            $prenda->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Notas de tallas guardadas correctamente',
                'notas' => $prenda->notas_tallas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las notas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar texto personalizado de tallas para una prenda (módulo contador)
     */
    public function guardarTextoPersonalizadoTallas($prendaId, Request $request)
    {
        try {
            $prenda = \App\Models\PrendaCot::findOrFail($prendaId);
            
            // Validar que se envíe el texto personalizado
            $request->validate([
                'texto_personalizado' => 'nullable|string'
            ]);
            
            // Guardar el texto personalizado
            $prenda->texto_personalizado_tallas = $request->input('texto_personalizado');
            $prenda->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Texto personalizado de tallas guardado correctamente',
                'texto_personalizado' => $prenda->texto_personalizado_tallas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el texto personalizado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar el estado de una cotización
     */
    public function cambiarEstado($id, Request $request)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            // Validar que el estado sea uno de los permitidos
            $request->validate([
                'estado' => 'required|in:ENVIADA_CONTADOR,APROBADA_COTIZACIONES,FINALIZADA'
            ]);
            
            // Guardar estado anterior
            $estadoAnterior = $cotizacion->estado;
            
            // Actualizar el estado
            $cotizacion->estado = $request->input('estado');
            $cotizacion->save();
            
            // Disparar evento de broadcast en tiempo real
            broadcast(new CotizacionEstadoCambiado(
                $cotizacion->id,
                $cotizacion->estado,
                $estadoAnterior,
                $cotizacion->asesor_id,
                $cotizacion->toArray()
            ))->toOthers();
            
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'estado' => $cotizacion->estado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener costos de prendas de una cotización
     */
    public function obtenerCostos($id)
    {
        try {
            $cotizacion = Cotizacion::with(['prendas.fotos', 'prendas.telaFotos'])->findOrFail($id);
            
            // Obtener costos de la cotización desde la tabla costos_prendas con la relación prenda
            $costosCotizacion = CostoPrenda::with('prenda.fotos', 'prenda.telaFotos')
                ->where('cotizacion_id', $id)
                ->get();
            
            if ($costosCotizacion->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'prendas' => []
                ]);
            }
            
            // Obtener productos de la cotización
            $cotizacionProductos = [];
            if ($cotizacion->productos) {
                $cotizacionProductos = is_string($cotizacion->productos) 
                    ? json_decode($cotizacion->productos, true) 
                    : $cotizacion->productos;
            }
            
            // Construir array de prendas con costos
            $prendas = [];
            foreach ($costosCotizacion as $costoPrenda) {
                // Obtener items de costos (estructura: [{item: "", precio: ""}])
                $items = [];
                $costoTotal = $costoPrenda->total_costo ?? 0;
                
                if ($costoPrenda->items) {
                    $itemsArray = is_string($costoPrenda->items) 
                        ? json_decode($costoPrenda->items, true) 
                        : $costoPrenda->items;
                    
                    if (is_array($itemsArray)) {
                        $items = $itemsArray;
                    }
                }
                
                // Obtener la prenda usando la relación directa
                $prenda = $costoPrenda->prenda;
                
                // Si no encuentra prenda, crear una estructura mínima
                if (!$prenda) {
                    // Determinar nombre: usar nombre_prenda, o extraer de descripción, o usar genérico
                    $nombreFinal = $costoPrenda->nombre_prenda;
                    if (empty($nombreFinal)) {
                        // Intentar extraer nombre de la descripción (primera palabra o hasta el primer espacio)
                        $descripcion = $costoPrenda->descripcion ?? '';
                        if (!empty($descripcion)) {
                            $palabras = explode(' ', trim($descripcion));
                            $nombreFinal = $palabras[0];
                        } else {
                            $nombreFinal = 'Prenda ' . (count($prendas) + 1);
                        }
                    }
                    
                    $prendas[] = [
                        'id' => null,
                        'nombre_producto' => $nombreFinal,
                        'descripcion' => $costoPrenda->descripcion ?? '',
                        'color' => '',
                        'tela' => '',
                        'tela_referencia' => '',
                        'manga_nombre' => '',
                        'costo_total' => $costoTotal,
                        'items' => $items,
                        'fotos' => []
                    ];
                    continue;
                }
                
                $productoIndex = $cotizacion->prendas->pluck('id')->search($prenda->id) ?? 0;
                
                // Obtener información de variantes
                $color = '';
                $tela = '';
                $tela_referencia = '';
                $manga_nombre = '';
                $descripcion = $costoPrenda->descripcion ?? '';
                
                if (!empty($cotizacionProductos) && isset($cotizacionProductos[$productoIndex])) {
                    $producto = $cotizacionProductos[$productoIndex];
                    $variantes = $producto['variantes'] ?? [];
                    
                    $color = $variantes['color'] ?? '';
                    $tela = $variantes['tela'] ?? '';
                    $tela_referencia = $variantes['tela_referencia'] ?? '';
                    $manga_nombre = $variantes['manga_nombre'] ?? '';
                    
                    // Construir descripción formateada usando DescripcionPrendaHelper
                    $datosFormato = [
                        'numero' => 1,
                        'tipo' => $prenda->nombre_producto ?? '',
                        'color' => $color,
                        'tela' => $tela,
                        'ref' => $tela_referencia,
                        'manga' => $manga_nombre,
                        'obs_manga' => '',
                        'logo' => '',
                        'bolsillos' => [],
                        'broche' => '',
                        'reflectivos' => [],
                        'otros' => [],
                        'tallas' => []
                    ];
                    
                    // Extraer datos de la descripción de la prenda
                    if ($prenda->descripcion) {
                        $desc = $prenda->descripcion;
                        
                        // Extraer Logo
                        if (preg_match('/Logo:\s*(.+?)(?:Bolsillos?:|Reflectivo?s?:|Otros:|$)/is', $desc, $matches)) {
                            $logoText = trim($matches[1]);
                            $logoText = preg_replace('/^(SI|NO)\s*-\s*/i', '', $logoText);
                            if ($logoText) {
                                $datosFormato['logo'] = trim($logoText);
                            }
                        }
                        
                        // Extraer Bolsillos
                        if (preg_match('/Bolsillos?:\s*(.+?)(?:Reflectivo?s?:|Otros:|Broche:|$)/is', $desc, $matches)) {
                            $bolsillosText = trim($matches[1]);
                            $datosFormato['bolsillos'] = array_filter(array_map('trim', preg_split('/[•\-\n]/', $bolsillosText)));
                        }
                        
                        // Extraer Broche
                        if (preg_match('/Broche:\s*(.+?)(?:Reflectivo?s?:|Otros:|Bolsillos?:|$)/is', $desc, $matches)) {
                            $brocheText = trim($matches[1]);
                            $brocheText = str_replace('|', '', $brocheText);
                            $datosFormato['broche'] = trim($brocheText);
                        }
                        
                        // Extraer Reflectivos
                        if (preg_match('/Reflectivo?s?:\s*(.+?)(?:Otros:|Bolsillos?:|Broche:|$)/is', $desc, $matches)) {
                            $reflectivosText = trim($matches[1]);
                            $datosFormato['reflectivos'] = array_filter(array_map('trim', preg_split('/[•\-\n]/', $reflectivosText)));
                        }
                        
                        // Extraer Otros detalles
                        if (preg_match('/Otros\s+detalles?:\s*(.+?)(?:Bolsillos?:|Reflectivo?s?:|Broche:|$)/is', $desc, $matches)) {
                            $otrosText = trim($matches[1]);
                            $datosFormato['otros'] = array_filter(array_map('trim', preg_split('/[•\-\n]/', $otrosText)));
                        }
                    }
                    
                    // Generar descripción formateada
                    $descripcion = DescripcionPrendaHelper::generarDescripcion($datosFormato);
                }
                
                // Obtener fotos de la prenda (excluir fotos de logos)
                $fotos = [];
                if ($prenda->fotos && $prenda->fotos->count() > 0) {
                    $fotos = $prenda->fotos
                        ->filter(function($foto) {
                            // Excluir fotos que contengan 'logo' o 'logos' en la ruta
                            $ruta = $foto->ruta_webp ?? $foto->ruta_original ?? '';
                            return !str_contains(strtolower($ruta), 'logo');
                        })
                        ->map(function($foto) {
                            return $foto->url; // Usar el accessor del modelo
                        })
                        ->values()
                        ->toArray();
                }
                
                // Obtener fotos de las telas
                $telaFotos = [];
                if ($prenda->telaFotos && $prenda->telaFotos->count() > 0) {
                    $telaFotos = $prenda->telaFotos->map(function($foto) {
                        return $foto->url; // Usar el accessor del modelo
                    })->toArray();
                }
                
                $prendas[] = [
                    'id' => $prenda->id,
                    'nombre_producto' => $prenda->nombre_producto ?: $costoPrenda->nombre_prenda,
                    'descripcion' => $descripcion,
                    'color' => $color,
                    'tela' => $tela,
                    'tela_referencia' => $tela_referencia,
                    'manga_nombre' => $manga_nombre,
                    'costo_total' => $costoTotal,
                    'items' => $items,
                    'fotos' => $fotos,
                    'tela_fotos' => $telaFotos
                ];
            }
            
            return response()->json([
                'success' => true,
                'prendas' => $prendas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener costos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener notificaciones del contador
     */
    public function getNotifications()
    {
        try {
            $user = Auth::user();
            
            // Obtener IDs de cotizaciones ya vistas por el usuario
            $viewedCotizationIds = session('viewed_cotizations_' . $user->id, []);
            
            // Cotizaciones enviadas a revisar (estado ENVIADA_CONTADOR)
            $cotizacionesParaRevisar = Cotizacion::with('cliente')
                ->where('estado', 'ENVIADA_CONTADOR')
                ->whereNotIn('id', $viewedCotizationIds)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            // Nuevas cotizaciones creadas (últimas 5 de las últimas 24 horas)
            $nuevasCotizaciones = Cotizacion::with('cliente')
                ->where('created_at', '>=', now()->subHours(24))
                ->whereNotIn('estado', ['ENVIADA_CONTADOR'])
                ->whereNotIn('id', $viewedCotizationIds)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            // Contar total de notificaciones
            $totalNotificaciones = $cotizacionesParaRevisar->count() + $nuevasCotizaciones->count();
            
            // Transformar respuesta para retornar solo nombre del cliente
            $paraRevisarTransformada = $cotizacionesParaRevisar->map(function($cot) {
                return [
                    'id' => $cot->id,
                    'cliente' => $cot->cliente ? $cot->cliente->nombre : 'Sin cliente',
                    'created_at' => $cot->created_at
                ];
            });
            
            $nuevasTransformada = $nuevasCotizaciones->map(function($cot) {
                return [
                    'id' => $cot->id,
                    'cliente' => $cot->cliente ? $cot->cliente->nombre : 'Sin cliente',
                    'created_at' => $cot->created_at
                ];
            });
            
            return response()->json([
                'cotizaciones_para_revisar' => $paraRevisarTransformada,
                'nuevas_cotizaciones' => $nuevasTransformada,
                'total_notificaciones' => $totalNotificaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener notificaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllNotificationsAsRead()
    {
        try {
            $user = Auth::user();
            
            // Obtener todas las cotizaciones que generan notificaciones
            $cotizacionesParaRevisar = Cotizacion::where('estado', 'ENVIADA_CONTADOR')
                ->pluck('id')
                ->toArray();
            
            $nuevasCotizaciones = Cotizacion::where('created_at', '>=', now()->subHours(24))
                ->whereNotIn('estado', ['ENVIADA_CONTADOR'])
                ->pluck('id')
                ->toArray();
            
            // Combinar todos los IDs de cotizaciones a marcar como vistas
            $allCotizationIds = array_merge($cotizacionesParaRevisar, $nuevasCotizaciones);
            
            // Guardar en sesión del usuario
            session(['viewed_cotizations_' . $user->id => $allCotizationIds]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notificaciones marcadas como leídas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones como leídas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener contador de cotizaciones pendientes (ENVIADA_CONTADOR)
     * Endpoint: GET /contador/cotizaciones-pendientes-count
     */
    public function cotizacionesPendientesCount()
    {
        try {
            $count = Cotizacion::where('estado', 'ENVIADA_CONTADOR')->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count > 0 ? "Hay $count cotización(es) pendiente(s) por revisar" : 'No hay cotizaciones pendientes'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener contador de cotizaciones pendientes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar cotizaciones aprobadas por el aprobador (APROBADA_POR_APROBADOR)
     */
    public function aprobadas(): View
    {
        // Obtener cotizaciones aprobadas por el aprobador de cotizaciones
        $cotizacionesAprobadas = Cotizacion::with('cliente', 'usuario')
            ->where('estado', 'APROBADA_POR_APROBADOR')
            ->where('es_borrador', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('contador.aprobadas', compact('cotizacionesAprobadas'));
    }

}
