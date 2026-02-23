<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\NumeroSecuencia;
use App\Events\CotizacionCreada;
use App\Services\CotizacionEstadoService;
use Intervention\Image\ImageManager;
use App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController;
use Intervention\Image\Drivers\Gd\Driver;
use App\Services\TecnicaImagenService;

class CotizacionBordadoController extends Controller
{
    public function __construct(
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService
    ) {
    }

    /**
     * Mostrar formulario de crear cotización de bordado
     */
    public function create(Request $request)
    {
        $cotizacion = null;

        // Si hay parámetro editar, cargar datos del borrador
        if ($request->has('editar')) {
            $id = $request->input('editar');
            $cotizacion = Cotizacion::with([
                'cliente',
                'logoCotizacion',
                'logoCotizacion.fotos',
                'logoCotizacion.prendas.tipoLogo',
                'logoCotizacion.prendas.prendaCot.fotos',
                'logoCotizacion.prendas.fotos'
            ])->findOrFail($id);

            // Verificar que sea un borrador y del asesor autenticado
            $allowEditarCotizacionCreada = $request->boolean('editar_cotizacion');
            if ($cotizacion->asesor_id !== Auth::id()) {
                abort(403, 'No tienes permiso para editar esta cotización');
            }

            if (!$allowEditarCotizacionCreada && $cotizacion->es_borrador !== true) {
                abort(403, 'No tienes permiso para editar este borrador');
            }

            Log::info('📥 Cargando borrador para edición', [
                'cotizacion_id' => $id,
                'cliente_id' => $cotizacion->cliente_id,
                'cliente_nombre' => $cotizacion->cliente ? $cotizacion->cliente->nombre : 'NULL',
                'tiene_cliente' => $cotizacion->cliente ? 'SI' : 'NO',
                'tiene_logo_cotizacion' => $cotizacion->logoCotizacion ? 'SI' : 'NO',
                'logo_prendas_count' => $cotizacion->logoCotizacion && $cotizacion->logoCotizacion->relationLoaded('prendas')
                    ? $cotizacion->logoCotizacion->prendas->count()
                    : null,
                'logo_prendas_loaded' => $cotizacion->logoCotizacion
                    ? ($cotizacion->logoCotizacion->relationLoaded('prendas') ? 'SI' : 'NO')
                    : 'NO_LOGO',
            ]);
        } else {
            //  NO CREAR COTIZACIÓN AUTOMÁTICAMENTE
            // La cotización se crea cuando el usuario hace POST (envía el formulario)
            // Esto evita crear borradores vacíos innecesarios
            Log::info(' Mostrando formulario vacío para crear nueva cotización', [
                'asesor_id' => Auth::id()
            ]);
        }

        return view('cotizaciones.bordado.create', [
            'cotizacion' => $cotizacion
        ]);
    }

