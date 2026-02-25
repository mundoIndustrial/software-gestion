<?php

namespace App\Http\Controllers\Api_temp;

use App\Http\Controllers\Controller;
use App\Application\Actions\Procesos\CrearProcesoAction;
use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;
use App\Domain\Procesos\Repositories\ProcesoPrendaImagenRepository;
use App\Domain\Procesos\Repositories\TipoProcesoRepository;
use App\Domain\Procesos\Services\AprobarProcesoPrendaService;
use App\Domain\Procesos\Services\RechazarProcesoPrendaService;
use App\Domain\Procesos\Services\SubirImagenProcesoService;
use App\DTOs\CrearProcesoPrendaDTO;
use App\Models\PedidoAuditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcesosController extends Controller
{
    public function __construct(
        private CrearProcesoAction $crearProcesoAction,
        private AprobarProcesoPrendaService $aprobarService,
        private RechazarProcesoPrendaService $rechazarService,
        private SubirImagenProcesoService $subirImagenService,
        private ProcesoPrendaDetalleRepository $procesoRepository,
        private ProcesoPrendaImagenRepository $imagenRepository,
        private TipoProcesoRepository $tipoProcesoRepository,
    ) {}

    /**
     * Obtener tipos de procesos disponibles
     * GET /api/procesos/tipos
     */
    public function tipos()
    {
        try {
            $tipos = $this->tipoProcesoRepository->obtenerActivos();

            return response()->json([
                'success' => true,
                'data' => array_map(fn($tipo) => $tipo->toArray(), $tipos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de procesos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener procesos de una prenda
     * GET /api/prendas/{id}/procesos
     */
    public function obtenerPorPrenda($prendaId)
    {
        try {
            $procesos = $this->procesoRepository->obtenerPorPrenda($prendaId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($p) => $p->toArray(), $procesos),
                'total' => count($procesos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener procesos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear nuevo proceso
     * POST /api/prendas/{id}/procesos
     */
    public function crear(Request $request, $prendaId)
    {
        try {
            // Validar request
            $validated = $request->validate([
                'tipo_proceso_id' => [
                    'required',
                    'integer',
                    Rule::exists('tipos_procesos', 'id')->where('activo', true),
                ],
                'ubicaciones' => 'required|array|min:1',
                'ubicaciones.*' => 'string|min:1|max:100',
                'observaciones' => 'nullable|string|max:1000',
                'tallas' => 'nullable|json', // Estructura relacional: {DAMA: {S: 10}, CABALLERO: {32: 5}}
                'imagen' => 'nullable|string', // base64
                'datos_adicionales' => 'nullable|array',
            ]);

            // Crear DTO
            $dto = CrearProcesoPrendaDTO::fromRequest([
                ...$validated,
                'imagen' => $request->input('imagen'),
            ], $prendaId);

            // Ejecutar action
            $proceso = $this->crearProcesoAction->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => 'Proceso creado correctamente',
                'data' => $proceso->toArray(),
            ], 201);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar proceso
     * PUT /api/procesos/{id}
     */
    public function actualizar(Request $request, $procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            if (!$proceso->puedeSerEditado()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este proceso no puede ser editado en estado: ' . $proceso->getEstado(),
                ], 422);
            }

            // Validar request
            $validated = $request->validate([
                'ubicaciones' => 'nullable|array|min:1',
                'ubicaciones.*' => 'string|min:1|max:100',
                'observaciones' => 'nullable|string|max:1000',
                'tallas' => 'nullable|json', // Estructura relacional: {DAMA: {S: 10}, CABALLERO: {32: 5}}
                'datos_adicionales' => 'nullable|array',
            ]);

            // Actualizar entity
            if (isset($validated['ubicaciones'])) {
                $proceso->setUbicaciones($validated['ubicaciones']);
            }
            if (isset($validated['observaciones'])) {
                $proceso->setObservaciones($validated['observaciones']);
            }
            if (isset($validated['tallas'])) {
                $proceso->setTallasRelacional(json_decode($validated['tallas'], true));
            }
            if (isset($validated['datos_adicionales'])) {
                $proceso->setDatosAdicionales($validated['datos_adicionales']);
            }

            // Guardar
            $procesoActualizado = $this->procesoRepository->actualizar($proceso);

            // Guardar tallas en tabla relacional (NUEVO MODELO)
            if (isset($validated['tallas'])) {
                $tallasRelacional = json_decode($validated['tallas'], true);
                
                // Usar el método del repositorio para guardar en tabla relacional
                $this->procesoRepository->guardarTallasRelacional($procesoActualizado->getId(), $tallasRelacional);
            }

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente',
                'data' => $procesoActualizado->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar proceso
     * DELETE /api/procesos/{id}
     */
    public function eliminar($procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            if (!$proceso->puedeSerEditado()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un proceso en estado: ' . $proceso->getEstado(),
                ], 422);
            }

            $this->procesoRepository->eliminar($procesoId);

            return response()->json([
                'success' => true,
                'message' => 'Proceso eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprobar proceso
     * POST /api/procesos/{id}/aprobar
     */
    public function aprobar($procesoId)
    {
        try {
            $usuarioId = auth()->id();

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $procesoAprobado = $this->aprobarService->ejecutar($procesoId, $usuarioId);

            return response()->json([
                'success' => true,
                'message' => 'Proceso aprobado correctamente',
                'data' => $procesoAprobado->toArray(),
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rechazar proceso
     * POST /api/procesos/{id}/rechazar
     */
    public function rechazar(Request $request, $procesoId)
    {
        try {
            $request->validate([
                'motivo' => 'required|string|min:5|max:500',
            ]);

            $procesoRechazado = $this->rechazarService->ejecutar($procesoId, $request->motivo);

            return response()->json([
                'success' => true,
                'message' => 'Proceso rechazado correctamente',
                'data' => $procesoRechazado->toArray(),
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener imágenes de un proceso
     * GET /api/procesos/{id}/imagenes
     */
    public function obtenerImagenes($procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);
            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            $imagenes = $this->imagenRepository->obtenerPorProceso($procesoId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($img) => $img->toArray(), $imagenes),
                'total' => count($imagenes),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener imágenes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir imagen a un proceso
     * POST /api/procesos/{id}/imagenes
     */
    public function subirImagen(Request $request, $procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);
            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            // Obtener el pedido_id desde la relación prenda → pedido
            $prendaPedidoId = $proceso->getPrendaPedidoId();
            $prenda = \App\Models\PrendaPedido::find($prendaPedidoId);
            if (!$prenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prenda no encontrada',
                ], 404);
            }
            
            $pedidoId = $prenda->pedido_produccion_id;

            // Validar request
            $validated = $request->validate([
                'imagen' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB
                'descripcion' => 'nullable|string|max:255',
                'es_principal' => 'nullable|boolean',
            ]);

            // Procesar archivo
            $archivo = $request->file('imagen');
            $imagenBinaria = file_get_contents($archivo->getRealPath());
            $tipoMime = $archivo->getMimeType();
            $nombreOriginal = $archivo->getClientOriginalName();

            // Generar nombre de archivo
            $nombreArchivo = "proceso-{$procesoId}-" . time() . "." . $archivo->getClientOriginalExtension();

            // Guardar archivo en estructura pedidos/{pedido_id}/procesos/
            $ruta = Storage::disk('public')->put("pedidos/{$pedidoId}/procesos/{$nombreArchivo}", $imagenBinaria);

            // Calcular hash MD5 para detectar duplicados
            $hashMd5 = md5($imagenBinaria);

            // Obtener dimensiones de imagen
            $imagenInfo = getimagesizefromstring($imagenBinaria);
            $ancho = $imagenInfo[0] ?? 0;
            $alto = $imagenInfo[1] ?? 0;

            // Usar domain service para guardar imagen
            $imagen = $this->subirImagenService->ejecutar(
                procesoPrendaDetalleId: $procesoId,
                rutaArchivo: "pedidos/{$pedidoId}/procesos/{$nombreArchivo}",
                nombreOriginal: $nombreOriginal,
                tipoMime: $tipoMime,
                tamaño: strlen($imagenBinaria),
                ancho: $ancho,
                alto: $alto,
                hashMd5: $hashMd5,
                descripcion: $validated['descripcion'] ?? null,
                esPrincipal: $validated['es_principal'] ?? false
            );

            // Registrar auditoría
            PedidoAuditoria::registrarCambio(
                $pedidoId,
                'AGREGADA_IMAGEN_PROCESO',
                auth()->id(),
                json_encode([
                    'ruta_archivo' => "pedidos/{$pedidoId}/procesos/{$nombreArchivo}",
                    'nombre_original' => $nombreOriginal,
                    'es_principal' => $validated['es_principal'] ?? false
                ]),
                null,
                "Imagen de proceso agregada: {$nombreOriginal}",
                null,
                $procesoId,
                $imagen->getId() ?? null,
                "pedidos/{$pedidoId}/procesos/{$nombreArchivo}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => $imagen->toArray(),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al subir imagen de proceso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imagen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar imagen como principal
     * POST /api/procesos/{id}/imagenes/{imagenId}/principal
     */
    public function marcarComoPrincipal($procesoId, $imagenId)
    {
        try {
            $imagen = $this->imagenRepository->obtenerPorId($imagenId);
            if (!$imagen || $imagen->getProcesoPrendaDetalleId() !== (int)$procesoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada',
                ], 404);
            }

            // Marcar otra como principal
            $this->imagenRepository->marcarOtraComoPrincipal($procesoId, $imagenId);

            return response()->json([
                'success' => true,
                'message' => 'Imagen marcada como principal',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar imagen como principal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar imagen de un proceso
     * DELETE /api/procesos/{id}/imagenes/{imagenId}
     */
    public function eliminarImagen($procesoId, $imagenId)
    {
        try {
            $imagen = $this->imagenRepository->obtenerPorId($imagenId);
            if (!$imagen || $imagen->getProcesoPrendaDetalleId() !== (int)$procesoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada',
                ], 404);
            }

            // Eliminar archivo físico
            $ruta = $imagen->getRuta();
            if (Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }

            // Eliminar registro de base de datos
            $this->imagenRepository->eliminar($imagenId);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar/Desactivar recibo de proceso
     * POST /api/procesos/{id}/activar-recibo
     */
    public function activarRecibo(Request $request, $procesoId)
    {
        \Log::info('[ProcesosController::activarRecibo] Iniciando', [
            'proceso_id' => $procesoId,
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'user' => auth()->user() ? [
                'id' => auth()->user()->id,
                'nombre' => auth()->user()->nombre,
                'email' => auth()->user()->email,
            ] : null,
            'request_data' => $request->all()
        ]);

        // Verificar autenticación
        if (!auth()->check()) {
            \Log::warning('[ProcesosController::activarRecibo] Usuario no autenticado', ['proceso_id' => $procesoId]);
            return response()->json([
                'success' => false,
                'message' => 'Debe estar autenticado para realizar esta acción',
            ], 401);
        }

        // Verificar permisos (solo supervisores pueden activar recibos)
        $usuario = auth()->user();
        \Log::info('[ProcesosController::activarRecibo] Verificando permisos', [
            'usuario_id' => $usuario->id,
            'usuario_nombre' => $usuario->nombre,
            'hasRole_supervisor' => $usuario->hasRole('supervisor'),
            'hasRole_supervisor_pedidos' => $usuario->hasRole('supervisor_pedidos'),
            'all_roles' => $usuario->roles->pluck('nombre')->toArray()
        ]);

        if (!$usuario || (!$usuario->hasRole('supervisor') && !$usuario->hasRole('supervisor_pedidos'))) {
            \Log::warning('[ProcesosController::activarRecibo] Usuario sin permisos', [
                'proceso_id' => $procesoId,
                'user_id' => auth()->id(),
                'roles' => $usuario ? $usuario->roles->pluck('nombre') : []
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción',
            ], 403);
        }

        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);

            if (!$proceso) {
                \Log::warning('[ProcesosController::activarRecibo] Proceso no encontrado', ['proceso_id' => $procesoId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            $request->validate([
                'activar' => 'required|boolean',
            ]);

            $activar = $request->input('activar');

            \Log::info('[ProcesosController::activarRecibo] Datos válidos', [
                'activar' => $activar
            ]);

            $numeroReciboGenerado = null;
            $tipoReciboUsado = null;

            // Actualizar estado
            if ($activar) {
                // Usar el método aprobar() de la entidad
                $proceso->aprobar(auth()->id());

                // Si el proceso no tiene numero_recibo, generarlo ahora (flujo normal)
                $rowProceso = DB::table('pedidos_procesos_prenda_detalles')
                    ->where('id', $procesoId)
                    ->lockForUpdate()
                    ->first();

                $yaTieneNumero = $rowProceso && !empty($rowProceso->numero_recibo);
                if (!$yaTieneNumero) {
                    $prendaPedidoId = (int)$proceso->getPrendaPedidoId();
                    $prenda = \App\Models\PrendaPedido::with('pedido')->find($prendaPedidoId);

                    if (!$prenda || !$prenda->pedido) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No se pudo determinar el pedido de la prenda para generar consecutivo',
                        ], 422);
                    }

                    $pedidoId = (int)$prenda->pedido_produccion_id;

                    $tipoProceso = DB::table('tipos_procesos')->where('id', $proceso->getTipoProcesoId())->first();
                    $tipoRecibo = strtoupper(trim($tipoProceso->nombre ?? ''));
                    if ($tipoRecibo === '') {
                        return response()->json([
                            'success' => false,
                            'message' => 'No se pudo determinar el tipo de recibo para generar consecutivo',
                        ], 422);
                    }

                    $consecutivoPedido = DB::table('consecutivos_recibos_pedidos')
                        ->where('pedido_produccion_id', $pedidoId)
                        ->where('prenda_id', $prendaPedidoId)
                        ->where('tipo_recibo', $tipoRecibo)
                        ->where('activo', 1)
                        ->lockForUpdate()
                        ->first();

                    if (!$consecutivoPedido) {
                        $registroMaestro = DB::table('consecutivos_recibos')
                            ->where('tipo_recibo', $tipoRecibo)
                            ->where('activo', 1)
                            ->lockForUpdate()
                            ->first();

                        if (!$registroMaestro) {
                            return response()->json([
                                'success' => false,
                                'message' => "No existe consecutivo maestro para tipo {$tipoRecibo}",
                            ], 422);
                        }

                        $nuevoConsecutivo = ((int)$registroMaestro->consecutivo_actual) + 1;
                        DB::table('consecutivos_recibos')
                            ->where('id', $registroMaestro->id)
                            ->update([
                                'consecutivo_actual' => $nuevoConsecutivo,
                                'updated_at' => now(),
                            ]);

                        DB::table('consecutivos_recibos_pedidos')->insert([
                            'pedido_produccion_id' => $pedidoId,
                            'prenda_id' => $prendaPedidoId,
                            'tipo_recibo' => $tipoRecibo,
                            'consecutivo_inicial' => $nuevoConsecutivo,
                            'consecutivo_actual' => $nuevoConsecutivo,
                            'activo' => 1,
                            'estado' => 'PENDIENTE_INSUMOS',
                            'notas' => "Generado por activación normal para pedido #{$prenda->pedido->numero_pedido}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $consecutivoPedido = DB::table('consecutivos_recibos_pedidos')
                            ->where('pedido_produccion_id', $pedidoId)
                            ->where('prenda_id', $prendaPedidoId)
                            ->where('tipo_recibo', $tipoRecibo)
                            ->where('activo', 1)
                            ->lockForUpdate()
                            ->first();
                    }

                    $numeroRecibo = (int)$consecutivoPedido->consecutivo_actual;

                    DB::table('pedidos_procesos_prenda_detalles')
                        ->where('id', $procesoId)
                        ->update([
                            'numero_recibo' => $numeroRecibo,
                            'tipo_recibo' => $tipoRecibo,
                            'updated_at' => now(),
                        ]);

                    DB::table('consecutivos_recibos_pedidos')
                        ->where('id', $consecutivoPedido->id)
                        ->update([
                            'consecutivo_actual' => $numeroRecibo + 1,
                            'updated_at' => now(),
                        ]);

                    $numeroReciboGenerado = $numeroRecibo;
                    $tipoReciboUsado = $tipoRecibo;
                }
            } else {
                // Para desactivar, necesitamos volver a PENDIENTE manualmente
                // ya que no hay un método específico para eso
                $reflection = new \ReflectionClass($proceso);
                $estadoProperty = $reflection->getProperty('estado');
                $estadoProperty->setAccessible(true);
                $estadoProperty->setValue($proceso, 'PENDIENTE');
                
                $fechaProperty = $reflection->getProperty('fechaAprobacion');
                $fechaProperty->setAccessible(true);
                $fechaProperty->setValue($proceso, null);
                
                $aprobadoProperty = $reflection->getProperty('aprobadoPor');
                $aprobadoProperty->setAccessible(true);
                $aprobadoProperty->setValue($proceso, null);
                
                // MANTENER numero_recibo existente - NO ELIMINAR
            }

            $procesoActualizado = $this->procesoRepository->actualizar($proceso);

            \Log::info('[ProcesosController::activarRecibo] Proceso actualizado exitosamente', [
                'proceso_id' => $procesoId,
                'estado' => $procesoActualizado->getEstado(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $activar ? 'Recibo activado correctamente' : 'Recibo desactivado correctamente',
                'data' => array_merge($procesoActualizado->toArray(), [
                    'numero_recibo_generado' => $numeroReciboGenerado,
                    'tipo_recibo_usado' => $tipoReciboUsado,
                ]),
            ]);

        } catch (\Exception $e) {
            \Log::error('[ProcesosController::activarRecibo] Error', [
                'proceso_id' => $procesoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado del recibo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener tallas disponibles para activar recibo (por proceso)
     * GET /procesos/{id}/tallas-disponibles
     */
    public function tallasDisponibles(Request $request, $procesoId)
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe estar autenticado para realizar esta acción',
                ], 401);
            }

            $usuario = auth()->user();
            if (!$usuario || (!$usuario->hasRole('supervisor') && !$usuario->hasRole('supervisor_pedidos'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para realizar esta acción',
                ], 403);
            }

            $proceso = $this->procesoRepository->obtenerPorId($procesoId);
            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            $prendaPedidoId = $proceso->getPrendaPedidoId();
            $tallas = DB::table('pedidos_procesos_prenda_tallas')
                ->select('genero', 'talla', 'cantidad')
                ->where('proceso_prenda_detalle_id', $procesoId)
                ->where('cantidad', '>', 0)
                ->orderBy('genero')
                ->orderBy('talla')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'proceso_id' => (int)$procesoId,
                    'prenda_pedido_id' => (int)$prendaPedidoId,
                    'tallas' => $tallas,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ProcesosController::tallasDisponibles] Error', [
                'proceso_id' => $procesoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tallas disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar recibo con selección de tallas/cantidades (split)
     * POST /procesos/{id}/activar-recibo-con-tallas
     * Body: { tallas: [{genero,talla,cantidad}] }
     */
    public function activarReciboConTallas(Request $request, $procesoId)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Debe estar autenticado para realizar esta acción',
            ], 401);
        }

        $usuario = auth()->user();
        if (!$usuario || (!$usuario->hasRole('supervisor') && !$usuario->hasRole('supervisor_pedidos'))) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción',
            ], 403);
        }

        $request->validate([
            'tallas' => 'required|array|min:1',
            'tallas.*.genero' => 'required|string|max:20',
            'tallas.*.talla' => 'required|string|max:20',
            'tallas.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request, $procesoId, $usuario) {
                $proceso = $this->procesoRepository->obtenerPorId($procesoId);
                if (!$proceso) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Proceso no encontrado',
                    ], 404);
                }

                $prendaPedidoId = (int)$proceso->getPrendaPedidoId();
                $prenda = \App\Models\PrendaPedido::with('pedido')->find($prendaPedidoId);
                if (!$prenda) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Prenda no encontrada',
                    ], 404);
                }

                $pedidoId = (int)$prenda->pedido_produccion_id;

                $tallasSeleccionadas = $request->input('tallas');

                // 1) Validar máximos disponibles contra tabla relacional del proceso
                $disponibles = DB::table('pedidos_procesos_prenda_tallas')
                    ->where('proceso_prenda_detalle_id', $procesoId)
                    ->get()
                    ->keyBy(fn($r) => strtoupper(trim($r->genero)) . '|' . strtoupper(trim($r->talla)));

                foreach ($tallasSeleccionadas as $item) {
                    $key = strtoupper(trim($item['genero'])) . '|' . strtoupper(trim($item['talla']));
                    $cant = (int)$item['cantidad'];
                    $row = $disponibles->get($key);
                    $max = $row ? (int)$row->cantidad : 0;
                    if ($cant < 1 || $cant > $max) {
                        return response()->json([
                            'success' => false,
                            'message' => "Cantidad inválida para {$item['genero']} {$item['talla']}. Máximo: {$max}",
                        ], 422);
                    }
                }

                // 2) Crear un NUEVO proceso (copia) con mismas propiedades, y tallas seleccionadas
                // Para el consecutivo, este flujo requiere que ya exista un registro en consecutivos_recibos_pedidos
                // para (pedido_id, prenda_id, tipo_recibo). Si no existe, se crea asignando el siguiente consecutivo maestro.

                // Determinar tipo_recibo: usar nombre del tipo_proceso asociado
                $tipoProceso = DB::table('tipos_procesos')->where('id', $proceso->getTipoProcesoId())->first();
                $tipoRecibo = strtoupper(trim($tipoProceso->nombre ?? ''));
                if ($tipoRecibo === '') {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo determinar el tipo de recibo',
                    ], 422);
                }

                // Obtener/crear consecutivo del pedido/prenda/tipo
                $consecutivoPedido = DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('prenda_id', $prendaPedidoId)
                    ->where('tipo_recibo', $tipoRecibo)
                    ->where('activo', 1)
                    ->lockForUpdate()
                    ->first();

                if (!$consecutivoPedido) {
                    $registroMaestro = DB::table('consecutivos_recibos')
                        ->where('tipo_recibo', $tipoRecibo)
                        ->where('activo', 1)
                        ->lockForUpdate()
                        ->first();

                    if (!$registroMaestro) {
                        return response()->json([
                            'success' => false,
                            'message' => "No existe consecutivo maestro para tipo {$tipoRecibo}",
                        ], 422);
                    }

                    $nuevoConsecutivo = ((int)$registroMaestro->consecutivo_actual) + 1;
                    DB::table('consecutivos_recibos')
                        ->where('id', $registroMaestro->id)
                        ->update([
                            'consecutivo_actual' => $nuevoConsecutivo,
                            'updated_at' => now(),
                        ]);

                    DB::table('consecutivos_recibos_pedidos')->insert([
                        'pedido_produccion_id' => $pedidoId,
                        'prenda_id' => $prendaPedidoId,
                        'tipo_recibo' => $tipoRecibo,
                        'consecutivo_inicial' => $nuevoConsecutivo,
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'activo' => 1,
                        'estado' => 'PENDIENTE_INSUMOS',
                        'notas' => "Generado por activación con tallas para pedido #{$prenda->pedido->numero_pedido}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $consecutivoPedido = DB::table('consecutivos_recibos_pedidos')
                        ->where('pedido_produccion_id', $pedidoId)
                        ->where('prenda_id', $prendaPedidoId)
                        ->where('tipo_recibo', $tipoRecibo)
                        ->where('activo', 1)
                        ->lockForUpdate()
                        ->first();
                }

                $numeroRecibo = (int)$consecutivoPedido->consecutivo_actual;

                // Insertar nuevo proceso en tabla principal (clon simple con estado APROBADO)
                $nuevoProcesoId = DB::table('pedidos_procesos_prenda_detalles')->insertGetId([
                    'prenda_pedido_id' => $prendaPedidoId,
                    'tipo_proceso_id' => $proceso->getTipoProcesoId(),
                    'ubicaciones' => json_encode($proceso->getUbicaciones() ?? []),
                    'observaciones' => $proceso->getObservaciones(),
                    'estado' => 'APROBADO',
                    'fecha_aprobacion' => now(),
                    'numero_recibo' => $numeroRecibo,
                    'tipo_recibo' => $tipoRecibo,
                    'aprobado_por' => $usuario->id,
                    'datos_adicionales' => json_encode($proceso->getDatosAdicionales() ?? []),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 3) Guardar tallas seleccionadas para el nuevo proceso y descontar del original
                foreach ($tallasSeleccionadas as $item) {
                    $genero = strtoupper(trim($item['genero']));
                    $talla = strtoupper(trim($item['talla']));
                    $cantidad = (int)$item['cantidad'];

                    DB::table('pedidos_procesos_prenda_tallas')->insert([
                        'proceso_prenda_detalle_id' => $nuevoProcesoId,
                        'genero' => $genero,
                        'talla' => $talla,
                        'cantidad' => $cantidad,
                        'es_sobremedida' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('pedidos_procesos_prenda_tallas')
                        ->where('proceso_prenda_detalle_id', $procesoId)
                        ->whereRaw('UPPER(TRIM(genero)) = ?', [$genero])
                        ->whereRaw('UPPER(TRIM(talla)) = ?', [$talla])
                        ->update([
                            'cantidad' => DB::raw('GREATEST(cantidad - ' . $cantidad . ', 0)'),
                            'updated_at' => now(),
                        ]);
                }

                // 4) Incrementar consecutivo actual para el siguiente recibo
                DB::table('consecutivos_recibos_pedidos')
                    ->where('id', $consecutivoPedido->id)
                    ->update([
                        'consecutivo_actual' => $numeroRecibo + 1,
                        'updated_at' => now(),
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Recibo activado correctamente',
                    'data' => [
                        'nuevo_proceso_id' => (int)$nuevoProcesoId,
                        'numero_recibo' => $numeroRecibo,
                        'tipo_recibo' => $tipoRecibo,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('[ProcesosController::activarReciboConTallas] Error', [
                'proceso_id' => $procesoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al activar recibo con tallas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
