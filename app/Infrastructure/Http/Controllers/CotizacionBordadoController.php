<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\NumeroSecuencia;

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
                'tiene_logo_cotizacion' => $cotizacion->logoCotizacion ? 'SI' : 'NO',
                'tecnicas' => $cotizacion->logoCotizacion ? $cotizacion->logoCotizacion->tecnicas : 'N/A',
                'descripcion' => $cotizacion->logoCotizacion ? $cotizacion->logoCotizacion->descripcion : 'N/A'
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
                Log::warning('⚠️ Imagen no encontrada:', ['foto_id' => $fotoId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ], 404);
            }
            
            // Borrar la imagen
            $foto->forceDelete();
            
            Log::info('✅ Imagen borrada exitosamente:', ['foto_id' => $fotoId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al borrar imagen:', ['error' => $e->getMessage()]);
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

                Log::info('👤 Cliente recibido en updateBorrador', [
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
                    Log::info('👤 Cliente creado o encontrado', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
                }

                // Si es envío, generar número y cambiar estado
                $numeroCotizacion = null;
                if ($esEnvio) {
                    $usuarioId = Auth::id();
                    $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                    Log::info('✅ Número generado para envío', ['numero' => $numeroCotizacion, 'cotizacion_id' => $id]);
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
                    Log::info('✅ Cotización actualizada', ['cotizacion_id' => $id, 'datos' => $datosActualizar]);
                } else {
                    Log::warning('⚠️ No se actualizó cotización - sin datos', ['cotizacion_id' => $id]);
                }

                // Actualizar o crear logo_cotizacion
                // NOTA: El campo 'imagenes' en logo_cotizaciones no se usa realmente,
                // las imágenes se almacenan en la tabla logo_fotos_cot
                
                // Procesar técnicas (pueden venir como JSON string desde FormData o como array desde JSON)
                $tecnicas = $request->input('tecnicas', '[]');
                Log::info('🔍 Técnicas RAW recibidas:', ['tecnicas_raw' => $tecnicas, 'type' => gettype($tecnicas)]);
                
                if (is_string($tecnicas)) {
                    $tecnicas = json_decode($tecnicas, true) ?? [];
                }
                
                // Procesar ubicaciones (pueden venir como JSON string desde FormData)
                $ubicaciones = $request->input('ubicaciones', '[]');
                if (is_string($ubicaciones)) {
                    $ubicaciones = json_decode($ubicaciones, true) ?? [];
                }
                
                // Procesar observaciones generales (pueden venir como JSON string desde FormData)
                $observacionesGenerales = $request->input('observaciones_generales', '[]');
                if (is_string($observacionesGenerales)) {
                    $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
                }
                
                $descripcion = $request->input('descripcion', '');
                $observacionesTecnicas = $request->input('observaciones_tecnicas', '');
                
                Log::info('📝 Datos recibidos en updateBorrador:', [
                    'descripcion' => $descripcion,
                    'observaciones_tecnicas' => $observacionesTecnicas,
                    'tecnicas' => $tecnicas,
                    'tecnicas_type' => gettype($tecnicas),
                    'ubicaciones' => $ubicaciones,
                    'observaciones_generales' => $observacionesGenerales
                ]);
                
                // Usar modelo LogoCotizacion para que aplique los casts correctamente
                // Busca por cotizacion_id y actualiza o crea si no existe
                // IMPORTANTE: Solo actualizar campos que tienen valor para no borrar datos existentes
                $datosActualizar = [];
                
                // Solo actualizar descripción si tiene valor
                if (!empty($descripcion)) {
                    $datosActualizar['descripcion'] = $descripcion;
                }
                
                // Solo actualizar técnicas si no está vacío
                if (!empty($tecnicas)) {
                    $datosActualizar['tecnicas'] = $tecnicas;
                }
                
                // Solo actualizar observaciones técnicas si tiene valor
                if (!empty($observacionesTecnicas)) {
                    $datosActualizar['observaciones_tecnicas'] = $observacionesTecnicas;
                }
                
                // Solo actualizar ubicaciones si no está vacío
                if (!empty($ubicaciones)) {
                    $datosActualizar['ubicaciones'] = $ubicaciones;
                }
                
                // Solo actualizar observaciones generales si no está vacío
                if (!empty($observacionesGenerales)) {
                    $datosActualizar['observaciones_generales'] = $observacionesGenerales;
                }
                
                // Agregar tipo_venta_bordado si está disponible
                $tipoVentaBordado = $request->input('tipo_venta_bordado') ?? $request->input('tipo_venta');
                if (!empty($tipoVentaBordado)) {
                    $datosActualizar['tipo_venta'] = $tipoVentaBordado;
                }
                
                $logoCotizacion = \App\Models\LogoCotizacion::updateOrCreate(
                    ['cotizacion_id' => $id],  // Condición de búsqueda
                    $datosActualizar  // Solo actualizar campos con valor
                );
                
                Log::info('✅ logo_cotizaciones actualizado/creado', [
                    'cotizacion_id' => $id,
                    'logo_id' => $logoCotizacion->id,
                    'descripcion' => $descripcion,
                    'tecnicas_enviadas' => $tecnicas,
                    'tecnicas_count' => count($tecnicas),
                    'tecnicas_guardadas_en_bd' => $logoCotizacion->tecnicas,
                    'accion' => 'updateOrCreate'
                ]);
                
                // Recargar desde BD para verificar
                $logoCotizacionRecargado = \App\Models\LogoCotizacion::find($logoCotizacion->id);
                Log::info('🔍 Verificación post-guardado:', [
                    'tecnicas_en_bd' => $logoCotizacionRecargado->tecnicas,
                    'tecnicas_raw' => DB::table('logo_cotizaciones')->where('id', $logoCotizacion->id)->first()->tecnicas ?? 'NULL'
                ]);

                // Borrar imágenes si se especificaron
                // NOTA: El borrado de imágenes se ejecuta DESPUÉS de la transacción
                // para evitar que se revierte si hay algún error
                
                // Procesar nuevas imágenes si existen
                // Las imágenes existentes en logo_fotos_cot se preservan automáticamente
                // ya que solo agregamos nuevas, no eliminamos las existentes
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

                Log::info('✅ Borrador de bordado actualizado', [
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
                Log::error('❌ Error al actualizar borrador de bordado', [
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

            Log::info('📋 Job de envío encolado', [
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
            Log::info('📊 Imágenes encontradas en BD:', ['count' => $imagenesEnBD->count(), 'ids' => $imagenesEnBD->pluck('id')->toArray()]);
            
            try {
                // Usar modelo Eloquent para borrar
                $borradas = \App\Models\LogoFotoCot::whereIn('id', $idsABorrar)->forceDelete();
                Log::info('✅ Imágenes borradas con forceDelete:', ['filas_borradas' => $borradas, 'ids_borrados' => $idsABorrar]);
                
                // Verificar post-borrado
                $imagenesRestantes = DB::table('logo_fotos_cot')->whereIn('id', $idsABorrar)->count();
                Log::info('✅ Verificación post-borrado:', ['restantes' => $imagenesRestantes]);
            } catch (\Exception $e) {
                Log::error('❌ Error al borrar imágenes DESPUÉS de transacción:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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

                Log::info('👤 Cliente recibido en store', [
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
                    Log::info('👤 Cliente creado o encontrado en store', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
                }

                // Generar número SINCRONICAMENTE si se envía
                $numeroCotizacion = null;
                if (!$esBorrador) {
                    $usuarioId = Auth::id();
                    $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                    Log::info('✅ Número generado sincronicamente', [
                        'numero' => $numeroCotizacion
                    ]);
                }

                // Procesar técnicas (pueden venir como JSON string desde FormData)
                $tecnicas = $request->input('tecnicas', '[]');
                Log::info('🔍 Técnicas recibidas (raw):', ['tecnicas' => $tecnicas, 'tipo' => gettype($tecnicas)]);
                
                if (is_string($tecnicas)) {
                    $tecnicas = json_decode($tecnicas, true) ?? [];
                }
                Log::info('✅ Técnicas procesadas:', ['tecnicas' => $tecnicas]);
                
                // Procesar ubicaciones (pueden venir como JSON string desde FormData)
                $ubicaciones = $request->input('ubicaciones', '[]');
                if (is_string($ubicaciones)) {
                    $ubicaciones = json_decode($ubicaciones, true) ?? [];
                }
                Log::info('✅ Ubicaciones procesadas:', ['ubicaciones' => $ubicaciones]);
                
                // Procesar observaciones generales (pueden venir como JSON string desde FormData)
                $observacionesGenerales = $request->input('observaciones_generales', '[]');
                if (is_string($observacionesGenerales)) {
                    $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
                }
                Log::info('✅ Observaciones generales procesadas:', ['observaciones' => $observacionesGenerales]);
                
                // Buscar el tipo de cotización "Logo/Bordado" dinámicamente
                $tipoBordado = \App\Models\TipoCotizacion::where('codigo', 'L')->first();
                
                if (!$tipoBordado) {
                    Log::error('❌ Tipo de cotización "Logo" (L) no encontrado en tipos_cotizacion');
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

                Log::info('✅ Cotización de Bordado creada en tabla cotizaciones', [
                    'cotizacion_id' => $cotizacion->id,
                    'numero_cotizacion' => $numeroCotizacion,
                ]);

                // Guardar detalles en tabla logo_cotizaciones
                // Usar modelo LogoCotizacion para que aplique los casts correctamente
                $logoCotizacion = \App\Models\LogoCotizacion::updateOrCreate(
                    ['cotizacion_id' => $cotizacion->id],
                    [
                        'descripcion' => $request->input('descripcion', ''),
                        'tecnicas' => $tecnicas,  // El modelo aplicará json_encode automáticamente
                        'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                        'ubicaciones' => $ubicaciones,  // El modelo aplicará json_encode automáticamente
                        'observaciones_generales' => $observacionesGenerales,  // El modelo aplicará json_encode automáticamente
                        'imagenes' => [],  // El modelo aplicará json_encode automáticamente
                        'tipo_venta' => $request->input('tipo_venta_bordado') ?? $request->input('tipo_venta') ?? null,
                    ]
                );

                Log::info('✅ Detalles de bordado guardados en tabla logo_cotizaciones', [
                    'cotizacion_id' => $cotizacion->id,
                    'logo_id' => $logoCotizacion->id,
                    'accion' => 'updateOrCreate'
                ]);

                // Procesar imágenes si existen
                if ($request->hasFile('imagenes') || $request->hasFile('imagenes_bordado')) {
                    $this->procesarImagenesCotizacion($request, $cotizacion->id);
                }

                // Si se envía, aún encolamos el job pero el número YA EXISTE
                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        2 // tipo_cotizacion_id para Logo/Bordado
                    )->onQueue('cotizaciones');

                    Log::info('📋 Job de envío encolado (número ya existe)', [
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
                    'redirect' => route('asesores.cotizaciones.index')
                ], 201);

            } catch (\Exception $e) {
                Log::error('❌ Error al guardar cotización de Bordado', [
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
            Log::warning('⚠️ No se encontró logo_cotizacion para cotización', [
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

        // Procesar archivos del request
        $archivos = $request->file('imagenes') ?? $request->file('imagenes_bordado') ?? [];
        if (!empty($archivos)) {
            foreach ($archivos as $archivo) {
                try {
                    // Guardar imagen original
                    $rutaOriginal = $archivo->store('bordado/cotizaciones/' . $cotizacionId, 'public');

                    // Aquí podrías generar versiones webp y miniatura
                    // Por ahora, usamos la misma ruta para las tres versiones
                    $rutaWebp = $rutaOriginal;
                    $rutaMiniatura = $rutaOriginal;

                    // Obtener dimensiones de la imagen
                    $imageInfo = @getimagesize(storage_path('app/public/' . $rutaOriginal));
                    $ancho = $imageInfo[0] ?? 0;
                    $alto = $imageInfo[1] ?? 0;
                    $tamaño = $archivo->getSize();

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

                    Log::info('✅ Imagen guardada en logo_fotos_cot', [
                        'logo_cotizacion_id' => $logoCotizacionId,
                        'ruta' => $rutaOriginal,
                        'orden' => $orden,
                        'tamaño' => $tamaño,
                        'dimensiones' => "{$ancho}x{$alto}"
                    ]);

                    $orden++;

                } catch (\Exception $e) {
                    Log::error('❌ Error al guardar imagen', [
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
}
