<?php

namespace App\Http\Controllers;

use App\Exceptions\RegistroOrdenPedidoNumberException;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
use App\Services\RegistroOrdenUpdateService;
use App\Services\RegistroOrdenDeletionService;
use App\Services\RegistroOrdenNumberService;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenCacheService;
use App\Services\RegistroOrdenEntregasService;
use App\Services\RegistroOrdenProcessesService;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use App\Services\PrendaCotizacionTemplateService;

class RegistroOrdenController extends Controller
{
    use RegistroOrdenExceptionHandler;

    protected $validationService;
    protected $creationService;
    protected $updateService;
    protected $deletionService;
    protected $numberService;
    protected $prendaService;
    protected $cacheService;
    protected $entregasService;
    protected $processesService;

    public function __construct(
        RegistroOrdenValidationService $validationService,
        RegistroOrdenCreationService $creationService,
        RegistroOrdenUpdateService $updateService,
        RegistroOrdenDeletionService $deletionService,
        RegistroOrdenNumberService $numberService,
        RegistroOrdenPrendaService $prendaService,
        RegistroOrdenCacheService $cacheService,
        RegistroOrdenEntregasService $entregasService,
        RegistroOrdenProcessesService $processesService
    )
    {
        $this->validationService = $validationService;
        $this->creationService = $creationService;
        $this->updateService = $updateService;
        $this->deletionService = $deletionService;
        $this->numberService = $numberService;
        $this->prendaService = $prendaService;
        $this->cacheService = $cacheService;
        $this->entregasService = $entregasService;
        $this->processesService = $processesService;
    }

    public function getNextPedido()
    {
        $pedidoInfo = $this->numberService->getNextPedidoInfo();
        return response()->json($pedidoInfo);
    }

    public function validatePedido(Request $request)
    {
        $request->validate(['pedido' => 'required|integer']);
        
        $pedido = $request->input('pedido');
        $nextInfo = $this->numberService->getNextPedidoInfo();
        $isValid = $this->numberService->isNextExpected($pedido);

        return response()->json([
            'valid' => $isValid,
            'next_pedido' => $nextInfo['next_pedido'],
        ]);
    }

