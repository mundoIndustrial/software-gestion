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
                'logoCotizacion.fotos'
            ])->findOrFail($id);

            // Verificar que sea un borrador y del asesor autenticado
            if ($cotizacion->es_borrador !== true || $cotizacion->asesor_id !== Auth::id()) {
                abort(403, 'No tienes permiso para editar este borrador');
            }

            Log::info('📥 Cargando borrador para edición', [
                'cotizacion_id' => $id,
                'cliente_id' => $cotizacion->cliente_id,
                'cliente_nombre' => $cotizacion->cliente ? $cotizacion->cliente->nombre : 'NULL',
                'tiene_cliente' => $cotizacion->cliente ? 'SI' : 'NO',
                'tiene_logo_cotizacion' => $cotizacion->logoCotizacion ? 'SI' : 'NO'
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
        
        Log::info('📤 Acción detectada:', ['action' => $action, 'es_envio' => $esEnvio]);
        
        // Ejecutar transacción para actualizar datos
        $resultado = DB::transaction(function () use ($request, $id, $esEnvio) {
            try {
                
                Log::info('🔄 CotizacionBordadoController@updateBorrador - Actualizando borrador', [
                    'cotizacion_id' => $id,
                    'id_type' => gettype($id),
                    'method' => $request->method(),
                    'es_envio' => $esEnvio
                ]);

                // Verificar que la cotización existe y es un borrador del asesor
                $cotizacion = Cotizacion::findOrFail($id);
                if ($cotizacion->es_borrador !== true || $cotizacion->asesor_id !== Auth::id()) {
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
                    $usuarioId = Auth::id();
                    $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                    Log::info(' Número generado para envío', ['numero' => $numeroCotizacion, 'cotizacion_id' => $id]);
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
        
        $mensaje = $esEnvio 
            ? 'Cotización enviada - Número: ' . ($resultado['numero_cotizacion'] ?? 'N/A')
            : 'Borrador actualizado exitosamente';
        
        $redirect = $esEnvio 
            ? route('asesores.cotizaciones.index')
            : route('asesores.cotizaciones-bordado.create', ['editar' => $id]);
        
        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $resultado,
            'redirect' => $redirect
        ]);
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
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización enviada exitosamente');
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
            Log::info('🔍 METADATA en Request->all():', [
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
                    
                    Log::info('🎨 Logo compartido encontrado', [
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
            
            Log::info('🎨 Metadatos de logos compartidos encontrados:', [
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
                        Log::info('🎨 Guardando logo compartido', [
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
                        
                        Log::info('✅ Logo compartido guardado UNA SOLA VEZ', [
                            'clave' => $clave,
                            'ruta' => $rutasImagen['ruta_webp'],
                            'tecnicas' => implode(' + ', $tecnicasCompartidas)
                        ]);
                    } catch (\Exception $e) {
                        Log::error('❌ Error guardando logo compartido', [
                            'clave' => $clave,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            Log::info('✅ TODOS los logos compartidos guardados', [
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
}