    /**
     * Borrar imagen específica
     */
    public function borrarImagen(Request $request, $id)
    {
        try {
            $fotoId = $request->input('foto_id');
            
            Log::info('🗑️ Borrando imagen específica:', ['foto_id' => $fotoId, 'cotizacion_id' => $id]);
            
            // Buscar y borrar la imagen
            $foto = \App\Models\LogoFotoCot::find($fotoId);
            
            if (!$foto) {
                Log::warning(' Imagen no encontrada:', ['foto_id' => $fotoId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ], 404);
            }
            
            // Borrar la imagen
            $foto->forceDelete();
            
            Log::info(' Imagen borrada exitosamente:', ['foto_id' => $fotoId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error(' Error al borrar imagen:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al borrar imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar borrador de cotización de bordado
     */
    public function updateBorrador(Request $request, $id)
    {
        // Convertir $id a entero para asegurar consistencia
        $id = (int) $id;
        
        // Obtener IDs de imágenes a borrar ANTES de la transacción
        $imagenesABorrar = $request->input('imagenes_a_borrar', '[]');
        if (is_string($imagenesABorrar)) {
            $imagenesABorrar = json_decode($imagenesABorrar, true) ?? [];
        }
        
        Log::info('🗑️ Imágenes a borrar (explícitamente):', ['ids' => $imagenesABorrar, 'count' => count($imagenesABorrar)]);
        
        // Determinar si es envío o guardado como borrador
        $action = $request->input('action') ?? $request->input('accion');
        $esEnvio = $action === 'enviar';

        $tecnicasFotosABorrar = $request->input('tecnicas_fotos_a_borrar', '[]');
        if (is_string($tecnicasFotosABorrar)) {
            $tecnicasFotosABorrar = json_decode($tecnicasFotosABorrar, true) ?? [];
        }
        
        Log::info('📤 Acción detectada:', ['action' => $action, 'es_envio' => $esEnvio]);
        
        // Ejecutar transacción para actualizar datos
        $resultado = DB::transaction(function () use ($request, $id, $esEnvio) {
            try {
                
                Log::info(' CotizacionBordadoController@updateBorrador - Actualizando borrador', [
                    'cotizacion_id' => $id,
                    'id_type' => gettype($id),
                    'method' => $request->method(),
                    'es_envio' => $esEnvio
                ]);

                // Verificar que la cotización existe y es un borrador del asesor
                $cotizacion = Cotizacion::findOrFail($id);
                $allowEditarCotizacionCreada = $request->boolean('editar_cotizacion');
                if ($cotizacion->asesor_id !== Auth::id()) {
                    abort(403, 'No tienes permiso para actualizar esta cotización');
                }

                if (!$allowEditarCotizacionCreada && $cotizacion->es_borrador !== true) {
                    abort(403, 'No tienes permiso para actualizar este borrador');
                }

                // Actualizar cliente si cambió
                $clienteId = $request->input('cliente_id');
                $nombreCliente = $request->input('cliente');

                Log::info('Cliente recibido en updateBorrador', [
                    'cliente_id' => $clienteId,
                    'nombre_cliente' => $nombreCliente,
                    'cliente_actual_id' => $cotizacion->cliente_id
                ]);

                if ($nombreCliente && !$clienteId) {
                    $cliente = Cliente::firstOrCreate(
                        ['nombre' => $nombreCliente],
                        ['nombre' => $nombreCliente]
                    );
                    $clienteId = $cliente->id;
                    Log::info('Cliente creado o encontrado', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
                }

                // Si es envío, generar número y cambiar estado
                $numeroCotizacion = null;
                if ($esEnvio) {
                    if (!empty($cotizacion->numero_cotizacion)) {
                        $numeroCotizacion = $cotizacion->numero_cotizacion;
                        Log::info(' Número existente preservado para re-envío', ['numero' => $numeroCotizacion, 'cotizacion_id' => $id]);
                    } else {
                        $usuarioId = Auth::id();
                        $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                        Log::info(' Número generado para envío', ['numero' => $numeroCotizacion, 'cotizacion_id' => $id]);
                    }
                }

                // Actualizar cotización principal
                $datosActualizar = [];
                if ($clienteId) {
                    $datosActualizar['cliente_id'] = $clienteId;
                }
                if ($esEnvio) {
                    $datosActualizar['numero_cotizacion'] = $numeroCotizacion;
                    $datosActualizar['es_borrador'] = false;
                    $datosActualizar['estado'] = 'ENVIADA_CONTADOR';
                    $datosActualizar['fecha_envio'] = now();
                }

                if (!empty($datosActualizar)) {
                    $cotizacion->update($datosActualizar);
                    Log::info(' Cotización actualizada', ['cotizacion_id' => $id, 'datos' => $datosActualizar]);
                } else {
                    Log::warning(' No se actualizó cotización - sin datos', ['cotizacion_id' => $id]);
                }

                // Actualizar o crear logo_cotizacion
                // NOTA: El campo 'imagenes' en logo_cotizaciones no se usa realmente,
                // las imágenes se almacenan en la tabla logo_fotos_cot
                
                // Procesar técnicas (pueden venir como JSON string desde FormData o como array desde JSON)
                $tecnicas = $request->input('tecnicas', '[]');
                Log::info(' Técnicas RAW recibidas:', ['tecnicas_raw' => $tecnicas, 'type' => gettype($tecnicas)]);
                
                if (is_string($tecnicas)) {
                    $tecnicas = json_decode($tecnicas, true) ?? [];
                }
                
                // Procesar secciones (pueden venir como JSON string desde FormData)
                $secciones = $request->input('secciones', '[]');
                if (is_string($secciones)) {
                    $secciones = json_decode($secciones, true) ?? [];
                }
                
                // Procesar observaciones generales (pueden venir como JSON string desde FormData)
                $observacionesGenerales = $request->input('observaciones_generales', '[]');
                if (is_string($observacionesGenerales)) {
                    $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
                }
                
                $descripcion = $request->input('descripcion', '');
                $observacionesTecnicas = $request->input('observaciones_tecnicas', '');
                
                Log::info(' Datos recibidos en updateBorrador:', [
                    'descripcion' => $descripcion,
                    'observaciones_tecnicas' => $observacionesTecnicas,
                    'tecnicas' => $tecnicas,
                    'tecnicas_type' => gettype($tecnicas),
                    'secciones' => $secciones,
                    'observaciones_generales' => $observacionesGenerales
                ]);
                
                // Preparar datos a actualizar (solo campos que existen en DB)
                $datosActualizar = [];
                
                // Observaciones generales: Actualizar con los datos proporcionados
                $datosActualizar['observaciones_generales'] = $observacionesGenerales ?? '';
                
                // Agregar tipo_venta_bordado si está disponible
                $tipoVentaBordado = $request->input('tipo_venta_bordado') ?? $request->input('tipo_venta');
                if (!empty($tipoVentaBordado)) {
                    $datosActualizar['tipo_venta'] = $tipoVentaBordado;
                }
                
                $logoCotizacion = \App\Models\LogoCotizacion::updateOrCreate(
                    ['cotizacion_id' => $id],  // Condición de búsqueda
                    $datosActualizar  // Actualizar solo campos válidos
                );
                
                Log::info(' logo_cotizaciones actualizado/creado', [
                    'cotizacion_id' => $id,
                    'logo_id' => $logoCotizacion->id,
                    'observaciones_generales' => $datosActualizar['observaciones_generales'] ?? 'NO ACTUALIZADO',
                    'tipo_venta' => $datosActualizar['tipo_venta'] ?? 'NO ACTUALIZADO',
                ]);
                
                // Recargar desde BD para verificar
                $logoCotizacionRecargado = \App\Models\LogoCotizacion::find($logoCotizacion->id);
                Log::info(' Verificación post-guardado:', [
                    'logo_id' => $logoCotizacion->id,
                    'cotizacion_id' => $id
                ]);

                // Borrar imágenes si se especificaron
                // NOTA: El borrado de imágenes se ejecuta DESPUÉS de la transacción
                // para evitar que se revierte si hay algún error
                
                // Procesar nuevas imágenes si existen
                // Las imágenes existentes en logo_fotos_cot se preservan automáticamente
                // ya que solo agregamos nuevas, no eliminamos las existentes
                // Procesar nuevas imágenes si existen, buscando en 'imagenes' y 'imagenes_bordado'
                $imagenes = $request->file('imagenes', $request->file('imagenes_bordado', []));
                if ($request->hasFile('imagenes') || $request->hasFile('imagenes_bordado')) {
                    $this->procesarImagenesCotizacion($request, $id);
                }

                // Sincronizar técnicas/prendas (Paso 3) en edición: eliminar faltantes y actualizar ubicaciones/tallas/obs
                $tecnicas = $request->input('tecnicas', '[]');
                if (is_string($tecnicas)) {
                    $tecnicas = json_decode($tecnicas, true) ?? [];
                }
                if (is_array($tecnicas)) {
                    $mapaPrendasTecnica = $this->syncTecnicasPrendasDesdeFormulario($tecnicas, (int) $logoCotizacion->id);
                    $this->adjuntarNuevasFotosTecnicasDesdeRequest($tecnicas, (int) $logoCotizacion->id, $request, $mapaPrendasTecnica);
                    if (!$esEnvio) {
                        $this->vincularLogosCompartidosTecnicasDesdeRequest($tecnicas, (int) $logoCotizacion->id, $request, $mapaPrendasTecnica);
                    }
                }

                // Recargar la cotización con todos sus datos actualizados
                // IMPORTANTE: Recargar DESPUÉS de borrar imágenes para obtener la lista actualizada
                $cotizacionActualizada = Cotizacion::with([
                    'cliente',
                    'logoCotizacion' => function ($query) {
                        $query->with(['fotos' => function ($fotosQuery) {
                            $fotosQuery->orderBy('orden');
                        }]);
                    }
                ])->findOrFail($id);

                Log::info(' Borrador de bordado actualizado', [
                    'cotizacion_id' => $id,
                    'descripcion' => $descripcion,
                    'tecnicas_count' => count($tecnicas),
                    'datos_guardados' => $cotizacionActualizada->toArray()
                ]);

                // Convertir a array y asegurar que los accessors estén incluidos
                $resultado = $cotizacionActualizada->toArray();
                
                // Asegurar que las URLs de las fotos estén correctas
                if (isset($resultado['logo_cotizacion']['fotos'])) {
                    foreach ($resultado['logo_cotizacion']['fotos'] as &$foto) {
                        // Agregar el accessor 'url' manualmente si no está
                        if (!isset($foto['url'])) {
                            $ruta = $foto['ruta_webp'] ?? $foto['ruta_original'];
                            if ($ruta && !str_starts_with($ruta, 'http') && !str_starts_with($ruta, '/storage/')) {
                                $foto['url'] = '/storage/' . ltrim($ruta, '/');
                            } else {
                                $foto['url'] = $ruta;
                            }
                        }
                    }
                }
                
                return $resultado;

            } catch (\Exception $e) {
                Log::error(' Error al actualizar borrador de bordado', [
                    'error' => $e->getMessage(),
                    'cotizacion_id' => $id
                ]);
                throw $e;
            }
        });
        
        // Si es envío, encolar el job
        if ($esEnvio) {
            \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                $id,
                2 // tipo_cotizacion_id para Logo/Bordado
            )->onQueue('cotizaciones');

            Log::info(' Job de envío encolado', [
                'cotizacion_id' => $id,
                'numero' => $resultado['numero_cotizacion'] ?? null,
                'queue' => 'cotizaciones'
            ]);

            // Broadcast realtime para que aparezca inmediatamente en el módulo Contador
            try {
                $cotizacionRealtime = Cotizacion::with(['cliente', 'asesor'])->find($id);
                if ($cotizacionRealtime) {
                    $payload = $cotizacionRealtime->toArray();
                    $payload['asesora'] = $cotizacionRealtime->asesor?->name;
                    $payload['usuario'] = [
                        'name' => $cotizacionRealtime->asesor?->name,
                    ];
                    $payload['nombre_cliente'] = $cotizacionRealtime->cliente?->nombre;

                    Log::info('[BROADCAST-BORRADOR] Emitiendo CotizacionCreada desde updateBorrador (LOGO)', [
                        'cotizacion_id' => $cotizacionRealtime->id,
                        'estado' => $cotizacionRealtime->estado,
                        'asesor_id' => $cotizacionRealtime->asesor_id,
                        'tipo_cotizacion_id' => $cotizacionRealtime->tipo_cotizacion_id,
                    ]);

                    broadcast(new CotizacionCreada(
                        $cotizacionRealtime->id,
                        $cotizacionRealtime->asesor_id,
                        $cotizacionRealtime->estado,
                        $payload
                    ));
                }
            } catch (\Exception $e) {
                Log::warning('[BROADCAST-BORRADOR] Falló broadcast desde updateBorrador (LOGO)', [
                    'cotizacion_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // DESPUÉS de la transacción, borrar imágenes
        if (!empty($imagenesABorrar)) {
            Log::info('🗑️ Borrando imágenes DESPUÉS de transacción:', ['ids' => $imagenesABorrar]);
            
            // Convertir IDs a enteros
            $idsABorrar = array_map(function($id) {
                return (int) $id;
            }, $imagenesABorrar);
            
            Log::info('🗑️ IDs a borrar (convertidos):', ['ids' => $idsABorrar]);
            
            // Verificar que existan antes de borrar
            $imagenesEnBD = DB::table('logo_fotos_cot')->whereIn('id', $idsABorrar)->get();
            Log::info(' Imágenes encontradas en BD:', ['count' => $imagenesEnBD->count(), 'ids' => $imagenesEnBD->pluck('id')->toArray()]);
            
            try {
                // Usar modelo Eloquent para borrar
                $borradas = \App\Models\LogoFotoCot::whereIn('id', $idsABorrar)->forceDelete();
                Log::info(' Imágenes borradas con forceDelete:', ['filas_borradas' => $borradas, 'ids_borrados' => $idsABorrar]);
                
                // Verificar post-borrado
                $imagenesRestantes = DB::table('logo_fotos_cot')->whereIn('id', $idsABorrar)->count();
                Log::info(' Verificación post-borrado:', ['restantes' => $imagenesRestantes]);
            } catch (\Exception $e) {
                Log::error(' Error al borrar imágenes DESPUÉS de transacción:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }

        // DESPUÉS de la transacción, borrar fotos de técnicas (Paso 3) por IDs explícitos
        if (!empty($tecnicasFotosABorrar)) {
            $ids = array_values(array_unique(array_map(fn($x) => (int) $x, (array) $tecnicasFotosABorrar)));
            $fotos = \App\Models\LogoCotizacionTecnicaPrendaFoto::whereIn('id', $ids)->get();
            foreach ($fotos as $foto) {
                $this->borrarArchivoPublicSiExiste($foto->ruta_webp);
                $this->borrarArchivoPublicSiExiste($foto->ruta_original);
                $this->borrarArchivoPublicSiExiste($foto->ruta_miniatura);
                $foto->forceDelete();
            }
        }
        
        $mensaje = $esEnvio 
            ? 'Cotización enviada - Número: ' . ($resultado['numero_cotizacion'] ?? 'N/A')
            : 'Borrador actualizado exitosamente';
        
        $redirect = route('asesores.cotizaciones.index')
            . '?'
            . http_build_query([
                'tab' => $esEnvio ? 'cotizaciones' : 'borradores',
                'highlight' => $id,
            ]);
        
        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $resultado,
            'redirect' => $redirect
        ]);
    }

    private function borrarArchivoPublicSiExiste(?string $ruta): void
    {
        if (!$ruta) return;
        $path = $ruta;
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, strlen('/storage/'));
        }
        $path = ltrim($path, '/');
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo borrar archivo físico', ['ruta' => $ruta, 'error' => $e->getMessage()]);
        }
    }

    private function syncTecnicasPrendasDesdeFormulario(array $tecnicas, int $logoCotizacionId): array
    {
        $mapaNuevas = [];
        $prendaCotCache = [];

        $existentes = \App\Models\LogoCotizacionTecnicaPrenda::with('fotos')
            ->where('logo_cotizacion_id', $logoCotizacionId)
            ->get();

        $logoCotizacion = \App\Models\LogoCotizacion::find($logoCotizacionId);
        $cotizacionId = $logoCotizacion ? (int) $logoCotizacion->cotizacion_id : 0;

        $idsIncoming = [];
        foreach ($tecnicas as $tecnica) {
            foreach (($tecnica['prendas'] ?? []) as $p) {
                if (!empty($p['id'])) {
                    $idsIncoming[] = (int) $p['id'];
                }
            }
        }
        $idsIncoming = array_values(array_unique($idsIncoming));

        // Eliminar prendas técnicas que ya no vienen (prenda completa)
        $aEliminar = $existentes->filter(fn($m) => !in_array((int) $m->id, $idsIncoming, true));
        foreach ($aEliminar as $prendaTecnica) {
            foreach ($prendaTecnica->fotos as $foto) {
                $this->borrarArchivoPublicSiExiste($foto->ruta_webp);
                $this->borrarArchivoPublicSiExiste($foto->ruta_original);
                $this->borrarArchivoPublicSiExiste($foto->ruta_miniatura);
                $foto->forceDelete();
            }

            $prendaCotId = (int) $prendaTecnica->prenda_cot_id;
            $prendaTecnica->delete();

            // Intentar borrar también la prenda base (si existe)
            if ($prendaCotId) {
                $prendaCotSigueUsandose = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                    ->where('prenda_cot_id', $prendaCotId)
                    ->exists();

                if (!$prendaCotSigueUsandose) {
                    $prendaCot = \App\Models\PrendaCot::with(['fotos', 'telaFotos'])->find($prendaCotId);
                    if ($prendaCot) {
                        foreach ($prendaCot->fotos as $f) {
                            $this->borrarArchivoPublicSiExiste($f->ruta_webp ?? $f->ruta_original ?? null);
                            $f->forceDelete();
                        }
                        foreach ($prendaCot->telaFotos as $tf) {
                            $this->borrarArchivoPublicSiExiste($tf->ruta_webp ?? $tf->ruta_original ?? null);
                            $tf->forceDelete();
                        }
                        $prendaCot->forceDelete();
                    }
                }
            }
        }

        // Actualizar prendas existentes (ubicaciones, obs, tallas, variaciones)
        foreach ($tecnicas as $tecnicaIdx => $tecnica) {
            $tipoLogoId = $tecnica['tipo_logo']['id'] ?? null;
            $grupo = $tecnica['grupo_combinado'] ?? null;
            foreach (($tecnica['prendas'] ?? []) as $prendaIdx => $p) {
                $idPrendaTecnica = $p['id'] ?? null;

                // Si no viene id, es una prenda nueva: crearla
                if (!$idPrendaTecnica) {
                    if (!$cotizacionId || !$tipoLogoId) {
                        continue;
                    }

                    $nombrePrenda = (string) ($p['nombre_prenda'] ?? '');
                    $variacionesKey = is_string($p['variaciones_prenda'] ?? null)
                        ? ($p['variaciones_prenda'] ?? '')
                        : json_encode($p['variaciones_prenda'] ?? null);
                    $cacheKey = $cotizacionId . '|' . $nombrePrenda . '|' . ((string) ($grupo ?? '')) . '|' . ((string) ($variacionesKey ?? ''));

                    $prendaCotId = $prendaCotCache[$cacheKey] ?? null;
                    if (!$prendaCotId) {
                        $prendaCot = \App\Models\PrendaCot::create([
                            'cotizacion_id' => $cotizacionId,
                            'nombre_producto' => $nombrePrenda,
                            'descripcion' => $p['observaciones'] ?? '',
                            'cantidad' => $p['cantidad'] ?? 1,
                            'texto_personalizado_tallas' => $p['texto_personalizado_tallas'] ?? null,
                        ]);
                        $prendaCotId = (int) $prendaCot->id;
                        $prendaCotCache[$cacheKey] = $prendaCotId;
                    }

                    $nueva = \App\Models\LogoCotizacionTecnicaPrenda::create([
                        'logo_cotizacion_id' => $logoCotizacionId,
                        'tipo_logo_id' => (int) $tipoLogoId,
                        'prenda_cot_id' => (int) $prendaCotId,
                        'observaciones' => $p['observaciones'] ?? null,
                        'ubicaciones' => $p['ubicaciones'] ?? [],
                        'talla_cantidad' => $p['talla_cantidad'] ?? [],
                        'variaciones_prenda' => $p['variaciones_prenda'] ?? null,
                        'grupo_combinado' => $grupo,
                    ]);

                    $mapaNuevas[$tecnicaIdx] = $mapaNuevas[$tecnicaIdx] ?? [];
                    $mapaNuevas[$tecnicaIdx][$prendaIdx] = (int) $nueva->id;
                    continue;
                }

                $model = $existentes->firstWhere('id', (int) $idPrendaTecnica);
                if (!$model) {
                    continue;
                }
                $model->update([
                    'tipo_logo_id' => $tipoLogoId ?? $model->tipo_logo_id,
                    'observaciones' => $p['observaciones'] ?? $model->observaciones,
                    'ubicaciones' => $p['ubicaciones'] ?? $model->ubicaciones,
                    'talla_cantidad' => $p['talla_cantidad'] ?? $model->talla_cantidad,
                    'variaciones_prenda' => $p['variaciones_prenda'] ?? $model->variaciones_prenda,
                ]);
            }
        }

        return $mapaNuevas;
    }

    private function adjuntarNuevasFotosTecnicasDesdeRequest(array $tecnicas, int $logoCotizacionId, Request $request, array $mapaPrendasTecnica = []): void
    {
        // Archivos vienen como tecnica_X_prenda_Y_img_Z
        $imagenService = new TecnicaImagenService();

        foreach ($request->files->all() as $fieldName => $archivo) {
            if (!preg_match('/^tecnica_(\d+)_prenda_(\d+)_img_(\d+)$/', $fieldName, $m)) {
                continue;
            }
            $tecnicaIdx = (int) $m[1];
            $prendaIdx = (int) $m[2];
            $imgIdx = (int) $m[3];

            $tecnica = $tecnicas[$tecnicaIdx] ?? null;
            $prenda = $tecnica['prendas'][$prendaIdx] ?? null;
            if (!$tecnica || !$prenda) continue;

            $prendaTecnicaId = $prenda['id'] ?? null;
            if (!$prendaTecnicaId) {
                $prendaTecnicaId = $mapaPrendasTecnica[$tecnicaIdx][$prendaIdx] ?? null;
            }
            if (!$prendaTecnicaId) continue;

            $tipoNombre = $tecnica['tipo_logo']['nombre'] ?? 'TÉCNICA';
            $grupo = $tecnica['grupo_combinado'] ?? null;

            $rutas = $imagenService->guardarImagen($archivo, $logoCotizacionId, $tipoNombre, $grupo);
            $rutaFinal = $rutas['ruta_webp'] ?? null;
            if (!$rutaFinal) continue;

            \App\Models\LogoCotizacionTecnicaPrendaFoto::create([
                'logo_cotizacion_tecnica_prenda_id' => (int) $prendaTecnicaId,
                'ruta_original' => $rutaFinal,
                'ruta_webp' => $rutaFinal,
                'ruta_miniatura' => $rutaFinal,
                'orden' => $imgIdx,
                'ancho' => $rutas['ancho'] ?? 0,
                'alto' => $rutas['alto'] ?? 0,
                'tamaño' => $rutas['tamaño'] ?? 0,
            ]);
        }
    }

    private function vincularLogosCompartidosTecnicasDesdeRequest(array $tecnicas, int $logoCotizacionId, Request $request, array $mapaPrendasTecnica = []): void
    {
        // Misma estrategia que Paso 3:
        // 1) leer metadatos logo_compartido_metadata_*
        // 2) guardar cada logo UNA sola vez (archivo tecnica_X_logo_compartido_<clave>)
        // 3) vincular como foto a cada prenda técnica cuya técnica esté incluida en tecnicasCompartidas
        $imagenService = new TecnicaImagenService();

        $imagenesCompartidas = [];
        foreach ($request->all() as $key => $value) {
            if (preg_match('/^logo_compartido_metadata_(\d+)$/', $key) && is_string($value)) {
                $metadatos = json_decode($value, true);
                if ($metadatos && isset($metadatos['nombreCompartido'])) {
                    $imagenesCompartidas[$metadatos['nombreCompartido']] = $metadatos;
                }
            }
        }
        if (empty($imagenesCompartidas)) {
            return;
        }

        $logosCompartidosGuardados = [];
        foreach ($imagenesCompartidas as $clave => $metadatos) {
            $tecnicasCompartidas = $metadatos['tecnicasCompartidas'] ?? [];
            if (empty($tecnicasCompartidas)) {
                continue;
            }

            $archivoEncontrado = null;
            foreach ($request->files->all() as $fieldName => $archivo) {
                if (preg_match('/^tecnica_(\d+)_logo_compartido_(.+)$/', $fieldName, $matches)) {
                    $claveEnCampo = $matches[2];
                    if ($claveEnCampo === $clave) {
                        $archivoEncontrado = $archivo;
                        break;
                    }
                }
            }
            if (!$archivoEncontrado) {
                continue;
            }

            try {
                $rutasImagen = $imagenService->guardarImagen(
                    $archivoEncontrado,
                    $logoCotizacionId,
                    implode('-', $tecnicasCompartidas),
                    null
                );
                $rutaFinal = $rutasImagen['ruta_webp'] ?? null;
                if ($rutaFinal) {
                    $logosCompartidosGuardados[$clave] = $rutaFinal;
                }
            } catch (\Exception $e) {
                Log::error(' Error guardando logo compartido (updateBorrador)', [
                    'clave' => $clave,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($logosCompartidosGuardados)) {
            return;
        }

        foreach ($tecnicas as $tecnicaIdx => $tecnica) {
            $tipoNombre = $tecnica['tipo_logo']['nombre'] ?? null;
            if (!$tipoNombre) {
                continue;
            }

            foreach (($tecnica['prendas'] ?? []) as $prendaIdx => $p) {
                $prendaTecnicaId = $p['id'] ?? null;
                if (!$prendaTecnicaId) {
                    $prendaTecnicaId = $mapaPrendasTecnica[$tecnicaIdx][$prendaIdx] ?? null;
                }
                if (!$prendaTecnicaId) {
                    continue;
                }

                foreach ($imagenesCompartidas as $clave => $metadatos) {
                    $tecnicasCompartidas = $metadatos['tecnicasCompartidas'] ?? [];
                    if (!in_array($tipoNombre, $tecnicasCompartidas, true)) {
                        continue;
                    }
                    $rutaCompartida = $logosCompartidosGuardados[$clave] ?? null;
                    if (!$rutaCompartida) {
                        continue;
                    }

                    $rutaNormalizada = $rutaCompartida;
                    if (is_string($rutaNormalizada) && str_starts_with($rutaNormalizada, '/storage/')) {
                        $rutaNormalizada = substr($rutaNormalizada, strlen('/storage/'));
                    }
                    if (is_string($rutaNormalizada)) {
                        $rutaNormalizada = ltrim($rutaNormalizada, '/');
                    }

                    $rutasAComparar = array_values(array_unique(array_filter([
                        $rutaNormalizada,
                        is_string($rutaNormalizada) ? ('/' . ltrim($rutaNormalizada, '/')) : null,
                        is_string($rutaNormalizada) ? ('/storage/' . ltrim($rutaNormalizada, '/')) : null,
                    ], fn($v) => is_string($v) && $v !== '')));

                    $yaExiste = \App\Models\LogoCotizacionTecnicaPrendaFoto::where('logo_cotizacion_tecnica_prenda_id', (int) $prendaTecnicaId)
                        ->where(function ($q) use ($rutasAComparar) {
                            $q->whereIn('ruta_webp', $rutasAComparar)
                                ->orWhereIn('ruta_original', $rutasAComparar);
                        })
                        ->exists();
                    if ($yaExiste) {
                        continue;
                    }

                    $ancho = 0;
                    $alto = 0;
                    $tam = 0;
                    try {
                        $path = $rutaNormalizada;
                        if (str_starts_with($path, '/storage/')) {
                            $path = substr($path, strlen('/storage/'));
                        }
                        $path = ltrim($path, '/');
                        $full = storage_path('app/public/' . $path);
                        $dim = @getimagesize($full);
                        $ancho = $dim[0] ?? 0;
                        $alto = $dim[1] ?? 0;
                        $tam = @filesize($full) ?: 0;
                    } catch (\Exception $e) {
                        // no-op
                    }

                    \App\Models\LogoCotizacionTecnicaPrendaFoto::create([
                        'logo_cotizacion_tecnica_prenda_id' => (int) $prendaTecnicaId,
                        'ruta_original' => $rutaNormalizada,
                        'ruta_webp' => $rutaNormalizada,
                        'ruta_miniatura' => $rutaNormalizada,
                        'orden' => 999,
                        'ancho' => $ancho,
                        'alto' => $alto,
                        'tamaño' => $tam,
                    ]);
                }
            }
        }
    }

    /**
     * Guardar cotización de bordado
     * SINCRÓNICO: Genera número INMEDIATAMENTE con pessimistic lock
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                Log::info('🔵 CotizacionBordadoController@store - Iniciando guardado de cotización de Bordado', [
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'is_editing' => $request->has('editar')
                ]);

                // Determinar si es borrador o enviada
                $action = $request->input('action') ?? $request->input('accion');
                $esBorrador = $action === 'borrador';
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

                // Obtener o crear cliente
                $clienteId = $request->input('cliente_id');
                $nombreCliente = $request->input('cliente');

                Log::info('Cliente recibido en store', [
                    'cliente_id' => $clienteId,
                    'nombre_cliente' => $nombreCliente,
                    'all_inputs' => $request->all()
                ]);

                if ($nombreCliente && !$clienteId) {
                    $cliente = Cliente::firstOrCreate(
                        ['nombre' => $nombreCliente],
                        ['nombre' => $nombreCliente]
                    );
                    $clienteId = $cliente->id;
                    Log::info('Cliente creado o encontrado en store', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
                }

                // Generar número SINCRONICAMENTE si se envía
                $numeroCotizacion = null;
                if (!$esBorrador) {
                    $usuarioId = Auth::id();
                    $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                    Log::info(' Número generado sincronicamente', [
                        'numero' => $numeroCotizacion
                    ]);
                }

                // Procesar técnicas (pueden venir como JSON string desde FormData)
                $tecnicas = $request->input('tecnicas', '[]');
                Log::info(' Técnicas recibidas (raw):', ['tecnicas' => $tecnicas, 'tipo' => gettype($tecnicas)]);
                
                if (is_string($tecnicas)) {
                    $tecnicas = json_decode($tecnicas, true) ?? [];
                }
                Log::info(' Técnicas procesadas:', ['tecnicas' => $tecnicas]);
                
                // Procesar secciones (pueden venir como JSON string desde FormData)
                $secciones = $request->input('secciones', '[]');
                if (is_string($secciones)) {
                    $secciones = json_decode($secciones, true) ?? [];
                }
                Log::info(' Secciones procesadas:', ['secciones' => $secciones]);
                
                // Procesar observaciones generales (pueden venir como JSON string desde FormData)
                $observacionesGenerales = $request->input('observaciones_generales', '[]');
                if (is_string($observacionesGenerales)) {
                    $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
                }
                Log::info(' Observaciones generales procesadas:', ['observaciones' => $observacionesGenerales]);
                
                // Buscar el tipo de cotización "Logo/Bordado" dinámicamente
                $tipoBordado = \App\Models\TipoCotizacion::where('codigo', 'L')->first();
                
                if (!$tipoBordado) {
                    Log::error(' Tipo de cotización "Logo" (L) no encontrado en tipos_cotizacion');
                    return response()->json([
                        'success' => false,
                        'message' => 'Error: Tipo de cotización Logo no está registrado en el sistema.',
                        'error' => 'TIPO_LOGO_NO_ENCONTRADO'
                    ], 500);
                }
                
                // Crear cotización en tabla cotizaciones
                $cotizacion = Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $clienteId,
                    'numero_cotizacion' => $numeroCotizacion,
                    'tipo_cotizacion_id' => $tipoBordado->id, // Cotización de Logo/Bordado (B)
                    'tipo_venta' => $request->input('tipo_venta', 'M'),
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'fecha_envio' => !$esBorrador ? now() : null,
                    'especificaciones' => json_encode($request->input('especificaciones', [])),
                ]);

                Log::info(' Cotización de Bordado creada en tabla cotizaciones', [
                    'cotizacion_id' => $cotizacion->id,
                    'numero_cotizacion' => $numeroCotizacion,
                ]);

                //  CREAR LogoCotizacion - NO viene del formulario, se crea aquí
                // Todos los datos de técnicas, prendas, etc se crean en este request
                $logoCotizacion = \App\Models\LogoCotizacion::create([
                    'cotizacion_id' => $cotizacion->id,
                    'observaciones_generales' => json_encode($observacionesGenerales ?? []),
                    'tipo_venta' => $request->input('tipo_venta_bordado') ?? $request->input('tipo_venta') ?? null,
                ]);

                Log::info(' LogoCotizacion creado nuevo', [
                    'logo_id' => $logoCotizacion->id,
                    'cotizacion_id' => $cotizacion->id
                ]);
                
                Log::info(' Detalles de bordado guardados en tabla logo_cotizaciones', [
                    'cotizacion_id' => $cotizacion->id,
                    'logo_id' => $logoCotizacion->id,
                    'estado' => 'nueva_cotizacion'
                ]);

                // Procesar imágenes si existen
                if ($request->hasFile('imagenes') || $request->hasFile('imagenes_bordado')) {
                    $this->procesarImagenesCotizacion($request, $cotizacion->id);
                }

                //  PROCESAR TÉCNICAS CON PRENDAS (nueva lógica)
                if (!empty($tecnicas) && is_array($tecnicas) && count($tecnicas) > 0) {
                    Log::info(' Procesando técnicas agregadas desde el modal', [
                        'count' => count($tecnicas),
                        'logo_cotizacion_id' => $logoCotizacion->id
                    ]);
                    
                    $this->procesarTecnicasDelFormulario($tecnicas, $logoCotizacion->id, $request);
                } else {
                    Log::info(' No hay técnicas para procesar', [
                        'tecnicas_count' => is_array($tecnicas) ? count($tecnicas) : 0,
                        'tecnicas_type' => gettype($tecnicas)
                    ]);
                }

                // PROCESAR TELAS, COLORES Y REFERENCIAS
                $this->procesarTelasDelFormulario($request, $logoCotizacion->id);

                // Si se envía, aún encolamos el job pero el número YA EXISTE
                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        2 // tipo_cotizacion_id para Logo/Bordado
                    )->onQueue('cotizaciones');

                    Log::info(' Job de envío encolado (número ya existe)', [
                        'cotizacion_id' => $cotizacion->id,
                        'numero' => $numeroCotizacion,
                        'queue' => 'cotizaciones'
                    ]);
                }

                // Recargar la cotización con todas sus relaciones
                $cotizacionCompleta = Cotizacion::with([
                    'cliente',
                    'logoCotizacion' => function ($query) {
                        $query->with(['fotos' => function ($fotosQuery) {
                            $fotosQuery->orderBy('orden');
                        }]);
                    }
                ])->findOrFail($cotizacion->id);

                // Convertir a array y asegurar que los accessors estén incluidos
                $resultado = $cotizacionCompleta->toArray();

                // Broadcast en tiempo real para contador (solo si NO es borrador)
                if (!$esBorrador) {
                    $cotizacionCompleta->loadMissing(['cliente', 'asesor']);
                    $payload = $cotizacionCompleta->toArray();

                    // Campos extra para compatibilidad con render en frontend
                    $payload['asesora'] = $cotizacionCompleta->asesor?->name;
                    $payload['usuario'] = [
                        'name' => $cotizacionCompleta->asesor?->name,
                    ];
                    $payload['nombre_cliente'] = $cotizacionCompleta->cliente?->nombre;

                    Log::info('[BROADCAST-LOGO] Emitiendo evento CotizacionCreada', [
                        'cotizacion_id' => $cotizacionCompleta->id,
                        'estado' => $cotizacionCompleta->estado,
                        'asesor_id' => $cotizacionCompleta->asesor_id,
                        'tipo_cotizacion_id' => $cotizacionCompleta->tipo_cotizacion_id,
                    ]);

                    broadcast(new CotizacionCreada(
                        $cotizacionCompleta->id,
                        $cotizacionCompleta->asesor_id,
                        $cotizacionCompleta->estado,
                        $payload
                    ));
                }
                
                // Asegurar que las URLs de las fotos estén correctas
                if (isset($resultado['logo_cotizacion']['fotos'])) {
                    foreach ($resultado['logo_cotizacion']['fotos'] as &$foto) {
                        // Agregar el accessor 'url' manualmente si no está
                        if (!isset($foto['url'])) {
                            $ruta = $foto['ruta_webp'] ?? $foto['ruta_original'];
                            if ($ruta && !str_starts_with($ruta, 'http') && !str_starts_with($ruta, '/storage/')) {
                                $foto['url'] = '/storage/' . ltrim($ruta, '/');
                            } else {
                                $foto['url'] = $ruta;
                            }
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => $esBorrador ? 'Cotización guardada como borrador' : 'Cotización enviada - Número: ' . $numeroCotizacion,
                    'data' => $resultado,
                    'logoCotizacionId' => $logoCotizacion->id,
                    'cotizacionId' => $cotizacion->id,
                    'redirect' => route('asesores.cotizaciones.index')
                        . '?'
                        . http_build_query([
                            'tab' => $esBorrador ? 'borradores' : 'cotizaciones',
                            'highlight' => $cotizacion->id,
                        ])
                ], 201);

            } catch (\Exception $e) {
                Log::error(' Error al guardar cotización de Bordado', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la cotización: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], 500);
            }
        }, attempts: 3);
    }

    /**
     * Procesar y guardar imágenes del bordado en logo_fotos_cot
     */
    private function procesarImagenesCotizacion(Request $request, $cotizacionId)
    {
        // Obtener el ID de logo_cotizacion
        $logoCotizacion = DB::table('logo_cotizaciones')
            ->where('cotizacion_id', $cotizacionId)
            ->first();

        if (!$logoCotizacion) {
            Log::warning(' No se encontró logo_cotizacion para cotización', [
                'cotizacion_id' => $cotizacionId
            ]);
            return;
        }

        $logoCotizacionId = $logoCotizacion->id;

        // Obtener el último orden para continuar la numeración
        $ultimoOrden = DB::table('logo_fotos_cot')
            ->where('logo_cotizacion_id', $logoCotizacionId)
            ->max('orden') ?? 0;

        $orden = $ultimoOrden + 1;

        // Crear instancia del ImageManager
        $manager = new ImageManager(new Driver());

        // Procesar archivos del request
        $archivos = $request->file('imagenes') ?? $request->file('imagenes_bordado') ?? [];
        if (!empty($archivos)) {
            foreach ($archivos as $archivo) {
                try {
                    // Generar un nombre de archivo único con extensión .webp
                    $nombreArchivo = uniqid() . '.webp';
                    $rutaDestino = 'bordado/cotizaciones/' . $cotizacionId . '/' . $nombreArchivo;

                    // Convertir y guardar la imagen en formato .webp usando Intervention Image v3
                    $image = $manager->read($archivo);
                    $webpContent = $image->toWebp(80);
                    Storage::disk('public')->put($rutaDestino, $webpContent);

                    // Las rutas ahora apuntan al archivo .webp
                    $rutaOriginal = $rutaDestino;
                    $rutaWebp = $rutaDestino;
                    $rutaMiniatura = $rutaDestino;

                    // Obtener dimensiones de la imagen
                    $imageInfo = @getimagesize(storage_path('app/public/' . $rutaOriginal));
                    $ancho = $imageInfo[0] ?? 0;
                    $alto = $imageInfo[1] ?? 0;
                    $tamaño = Storage::disk('public')->size($rutaOriginal);

                    // Guardar en logo_fotos_cot
                    DB::table('logo_fotos_cot')->insert([
                        'logo_cotizacion_id' => $logoCotizacionId,
                        'ruta_original' => $rutaOriginal,
                        'ruta_webp' => $rutaWebp,
                        'ruta_miniatura' => $rutaMiniatura,
                        'orden' => $orden,
                        'ancho' => $ancho,
                        'alto' => $alto,
                        'tamaño' => $tamaño,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info(' Imagen guardada en logo_fotos_cot', [
                        'logo_cotizacion_id' => $logoCotizacionId,
                        'ruta' => $rutaOriginal,
                        'orden' => $orden,
                        'tamaño' => $tamaño,
                        'dimensiones' => "{$ancho}x{$alto}"
                    ]);

                    $orden++;

                } catch (\Exception $e) {
                    Log::error(' Error al guardar imagen', [
                        'error' => $e->getMessage(),
                        'archivo' => $archivo->getClientOriginalName()
                    ]);
                }
            }
        }
    }

    /**
     * Generar número de cotización sincronicamente con pessimistic lock
     * 
     * Usa lockForUpdate() para prevenir race conditions
     * Formato: COT-20250124-001
     * 
     * @param string $tipo tipo de secuencia (cotizaciones_prenda, cotizaciones_bordado, etc)
     * @return string número generado
     */
    
    /**
     * Listar cotizaciones de bordado
     */
    public function lista()
    {
        return redirect()->route('cotizaciones.index');
    }

    /**
     * Editar cotización de bordado
     */
    public function edit($id)
    {
        $cotizacion = Cotizacion::with([
            'cliente',
            'logoCotizacion.fotos'
        ])->findOrFail($id);

        // Verificar que el usuario es propietario
        if ($cotizacion->asesor_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar esta cotización');
        }

        return view('cotizaciones.bordado.edit', [
            'cotizacion' => $cotizacion,
            'id' => $id
        ]);
    }

    /**
     * Actualizar cotización de bordado
     */
    public function update(Request $request, $id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización actualizada exitosamente');
    }

    /**
     * Enviar cotización de bordado
     */
    public function enviar(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);

            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para enviar esta cotización'
                ], 403);
            }

            if ($cotizacion->estado !== 'BORRADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no está en estado borrador'
                ], 422);
            }

            app(CotizacionEstadoService::class)->enviarACOntador($cotizacion);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada exitosamente',
                'cotizacion_id' => $cotizacion->id,
                'cotizacion' => $cotizacion->fresh(),
                'pedido_id' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar cotización de bordado', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la cotización: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar cotización de bordado
     */
    public function destroy($id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización eliminada exitosamente');
    }

    /**
     * Procesar técnicas del formulario y guardarlas en logo_cotizacion_tecnica_prendas
     * 
     * Las técnicas vienen del array window.tecnicasAgregadas del cliente
     * Los archivos vienen con nombres: tecnica_X_prenda_Y_img_Z
     */
    private function procesarTecnicasDelFormulario(array $tecnicas, int $logoCotizacionId, Request $request)
    {
        try {
            Log::info('🔵 procesarTecnicasDelFormulario() - Iniciando', [
                'count' => count($tecnicas),
                'logoCotizacionId' => $logoCotizacionId
            ]);
            
            // DEBUG: Ver qué metadatos llegaron al request
            $todasLasClavesRequest = array_keys($request->all());
            $clavesConMetadata = array_filter($todasLasClavesRequest, fn($k) => str_contains($k, 'logo_compartido_metadata'));
            Log::info(' METADATA en Request->all():', [
                'todas_las_claves' => $todasLasClavesRequest,
                'claves_con_metadata' => $clavesConMetadata,
                'count_metadata' => count($clavesConMetadata),
                'valores_metadata' => array_intersect_key($request->all(), array_flip($clavesConMetadata))
            ]);

            // Recopilar archivos por técnica, prenda y logos compartidos
            $archivosAgrupados = [];
            $logosCompartidosAgrupados = [];
            foreach ($request->files->all() as $fieldName => $archivo) {
                if (preg_match('/^tecnica_(\d+)_prenda_(\d+)_img_(\d+)$/', $fieldName, $matches)) {
                    $tecnicaIdx = (int)$matches[1];
                    $prendaIdx = (int)$matches[2];
                    $imgIdx = (int)$matches[3];
                    
                    if (!isset($archivosAgrupados[$tecnicaIdx])) {
                        $archivosAgrupados[$tecnicaIdx] = [];
                    }
                    if (!isset($archivosAgrupados[$tecnicaIdx][$prendaIdx])) {
                        $archivosAgrupados[$tecnicaIdx][$prendaIdx] = [];
                    }
                    
                    $archivosAgrupados[$tecnicaIdx][$prendaIdx][$imgIdx] = $archivo;
                    
                    Log::info('📸 Archivo encontrado', [
                        'fieldName' => $fieldName,
                        'tecnica_idx' => $tecnicaIdx,
                        'prenda_idx' => $prendaIdx,
                        'img_idx' => $imgIdx,
                        'nombre' => $archivo->getClientOriginalName()
                    ]);
                } elseif (preg_match('/^tecnica_(\d+)_logo_compartido_(.+)$/', $fieldName, $matches)) {
                    // NUEVO: Procesar logos compartidos
                    $tecnicaIdx = (int)$matches[1];
                    $claveLogo = $matches[2];
                    
                    if (!isset($logosCompartidosAgrupados[$tecnicaIdx])) {
                        $logosCompartidosAgrupados[$tecnicaIdx] = [];
                    }
                    
                    $logosCompartidosAgrupados[$tecnicaIdx][$claveLogo] = $archivo;
                    
                    Log::info(' Logo compartido encontrado', [
                        'fieldName' => $fieldName,
                        'tecnica_idx' => $tecnicaIdx,
                        'clave' => $claveLogo,
                        'nombre' => $archivo->getClientOriginalName()
                    ]);
                }
            }
            
            Log::info(' Archivos agrupados por técnica', [
                'tecnicas_con_archivos' => count($archivosAgrupados),
                'estructura' => json_encode(array_map(
                    fn($t) => array_map(fn($p) => count($p), $t),
                    $archivosAgrupados
                )),
                'tecnicas_con_logos_compartidos' => count($logosCompartidosAgrupados)
            ]);

            // NUEVO: PROCESAR Y GUARDAR TODOS LOS LOGOS COMPARTIDOS UNA SOLA VEZ AL INICIO
            $imagenService = new TecnicaImagenService();
            $logosCompartidosGuardados = []; // Mapeo de clave -> ruta guardada
            
            // Obtener metadatos de logos compartidos
            $imagenesCompartidas = [];
            foreach ($request->all() as $key => $value) {
                if (preg_match('/^logo_compartido_metadata_(\d+)$/', $key) && is_string($value)) {
                    $metadatos = json_decode($value, true);
                    if ($metadatos && isset($metadatos['nombreCompartido'])) {
                        $imagenesCompartidas[$metadatos['nombreCompartido']] = $metadatos;
                    }
                }
            }
            
            Log::info(' Metadatos de logos compartidos encontrados:', [
                'count' => count($imagenesCompartidas),
                'claves' => array_keys($imagenesCompartidas)
            ]);
            
            // Procesar cada logo compartido UNA SOLA VEZ
            foreach ($imagenesCompartidas as $clave => $metadatos) {
                $tecnicasCompartidas = $metadatos['tecnicasCompartidas'] ?? [];
                
                if (empty($tecnicasCompartidas)) {
                    continue;
                }
                
                // Buscar el archivo en el FormData
                $archivoEncontrado = null;
                foreach ($request->files->all() as $fieldName => $archivo) {
                    if (preg_match("/^tecnica_(\d+)_logo_compartido_(.+)$/", $fieldName, $matches)) {
                        $claveEnCampo = $matches[2];
                        if ($claveEnCampo === $clave) {
                            $archivoEncontrado = $archivo;
                            break; // Solo procesar una vez por clave
                        }
                    }
                }
                
                if ($archivoEncontrado) {
                    try {
                        Log::info(' Guardando logo compartido', [
                            'clave' => $clave,
                            'tecnicas' => implode(' + ', $tecnicasCompartidas),
                            'archivo' => $archivoEncontrado->getClientOriginalName()
                        ]);
                        
                        // Guardar imagen UNA SOLA VEZ con nombre que incluye todas las técnicas
                        $rutasImagen = $imagenService->guardarImagen(
                            $archivoEncontrado,
                            $logoCotizacionId,
                            implode('-', $tecnicasCompartidas),
                            null
                        );
                        
                        $logosCompartidosGuardados[$clave] = $rutasImagen['ruta_webp'];
                        
                        Log::info(' Logo compartido guardado UNA SOLA VEZ', [
                            'clave' => $clave,
                            'ruta' => $rutasImagen['ruta_webp'],
                            'tecnicas' => implode(' + ', $tecnicasCompartidas)
                        ]);
                    } catch (\Exception $e) {
                        Log::error(' Error guardando logo compartido', [
                            'clave' => $clave,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            Log::info(' TODOS los logos compartidos guardados', [
                'count' => count($logosCompartidosGuardados),
                'claves' => array_keys($logosCompartidosGuardados)
            ]);

            // Procesar cada técnica
            $tecnicaController = new LogoCotizacionTecnicaController();

            foreach ($tecnicas as $tecnicaIdx => $tecnica) {
                Log::info(" Procesando técnica [{$tecnicaIdx}]", [
                    'tipo_logo' => $tecnica['tipo_logo']['nombre'] ?? 'desconocido',
                    'prendas_count' => count($tecnica['prendas'] ?? []),
                    'es_combinada' => $tecnica['es_combinada'] ?? false
                ]);

                // Validar que tenga tipo_logo
                if (!isset($tecnica['tipo_logo']['id'])) {
                    Log::warning(" Técnica sin tipo_logo válido, omitiendo");
                    continue;
                }

                // Preparar prendas con archivos
                $prendasSinArchivos = [];
                foreach ($tecnica['prendas'] as $prendaIdx => $prenda) {
                    $prendasSinArchivos[] = [
                        'nombre_prenda' => $prenda['nombre_prenda'] ?? '',
                        'observaciones' => $prenda['observaciones'] ?? '',
                        'ubicaciones' => $prenda['ubicaciones'] ?? [],
                        'talla_cantidad' => $prenda['talla_cantidad'] ?? [],
                        'variaciones_prenda' => $prenda['variaciones_prenda'] ?? null,
                        'imagenes_data_urls' => []
                    ];
                }

                // Crear Request simulado
                //  Convertir es_combinada a string 'true'/'false' para validación
                $esCombinada = $tecnica['es_combinada'] ?? false;
                $esCombinada = ($esCombinada === true || $esCombinada === 'true' || $esCombinada === 1 || $esCombinada === '1') ? 'true' : 'false';
                
                // Preparar parámetros incluyendo metadatos de logos compartidos
                $parametrosFakeRequest = [
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'tipo_logo_id' => $tecnica['tipo_logo']['id'],
                    'prendas' => json_encode($prendasSinArchivos),
                    'es_combinada' => $esCombinada,  // ← String, no boolean
                    'grupo_combinado' => $tecnica['grupo_combinado'] ?? null,
                    // NUEVO: Pasar las rutas de logos compartidos ya guardados
                    'logos_compartidos_guardados' => json_encode($logosCompartidosGuardados),
                ];
                
                // Agregar metadatos de logos compartidos desde el request original
                foreach ($request->all() as $key => $value) {
                    if (preg_match('/^logo_compartido_metadata_(\d+)$/', $key) && is_string($value)) {
                        $parametrosFakeRequest[$key] = $value;
                    }
                }
                
                $fakeRequest = new Request($parametrosFakeRequest);

                // Agregar archivos al Request simulado
                $archivosEnEstaTecnica = $archivosAgrupados[$tecnicaIdx] ?? [];
                $logosCompartidosEnEstaTecnica = $logosCompartidosAgrupados[$tecnicaIdx] ?? [];
                $archivosCopiados = 0;
                
                foreach ($archivosEnEstaTecnica as $prendaIdx => $archivosPorIndice) {
                    foreach ($archivosPorIndice as $imgIdx => $archivo) {
                        $fieldName = "imagenes_prenda_{$prendaIdx}_{$imgIdx}";
                        $fakeRequest->files->set($fieldName, $archivo);
                        $archivosCopiados++;
                        
                        Log::info("📸 Archivo asignado al Request", [
                            'fieldName' => $fieldName,
                            'nombre' => $archivo->getClientOriginalName()
                        ]);
                    }
                }
                
                // NO AGREGAR LOGOS COMPARTIDOS AL REQUEST - YA FUERON GUARDADOS
                // Solo pasamos las rutas a través del parámetro 'logos_compartidos_guardados'

                // Llamar al controlador
                try {
                    $response = $tecnicaController->agregarTecnica($fakeRequest);
                    $statusCode = $response->getStatusCode();
                    
                    if ($statusCode === 201) {
                        Log::info(" Técnica agregada exitosamente", [
                            'tipo_logo' => $tecnica['tipo_logo']['nombre'],
                            'archivos_procesados' => $archivosCopiados
                        ]);
                    } else {
                        Log::warning(" Técnica procesada con status {$statusCode}");
                    }
                } catch (\Exception $e) {
                    Log::error(" Error procesando técnica", [
                        'tipo_logo' => $tecnica['tipo_logo']['nombre'] ?? 'desconocido',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info(" Todas las técnicas procesadas");

        } catch (\Exception $e) {
            Log::error(' Error en procesarTecnicasDelFormulario()', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Procesar y guardar Telas, Colores y Referencias del formulario
     * 
     * Los datos vienen en: telas_prendas_json (JSON array)
     * Estructura:
     * [
     *   {
     *     "prenda_cot_id": 1,
     *     "color": "Rojo",
     *     "tela": "Algodón 100%",
     *     "ref": "REF-001",
     *     "imagen": File (opcional)
     *   }
     * ]
     */
    private function procesarTelasDelFormulario(Request $request, int $logoCotizacionId)
    {
        try {
            $procesados = 0;

            Log::info('🧵 procesarTelasDelFormulario() - Iniciando', [
                'logo_cotizacion_id' => $logoCotizacionId
            ]);

            // 🆕 NUEVA ESTRATEGIA: Procesar telas desde la estructura de TÉCNICAS en el JSON
            // Obtener técnicas del request (es lo que se envió desde el formulario)
            $tecnicasJson = $request->input('tecnicas', '[]');
            if (is_string($tecnicasJson)) {
                $tecnicasArray = json_decode($tecnicasJson, true) ?? [];
            } else {
                $tecnicasArray = $tecnicasJson;
            }
            
            Log::info('📄 Técnicas en request JSON:', [
                'count' => count($tecnicasArray)
            ]);

            // Iterar por cada técnica en el JSON (preservando índices)
            foreach ($tecnicasArray as $tecnicaIdx => $tecnicaData) {
                Log::info("  📌 Técnica [{$tecnicaIdx}]", [
                    'tipo_logo' => $tecnicaData['tipo_logo']['nombre'] ?? 'desconocido'
                ]);

                // Obtener prendas de esta técnica
                $prendas = $tecnicaData['prendas'] ?? [];
                
                foreach ($prendas as $prendaIdx => $prendaData) {
                    Log::info("     Prenda [{$prendaIdx}]", [
                        'nombre' => $prendaData['nombre_prenda'] ?? 'sin nombre'
                    ]);

                    // Obtener telas de esta prenda
                    $telasData = $prendaData['telas'] ?? [];
                    
                    if (empty($telasData)) {
                        Log::info("      (sin telas)");
                        continue;
                    }

                    // Ahora necesito obtener el prenda_cot_id real
                    // Buscar la prenda técnica guardada que corresponde a esta técnica y prenda
                    $prendaTecnicaGuardada = null;
                    
                    // Estrategia: buscar prendas técnicas con el nombre que coincida
                    $nombrePrenda = $prendaData['nombre_prenda'] ?? null;
                    
                    $prendasTecnicas = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                        ->get();
                    
                    // Filtrar por técnica y nombre (aproximado)
                    $prendaTecnicaGuardada = $prendasTecnicas->first(function ($prenda) use ($nombrePrenda) {
                        $prendaCot = $prenda->prendaCot;
                        return $prendaCot && $prendaCot->nombre_producto === $nombrePrenda;
                    });
                    
                    if (!$prendaTecnicaGuardada) {
                        // Si no encuentra por nombre exacto, usar el siguiente disponible
                        // (esto es un fallback para casos donde múltiples prendas tienen mismo nombre)
                        $usedIds = \App\Models\LogoCotizacionTelasPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                            ->pluck('prenda_cot_id')
                            ->toArray();
                        
                        $prendaTecnicaGuardada = $prendasTecnicas->first(function ($prenda) use ($usedIds) {
                            return !in_array($prenda->prenda_cot_id, $usedIds);
                        });
                    }
                    
                    if (!$prendaTecnicaGuardada) {
                        Log::warning("     No se encontró prenda técnica para vincular telas", [
                            'nombre_prenda' => $nombrePrenda
                        ]);
                        continue;
                    }
                    
                    $prendaCotId = $prendaTecnicaGuardada->prenda_cot_id;
                    
                    Log::info("     Prenda técnica encontrada", [
                        'prenda_cot_id' => $prendaCotId,
                        'nombre' => $nombrePrenda
                    ]);

                    // Procesar cada tela de esta prenda
                    foreach ($telasData as $telaIdx => $tela) {
                        $color = $tela['color'] ?? null;
                        $nombreTela = $tela['tela'] ?? null;
                        $referencia = $tela['referencia'] ?? null;
                        
                        Log::info("       Tela [{$telaIdx}]", [
                            'color' => $color,
                            'tela' => $nombreTela,
                            'referencia' => $referencia
                        ]);

                        // Si hay al menos un dato, verificar y guardar
                        if ($color || $nombreTela || $referencia) {
                            // 🆕 VERIFICAR SI YA EXISTE PARA EVITAR DUPLICADOS
                            // Hacer esto ANTES de procesar la imagen
                            // Mismo prenda_cot_id + misma tela + mismo color = DUPLICADO
                            $yaExiste = \App\Models\LogoCotizacionTelasPrenda::where([
                                ['logo_cotizacion_id', '=', $logoCotizacionId],
                                ['prenda_cot_id', '=', $prendaCotId],
                                ['tela', '=', $nombreTela],
                                ['color', '=', $color],
                                ['ref', '=', $referencia],
                            ])->exists();
                            
                            if ($yaExiste) {
                                Log::info('        ⏭️ Tela ya existe, saltando duplicado (sin guardar imagen)', [
                                    'prenda_cot_id' => $prendaCotId,
                                    'tela' => $nombreTela,
                                    'color' => $color,
                                    'ref' => $referencia
                                ]);
                                continue;  // ← Saltamos TODO, incluida la imagen
                            }

                            // Obtener imagen SI NO ES DUPLICADO
                            $rutaImagen = null;
                            $fieldName = "tecnica_{$tecnicaIdx}_prenda_{$prendaIdx}_tela_{$telaIdx}";
                            
                            if ($request->hasFile($fieldName)) {
                                try {
                                    $archivoTela = $request->file($fieldName);
                                    $directorioTelas = "cotizaciones/{$logoCotizacionId}/tela";
                                    $nombreImagen = 'tela_' . time() . '_' . uniqid() . '.' . $archivoTela->extension();
                                    
                                    $rutaGuardada = Storage::disk('public')->putFileAs(
                                        $directorioTelas,
                                        $archivoTela,
                                        $nombreImagen
                                    );
                                    
                                    $rutaImagen = Storage::url($rutaGuardada);
                                    
                                    Log::info('         Imagen guardada', [
                                        'fieldName' => $fieldName,
                                        'archivo' => $archivoTela->getClientOriginalName(),
                                        'ruta' => $rutaImagen
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error('         Error guardando imagen', [
                                        'fieldName' => $fieldName,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                            
                            try {
                                \App\Models\LogoCotizacionTelasPrenda::create([
                                    'logo_cotizacion_id' => $logoCotizacionId,
                                    'prenda_cot_id' => $prendaCotId,
                                    'tela' => $nombreTela,
                                    'color' => $color,
                                    'ref' => $referencia,
                                    'img' => $rutaImagen,
                                ]);

                                $procesados++;

                                Log::info('         Tela guardada en BD', [
                                    'prenda_cot_id' => $prendaCotId,
                                    'tela' => $nombreTela,
                                    'color' => $color,
                                    'ref' => $referencia
                                ]);
                            } catch (\Exception $e) {
                                Log::error('         Error en BD', [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            }

            Log::info(" procesarTelasDelFormulario() completado", [
                'total_telas_guardadas' => $procesados
            ]);

        } catch (\Exception $e) {
            Log::error(' Error en procesarTelasDelFormulario()', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Guardar información de Tela, Color y Referencia de una Prenda en Cotización de Logo
     * 
     * Esperado: POST /cotizaciones/{cotizacion_id}/logo/telas-prenda
     * Con: logo_cotizacion_id, prenda_cot_id, tela, color, ref, imagen (archivo)
     */
    public function guardarTelaPrenda(Request $request, $cotizacionId)
    {
        return DB::transaction(function () use ($request, $cotizacionId) {
            try {
                Log::info(' Iniciando guardado de Tela, Color, Ref para prenda', [
                    'cotizacion_id' => $cotizacionId,
                    'logo_cotizacion_id' => $request->input('logo_cotizacion_id'),
                    'prenda_cot_id' => $request->input('prenda_cot_id'),
                ]);

                // Validar datos requeridos
                $request->validate([
                    'logo_cotizacion_id' => 'required|integer|exists:logo_cotizaciones,id',
                    'prenda_cot_id' => 'required|integer|exists:prendas_cot,id',
                    'tela' => 'nullable|string|max:255',
                    'color' => 'nullable|string|max:255',
                    'ref' => 'nullable|string|max:255',
                    'imagen' => 'nullable|image|mimes:jpeg,png,webp,jpg|max:5120', // Max 5MB
                ]);

                $logoCotizacionId = $request->input('logo_cotizacion_id');
                $prendaCotId = $request->input('prenda_cot_id');
                $tela = $request->input('tela');
                $color = $request->input('color');
                $ref = $request->input('ref');

                // Procesar imagen si existe
                $rutaImagen = null;
                if ($request->hasFile('imagen')) {
                    $archivo = $request->file('imagen');
                    
                    // Crear directorio de almacenamiento: storage/app/public/cotizaciones/{logo_id}/telas/
                    $directorioTelas = "cotizaciones/{$logoCotizacionId}/telas";
                    
                    // Generar nombre único para la imagen
                    $nombreImagen = time() . '_' . uniqid() . '.' . $archivo->extension();
                    
                    // Almacenar imagen
                    $rutaImagen = Storage::disk('public')->putFileAs(
                        $directorioTelas,
                        $archivo,
                        $nombreImagen
                    );

                    // Convertir a URL completa para usarla en el frontend
                    $rutaImagen = Storage::url($rutaImagen);

                    Log::info('🖼️ Imagen de tela almacenada', [
                        'ruta' => $rutaImagen,
                        'tamaño' => $archivo->getSize(),
                    ]);
                }

                // Guardar registro en la tabla
                $telasPrenda = \App\Models\LogoCotizacionTelasPrenda::create([
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'prenda_cot_id' => $prendaCotId,
                    'tela' => $tela,
                    'color' => $color,
                    'ref' => $ref,
                    'img' => $rutaImagen,
                ]);

                Log::info(' Tela, Color y Ref guardados exitosamente', [
                    'id' => $telasPrenda->id,
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'prenda_cot_id' => $prendaCotId,
                    'tela' => $tela,
                    'color' => $color,
                    'ref' => $ref,
                    'imagen' => $rutaImagen ? 'SI' : 'NO',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Información de tela, color y referencia guardada correctamente',
                    'data' => [
                        'id' => $telasPrenda->id,
                        'logo_cotizacion_id' => $telasPrenda->logo_cotizacion_id,
                        'prenda_cot_id' => $telasPrenda->prenda_cot_id,
                        'tela' => $telasPrenda->tela,
                        'color' => $telasPrenda->color,
                        'ref' => $telasPrenda->ref,
                        'img' => $telasPrenda->img,
                        'url_imagen' => $telasPrenda->url_imagen, // Usar el accessor para la URL pública
                        'created_at' => $telasPrenda->created_at,
                    ]
                ], 201);

            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::warning(' Validación fallida en guardarTelaPrenda', [
                    'errores' => $e->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $e->errors()
                ], 422);
            } catch (\Exception $e) {
                Log::error(' Error al guardar tela, color y referencia', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la información: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Obtener telas de prendas de una cotización de logo
     * 
     * GET /cotizaciones/{cotizacion_id}/logo/telas-prenda
     */
    public function obtenerTelasPrenda($cotizacionId)
    {
        try {
            // Obtener la cotización
            $cotizacion = Cotizacion::findOrFail($cotizacionId);
            
            if (!$cotizacion->logoCotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cotización no tiene información de logo'
                ], 404);
            }

            // Obtener todas las telas de las prendas
            $telasPrendas = \App\Models\LogoCotizacionTelasPrenda::where(
                'logo_cotizacion_id',
                $cotizacion->logoCotizacion->id
            )
            ->with(['logoCotizacion', 'prenda'])
            ->get()
            ->map(function ($tela) {
                return [
                    'id' => $tela->id,
                    'logo_cotizacion_id' => $tela->logo_cotizacion_id,
                    'prenda_cot_id' => $tela->prenda_cot_id,
                    'prenda_nombre' => $tela->prenda?->nombre_producto ?? 'Desconocida',
                    'tela' => $tela->tela,
                    'color' => $tela->color,
                    'ref' => $tela->ref,
                    'img' => $tela->img,
                    'url_imagen' => $tela->url_imagen,
                    'created_at' => $tela->created_at,
                ];
            });

            Log::info(' Telas de prendas obtenidas', [
                'cotizacion_id' => $cotizacionId,
                'total' => $telasPrendas->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $telasPrendas,
                'total' => $telasPrendas->count()
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al obtener telas de prendas', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las telas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar tela de prenda
     * 
     * DELETE /cotizaciones/{cotizacion_id}/logo/telas-prenda/{id}
     */
    public function eliminarTelaPrenda($cotizacionId, $telaId)
    {
        try {
            $tela = \App\Models\LogoCotizacionTelasPrenda::findOrFail($telaId);

            // Eliminar imagen si existe
            if ($tela->img && Storage::disk('public')->exists($tela->img)) {
                Storage::disk('public')->delete($tela->img);
                Log::info('🗑️ Imagen de tela eliminada', ['ruta' => $tela->img]);
            }

            $tela->delete();

            Log::info(' Tela eliminada correctamente', [
                'id' => $telaId,
                'cotizacion_id' => $cotizacionId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tela eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al eliminar tela', [
                'id' => $telaId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tela: ' . $e->getMessage()
            ], 500);
        }
    }
}
