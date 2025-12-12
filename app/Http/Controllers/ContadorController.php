<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use App\Services\ImagenCotizacionService;
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
     */
    public function getCotizacionDetail($id)
    {
        try {
            $cotizacion = Cotizacion::with([
                'prendasCotizaciones',
                'logoCotizacion',
                'cliente',
                'prendas' => function($query) {
                    $query->with([
                        'fotos',
                        'telas' => function($q) {
                            $q->with(['tela', 'color']);
                        },
                        'telaFotos',
                        'tallas',
                        'variantes' => function($v) {
                            $v->with(['genero', 'manga', 'broche']);
                        }
                    ]);
                }
            ])->findOrFail($id);
            
            // Construir HTML manualmente para evitar errores de vista
            $html = '<div class="cotizacion-detail" style="padding: 1.5rem; max-height: 80vh; overflow-y: auto;">';
            
            // Prendas (estructura DDD)
            if ($cotizacion->prendas && count($cotizacion->prendas) > 0) {
                \Log::info('=== getCotizacionDetail - Prendas encontradas: ' . count($cotizacion->prendas));
                $html .= '<h4 style="color: #1e5ba8; margin-top: 1.5rem; margin-bottom: 1rem;">📦 Prendas</h4>';
                
                foreach ($cotizacion->prendas as $index => $prenda) {
                    \Log::info('Prenda ' . $index . ': ' . ($prenda->nombre_producto ?? 'N/A'));
                    \Log::info('  - ID Prenda: ' . $prenda->id);
                    \Log::info('  - Telas cargadas: ' . count($prenda->telas ?? []));
                    if ($prenda->telas && count($prenda->telas) > 0) {
                        foreach ($prenda->telas as $t) {
                            \Log::info('    - Tela ID: ' . $t->id . ', telaPrenda: ' . ($t->telaPrenda ? 'Sí' : 'No'));
                        }
                    }
                    \Log::info('  - Tela Fotos: ' . count($prenda->telaFotos ?? []));
                    \Log::info('  - Fotos: ' . count($prenda->fotos ?? []));
                    
                    $html .= '<div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #2b7ec9;">';
                    
                    // Nombre
                    $html .= '<h5 style="margin: 0 0 0.5rem 0; color: #1e40af;">' . htmlspecialchars($prenda->nombre_producto ?? 'Prenda sin nombre') . '</h5>';
                    
                    // Descripción
                    if ($prenda->descripcion) {
                        $html .= '<p style="margin: 0.25rem 0; color: #333; font-size: 0.9rem;">' . htmlspecialchars($prenda->descripcion) . '</p>';
                    }
                    
                    // Cantidad
                    if ($prenda->cantidad) {
                        $html .= '<p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;"><strong>Cantidad:</strong> ' . htmlspecialchars($prenda->cantidad) . '</p>';
                    }
                    
                    // Fotos de prenda
                    if ($prenda->fotos && count($prenda->fotos) > 0) {
                        $html .= '<div style="margin-top: 0.75rem;">';
                        $html .= '<p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; font-weight: 600;">Imágenes de Prenda:</p>';
                        $html .= '<div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">';
                        foreach ($prenda->fotos as $foto) {
                            if ($foto && $foto->ruta_webp) {
                                $html .= '<img src="' . htmlspecialchars($foto->ruta_webp) . '" alt="Prenda" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
                            }
                        }
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    
                    // Detalles de la Prenda - Extraer telas y variaciones de variantes
                    $detallesMostrados = false;
                    if ($prenda->variantes && count($prenda->variantes) > 0) {
                        foreach ($prenda->variantes as $variante) {
                            // Inicializar sección de detalles si no está abierta
                            if (!$detallesMostrados) {
                                $html .= '<div style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #2b7ec9;">';
                                $html .= '<div style="font-weight: 700; color: #1e5ba8; margin-bottom: 0.75rem; font-size: 0.9rem;">📋 Detalles de la Prenda:</div>';
                                $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">';
                                $detallesMostrados = true;
                            }
                            
                            // Género - Mostrar nombre
                            if ($variante->genero && $variante->genero->nombre) {
                                $html .= '<div>';
                                $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">Género:</div>';
                                $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">' . htmlspecialchars($variante->genero->nombre) . '</div>';
                                $html .= '</div>';
                            }
                            
                            // Manga - Mostrar nombre y observación
                            if ($variante->manga && $variante->manga->nombre) {
                                $html .= '<div>';
                                $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">Manga:</div>';
                                $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">' . htmlspecialchars($variante->manga->nombre);
                                if (!empty($variante->obs_manga)) {
                                    $html .= ' - ' . htmlspecialchars($variante->obs_manga);
                                }
                                $html .= '</div>';
                                $html .= '</div>';
                            }
                            
                            // Broche/Botón - Dinámico según tipo elegido
                            if ($variante->broche && $variante->broche->nombre) {
                                $html .= '<div>';
                                $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">' . htmlspecialchars($variante->broche->nombre) . ':</div>';
                                $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">';
                                if (!empty($variante->obs_broche)) {
                                    $html .= htmlspecialchars($variante->obs_broche);
                                } else {
                                    $html .= 'Sí';
                                }
                                $html .= '</div>';
                                $html .= '</div>';
                            }
                            
                            // Observación Bolsillos
                            if (!empty($variante->obs_bolsillos)) {
                                $html .= '<div>';
                                $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">Bolsillos:</div>';
                                $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">' . htmlspecialchars($variante->obs_bolsillos) . '</div>';
                                $html .= '</div>';
                            }
                            
                            // Observación Reflectivo
                            if (!empty($variante->obs_reflectivo)) {
                                $html .= '<div>';
                                $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">Reflectivo:</div>';
                                $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">' . htmlspecialchars($variante->obs_reflectivo) . '</div>';
                                $html .= '</div>';
                            }
                            
                            // Telas Múltiples
                            $telasMultiples = $variante->telas_multiples ?? null;
                            
                            // Decodificar JSON si es string
                            if (is_string($telasMultiples)) {
                                $telasMultiples = json_decode($telasMultiples, true) ?? [];
                            }
                            
                            if (!empty($telasMultiples) && is_array($telasMultiples)) {
                                foreach ($telasMultiples as $telaIndex => $telaData) {
                                    \Log::info('    Tela ' . $telaIndex . ':');
                                    \Log::info('      - tela: ' . ($telaData['tela'] ?? 'N/A'));
                                    \Log::info('      - color: ' . ($telaData['color'] ?? 'N/A'));
                                    \Log::info('      - referencia: ' . ($telaData['referencia'] ?? 'N/A'));
                                    
                                    // Nombre de la tela
                                    if (!empty($telaData['tela'])) {
                                        $html .= '<div>';
                                        $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">Tela:</div>';
                                        $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">' . htmlspecialchars($telaData['tela']) . '</div>';
                                        $html .= '</div>';
                                    }
                                    
                                    // Color
                                    if (!empty($telaData['color'])) {
                                        $html .= '<div>';
                                        $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">Color:</div>';
                                        $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">' . htmlspecialchars($telaData['color']) . '</div>';
                                        $html .= '</div>';
                                    }
                                    
                                    // Referencia
                                    if (!empty($telaData['referencia'])) {
                                        $html .= '<div>';
                                        $html .= '<div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">Referencia:</div>';
                                        $html .= '<div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">' . htmlspecialchars($telaData['referencia']) . '</div>';
                                        $html .= '</div>';
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($detallesMostrados) {
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    
                    // Fotos de tela
                    \Log::info('  Tela Fotos: ' . count($prenda->telaFotos ?? []));
                    if ($prenda->telaFotos && count($prenda->telaFotos) > 0) {
                        $html .= '<div style="margin-top: 0.75rem;">';
                        $html .= '<p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; font-weight: 600;">📸 Imágenes de Tela:</p>';
                        $html .= '<div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">';
                        foreach ($prenda->telaFotos as $index => $telaFoto) {
                            \Log::info('    Tela Foto ' . $index . ': ' . ($telaFoto->ruta_webp ?? 'sin ruta'));
                            if ($telaFoto && $telaFoto->ruta_webp) {
                                $html .= '<img src="' . htmlspecialchars($telaFoto->ruta_webp) . '" alt="Tela" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
                            }
                        }
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    
                    // Tallas
                    if ($prenda->tallas && count($prenda->tallas) > 0) {
                        $html .= '<div style="margin-top: 0.75rem;">';
                        $html .= '<p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; font-weight: 600;">Tallas:</p>';
                        $tallasArray = [];
                        foreach ($prenda->tallas as $talla) {
                            $tallasArray[] = htmlspecialchars($talla->talla);
                        }
                        $html .= '<p style="margin: 0; color: #e74c3c; font-weight: bold; font-size: 0.9rem;">👕 ' . implode(', ', $tallasArray) . '</p>';
                        $html .= '</div>';
                    }
                    
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
            
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getCotizacionDetail', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            
            \Log::info('✅ Cotización eliminada completamente', [
                'cotizacion_id' => $id,
                'cliente' => $cotizacion->cliente
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización, imágenes y todos sus registros relacionados han sido eliminados correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error al eliminar cotización', [
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
            
            // Actualizar el estado
            $cotizacion->estado = $request->input('estado');
            $cotizacion->save();
            
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
            $cotizacion = Cotizacion::with('prendasCotizaciones')->findOrFail($id);
            
            // Obtener costos de la cotización desde la tabla costos_prendas
            $costosCotizacion = \DB::table('costos_prendas')
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
                // Obtener la prenda correspondiente buscando por nombre
                $prenda = $cotizacion->prendasCotizaciones()
                    ->where('nombre_producto', $costoPrenda->nombre_prenda)
                    ->orWhere('nombre_producto', 'LIKE', '%' . $costoPrenda->nombre_prenda . '%')
                    ->first();
                
                if (!$prenda) {
                    // Si no encuentra por nombre, usar la primera prenda disponible
                    $prenda = $cotizacion->prendasCotizaciones()->first();
                    if (!$prenda) {
                        continue;
                    }
                }
                
                $productoIndex = $cotizacion->prendasCotizaciones()->pluck('id')->search($prenda->id) ?? 0;
                
                // Obtener información de variantes
                $color = '';
                $tela = '';
                $tela_referencia = '';
                $manga_nombre = '';
                $descripcion = '';
                
                if (!empty($cotizacionProductos) && isset($cotizacionProductos[$productoIndex])) {
                    $producto = $cotizacionProductos[$productoIndex];
                    $variantes = $producto['variantes'] ?? [];
                    
                    $color = $variantes['color'] ?? '';
                    $tela = $variantes['tela'] ?? '';
                    $tela_referencia = $variantes['tela_referencia'] ?? '';
                    $manga_nombre = $variantes['manga_nombre'] ?? '';
                    
                    // Construir descripción con especificaciones
                    $descripcionBase = $prenda->descripcion ?? '';
                    $especificaciones = $variantes['descripcion_adicional'] ?? '';
                    $descripcion = $descripcionBase;
                    if ($especificaciones) {
                        $descripcion .= ' | ' . $especificaciones;
                    }
                }
                
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
                
                // Obtener fotos de la prenda
                $fotos = [];
                if ($prenda->fotos) {
                    $fotosArray = is_string($prenda->fotos) 
                        ? json_decode($prenda->fotos, true) 
                        : $prenda->fotos;
                    
                    if (is_array($fotosArray)) {
                        $fotos = $fotosArray;
                    }
                }
                
                $prendas[] = [
                    'id' => $prenda->id,
                    'nombre_producto' => $prenda->nombre_producto,
                    'descripcion' => $descripcion,
                    'color' => $color,
                    'tela' => $tela,
                    'tela_referencia' => $tela_referencia,
                    'manga_nombre' => $manga_nombre,
                    'costo_total' => $costoTotal,
                    'items' => $items,
                    'fotos' => $fotos
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

}