    public function store(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            // Validar datos
            $validatedData = $this->validationService->validateStoreRequest($request);

            // Verificar número consecutivo
            $nextPedido = $this->numberService->getNextNumber();
            
            if (!$request->input('allow_any_pedido', false)) {
                if ($request->pedido != $nextPedido) {
                    throw RegistroOrdenPedidoNumberException::unexpectedNumber(
                        $nextPedido,
                        $request->pedido
                    );
                }
            }

            // Crear orden con todas sus prendas
            $pedido = $this->creationService->createOrder($validatedData);

            // Registrar evento
            $this->creationService->logOrderCreated(
                $pedido->numero_pedido,
                $validatedData['cliente'],
                $validatedData['estado'] ?? 'No iniciado'
            );

            // Broadcast evento
            $this->creationService->broadcastOrderCreated($pedido);

            return response()->json([
                'success' => true,
                'message' => 'Orden registrada correctamente',
                'pedido' => $pedido->numero_pedido
            ]);
        });
    }

    public function update(Request $request, $pedido)
    {
        return $this->tryExec(function() use ($request, $pedido) {
            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)
                ->firstOrFail();

            // Validar datos
            $validatedData = $this->validationService->validateUpdateRequest($request);

            // Ejecutar actualización delegada al servicio
            $response = $this->updateService->updateOrder($orden, $validatedData);

            // Broadcast eventos
            $this->updateService->broadcastOrderUpdated($orden, $validatedData);

            return response()->json($response);
        });
    }

    public function destroy($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $this->deletionService->deleteOrder($pedido);
            
            // Broadcast evento
            $this->deletionService->broadcastOrderDeleted($pedido);

            return response()->json([
                'success' => true,
                'message' => 'Orden eliminada correctamente',
                'pedido' => $pedido
            ]);
        });
    }

    public function getEntregas($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $entregas = $this->entregasService->getEntregas($pedido);
            return response()->json($entregas);
        });
    }

    /**
     * Invalidar caché de días calculados para una orden específica
     * Se ejecuta cuando se actualiza o elimina una orden
     * 
     * Delegado a: RegistroOrdenCacheService::invalidateDaysCache()
     */
    private function invalidarCacheDias($pedido): void
    {
        $this->cacheService->invalidateDaysCache($pedido);
    }

    public function updatePedido(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            $validatedData = $request->validate([
                'old_pedido' => 'required|integer',
                'new_pedido' => 'required|integer|min:1',
            ]);

            $this->numberService->updatePedidoNumber(
                $validatedData['old_pedido'],
                $validatedData['new_pedido']
            );

            // Obtener la orden actualizada para broadcast
            $orden = PedidoProduccion::where('numero_pedido', $validatedData['new_pedido'])->first();
            if ($orden) {
                $this->numberService->broadcastPedidoUpdated($orden);
            }

            return response()->json([
                'success' => true,
                'message' => 'Número de pedido actualizado correctamente',
                'old_pedido' => $validatedData['old_pedido'],
                'new_pedido' => $validatedData['new_pedido']
            ]);
        });
    }

    /**
     * Obtener registros por orden (API para el modal de edición)
     * Retorna las prendas desde la nueva arquitectura
     */
    public function getRegistrosPorOrden($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $prendas = $this->prendaService->getPrendasArray($pedido);
            return response()->json($prendas);
        });
    }

    /**
     * Editar orden completa (actualiza tabla_original y registros_por_orden)
     */
    public function editFullOrder(Request $request, $pedido)
    {
        return $this->tryExec(function() use ($request, $pedido) {
            // Validar datos
            $validatedData = $this->validationService->validateEditFullOrderRequest($request);

            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Actualizar orden y prendas
            DB::beginTransaction();

            $orden->update([
                'estado' => $validatedData['estado'] ?? 'No iniciado',
                'cliente' => $validatedData['cliente'],
                'fecha_de_creacion_de_orden' => $validatedData['fecha_creacion'],
                'forma_de_pago' => $validatedData['forma_pago'] ?? null,
            ]);

            // Reemplazar prendas
            $totalPrendas = $this->prendaService->replacePrendas($pedido, $validatedData['prendas']);

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log evento
            News::create([
                'event_type' => 'order_updated',
                'description' => "Orden editada: Pedido {$pedido} para cliente {$validatedData['cliente']}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['cliente' => $validatedData['cliente'], 'total_prendas' => count($validatedData['prendas'])]
            ]);

            DB::commit();

            // Recargar relaciones
            $orden->load('prendas');

            // Broadcast evento
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizada correctamente',
                'pedido' => $pedido,
                'orden' => $orden
            ]);
        });
    }

    /**
     * Actualizar descripción y regenerar registros_por_orden basado en el contenido
     */
    public function updateDescripcionPrendas(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            // Validar datos
            $validatedData = $this->validationService->validateUpdateDescripcionRequest($request);

            $pedido = $validatedData['pedido'];
            $nuevaDescripcion = $validatedData['descripcion'];

            DB::beginTransaction();

            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Parsear descripción
            $prendas = $this->prendaService->parseDescripcionToPrendas($nuevaDescripcion);
            $procesarRegistros = $this->prendaService->isValidParsedPrendas($prendas);

            // Si hay prendas válidas, reemplazarlas
            if ($procesarRegistros) {
                $this->prendaService->replacePrendas($pedido, $prendas);
            }

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log evento
            News::create([
                'event_type' => 'description_updated',
                'description' => "Descripción y prendas actualizadas para pedido {$pedido}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['prendas_count' => count($prendas)]
            ]);

            DB::commit();

            // Recargar relaciones
            $orden->load('prendas');

            // Broadcast evento
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            // Obtener mensaje de resultado
            $mensaje = $this->prendaService->getParsedPrendasMessage($prendas);

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'prendas_procesadas' => count($prendas),
                'registros_regenerados' => $procesarRegistros
            ]);
        });
    }

    /**
     * Parsear descripción para extraer información de prendas y tallas
     */
    /**
     * DEPRECATED: Método movido a RegistroOrdenPrendaService::parseDescripcionToPrendas()
     * Se mantiene como referencia pero ya no se utiliza
     */
    // parseDescripcionToPrendas() - Ver RegistroOrdenPrendaService

    /**
     * Obtener detalles de una orden específica para el modal
     * GET /orders/{numero_pedido}
     */
    public function show($numeroPedido)
    {
        try {
            // Buscar la orden en PedidoProduccion con relaciones
            $order = PedidoProduccion::with('asesora')->where('numero_pedido', $numeroPedido)->first();
            
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            // Obtener nombre de la asesora
            $asesoraName = '';
            if ($order->asesora) {
                $asesoraName = $order->asesora->name ?? '';
            }

            // Obtener datos básicos
            $orderData = [
                'id' => $order->id,
                'numero_pedido' => $order->numero_pedido,
                'cliente' => $order->cliente,
                'fecha_de_creacion_de_orden' => $order->fecha_de_creacion_de_orden,
                'descripcion_prendas' => $order->descripcion_prendas ?? '',
                'estado' => $order->estado,
                'forma_de_pago' => $order->forma_de_pago ?? '-',
                'area' => $order->area,
                'novedades' => $order->novedades,
                'total_cantidad' => 0,
                'total_entregado' => 0,
                'cantidad' => 0,
                'encargado_orden' => '',
                'asesora' => $asesoraName,
            ];

            // Calcular totales si existen prendas
            try {
                $totalCantidad = DB::table('prendas')
                    ->where('numero_pedido', $numeroPedido)
                    ->sum('cantidad');
                $orderData['total_cantidad'] = $totalCantidad ?? 0;
                $orderData['cantidad'] = $totalCantidad ?? 0;
            } catch (\Exception $e) {
                \Log::warning('Error calculando cantidad: ' . $e->getMessage());
            }

            // Calcular entregas
            try {
                $totalEntregado = DB::table('entregas')
                    ->where('numero_pedido', $numeroPedido)
                    ->sum('cantidad_entregada');
                $orderData['total_entregado'] = $totalEntregado ?? 0;
            } catch (\Exception $e) {
                \Log::warning('Error calculando entregas: ' . $e->getMessage());
            }

            // Obtener prendas - usar plantilla si está relacionado a cotización
            try {
                // Verificar si el pedido está relacionado a una cotización
                $esCotizacion = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $numeroPedido)
                    ->whereNotNull('cotizacion_id')
                    ->exists();

                if ($esCotizacion) {
                    // Usar plantilla para cotizaciones
                    $templateService = new PrendaCotizacionTemplateService();
                    $orderData['prendas'] = $templateService->generarPlantillaPrendas($numeroPedido);
                    $orderData['es_cotizacion'] = true;
                } else {
                    // Usar formato simple para pedidos sin cotización
                    $prendas = DB::table('prendas_pedido')
                        ->where('numero_pedido', $numeroPedido)
                        ->orderBy('id', 'asc')
                        ->get(['nombre_prenda', 'descripcion', 'cantidad_talla']);

                    // Formatear prendas con enumeración
                    $prendasFormato = [];
                    foreach ($prendas as $index => $prenda) {
                        $prendasFormato[] = [
                            'numero' => $index + 1,
                            'nombre' => $prenda->nombre_prenda ?? '-',
                            'descripcion' => $prenda->descripcion ?? '-',
                            'cantidad_talla' => $prenda->cantidad_talla ?? '-'
                        ];
                    }
                    $orderData['prendas'] = $prendasFormato;
                    $orderData['es_cotizacion'] = false;
                }
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo prendas: ' . $e->getMessage());
                $orderData['prendas'] = [];
                $orderData['es_cotizacion'] = false;
            }

            return response()->json($orderData);
        } catch (\Exception $e) {
            \Log::error('Error en show de orden: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => 'Error al obtener datos'], 500);
        }
    }

    /**
     * Obtener imágenes de una orden (DEPRECATED - Usar RegistroOrdenQueryController)
     */
}
