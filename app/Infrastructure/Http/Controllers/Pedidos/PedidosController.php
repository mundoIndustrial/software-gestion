<?php
namespace App\Infrastructure\Http\Controllers\Pedidos;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Controllers\RegistroOrdenExceptionHandler;
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
use App\Constants\DescripcionPrendaConstants;
//  FASE 1: UseCase Imports
use App\Application\UseCases\Pedidos\CrearOrdenUseCase;
use App\Application\UseCases\Pedidos\ActualizarOrdenUseCase;
use App\Application\UseCases\Pedidos\EliminarOrdenUseCase;
use App\Application\UseCases\Pedidos\ObtenerDetallesOrdenUseCase;
use App\Application\UseCases\Pedidos\DTOs\CrearOrdenInput;
use App\Application\UseCases\Pedidos\DTOs\ActualizarOrdenInput;

//  FASE 2: UseCase Imports
use App\Application\UseCases\Pedidos\BuscarOrdenesUseCase;
use App\Application\UseCases\Pedidos\FiltrarOrdenesUseCase;
use App\Application\UseCases\Pedidos\ObtenerOpcionesColumnaUseCase;
use App\Application\UseCases\Pedidos\ObtenerOpcionesGeneralesUseCase;
use App\Application\UseCases\Pedidos\DTOs\BuscarOrdenesInput;
use App\Application\UseCases\Pedidos\DTOs\FiltrarOrdenesInput;
use App\Application\UseCases\Pedidos\DTOs\ObtenerOpcionesColumnaInput;

//  FASE 3: UseCase Imports
use App\Application\UseCases\Pedidos\ObtenerPrendasUseCase;
use App\Application\UseCases\Pedidos\ActualizarPrendasUseCase;
use App\Application\UseCases\Pedidos\ActualizarDescripcionUseCase;
use App\Application\UseCases\Pedidos\DTOs\ObtenerPrendasInput;
use App\Application\UseCases\Pedidos\DTOs\ActualizarPrendasInput;
use App\Application\UseCases\Pedidos\DTOs\ActualizarDescripcionInput;

//  FASE 4: UseCase Imports
use App\Application\UseCases\Pedidos\ActualizarNoveadUseCase;
use App\Application\UseCases\Pedidos\AgregarNoveadUseCase;
use App\Application\UseCases\Pedidos\DTOs\ActualizarNoveadInput;
use App\Application\UseCases\Pedidos\DTOs\AgregarNoveadInput;



class PedidosController extends Controller
{
    use RegistroOrdenExceptionHandler;

    // ðŸ”§ Original Services
    protected $validationService;
    protected $creationService;
    protected $updateService;
    protected $deletionService;
    protected $numberService;
    protected $prendaService;
    protected $cacheService;
    protected $entregasService;
    protected $processesService;

    //  FASE 1: UseCase Injections
    protected $crearOrdenUseCase;
    protected $actualizarOrdenUseCase;
    protected $eliminarOrdenUseCase;
    protected $obtenerDetallesOrdenUseCase;

    //  FASE 2: UseCase Injections
    protected $buscarOrdenesUseCase;
    protected $filtrarOrdenesUseCase;
    protected $obtenerOpcionesColumnaUseCase;
    protected $obtenerOpcionesGeneralesUseCase;

    //  FASE 3: UseCase Injections
    protected $obtenerPrendasUseCase;
    protected $actualizarPrendasUseCase;
    protected $actualizarDescripcionUseCase;

    //  FASE 4: UseCase Injections
    protected $actualizarNoveadUseCase;
    protected $agregarNoveadUseCase;



    public function __construct(
        RegistroOrdenValidationService $validationService,
        RegistroOrdenCreationService $creationService,
        RegistroOrdenUpdateService $updateService,
        RegistroOrdenDeletionService $deletionService,
        RegistroOrdenNumberService $numberService,
        RegistroOrdenPrendaService $prendaService,
        RegistroOrdenCacheService $cacheService,
        RegistroOrdenEntregasService $entregasService,
        RegistroOrdenProcessesService $processesService,
        //  FASE 1: UseCase Injections
        CrearOrdenUseCase $crearOrdenUseCase,
        ActualizarOrdenUseCase $actualizarOrdenUseCase,
        EliminarOrdenUseCase $eliminarOrdenUseCase,
        ObtenerDetallesOrdenUseCase $obtenerDetallesOrdenUseCase,
        //  FASE 2: UseCase Injections
        BuscarOrdenesUseCase $buscarOrdenesUseCase,
        FiltrarOrdenesUseCase $filtrarOrdenesUseCase,
        ObtenerOpcionesColumnaUseCase $obtenerOpcionesColumnaUseCase,
        ObtenerOpcionesGeneralesUseCase $obtenerOpcionesGeneralesUseCase,
        //  FASE 3: UseCase Injections
        ObtenerPrendasUseCase $obtenerPrendasUseCase,
        ActualizarPrendasUseCase $actualizarPrendasUseCase,
        ActualizarDescripcionUseCase $actualizarDescripcionUseCase,
        //  FASE 4: UseCase Injections
        ActualizarNoveadUseCase $actualizarNoveadUseCase,
        AgregarNoveadUseCase $agregarNoveadUseCase,

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

        //  FASE 1: UseCase Injections
        $this->crearOrdenUseCase = $crearOrdenUseCase;
        $this->actualizarOrdenUseCase = $actualizarOrdenUseCase;
        $this->eliminarOrdenUseCase = $eliminarOrdenUseCase;
        $this->obtenerDetallesOrdenUseCase = $obtenerDetallesOrdenUseCase;

        //  FASE 2: UseCase Injections
        $this->buscarOrdenesUseCase = $buscarOrdenesUseCase;
        $this->filtrarOrdenesUseCase = $filtrarOrdenesUseCase;
        $this->obtenerOpcionesColumnaUseCase = $obtenerOpcionesColumnaUseCase;
        $this->obtenerOpcionesGeneralesUseCase = $obtenerOpcionesGeneralesUseCase;

        //  FASE 3: UseCase Injections
        $this->obtenerPrendasUseCase = $obtenerPrendasUseCase;
        $this->actualizarPrendasUseCase = $actualizarPrendasUseCase;
        $this->actualizarDescripcionUseCase = $actualizarDescripcionUseCase;

        //  FASE 4: UseCase Injections
        $this->actualizarNoveadUseCase = $actualizarNoveadUseCase;
        $this->agregarNoveadUseCase = $agregarNoveadUseCase;


    }

    public function getNextPedido()
    {
        $pedidoInfo = $this->numberService->getNextPedidoInfo();
        return response()->json($pedidoInfo);
    }

    public function validatePedido(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            $request->validate(['pedido' => 'required|integer']);
            
            $pedido = $request->input('pedido');
            $nextInfo = $this->numberService->getNextPedidoInfo();
            $isValid = $this->numberService->isNextExpected($pedido);

            return response()->json([
                'valid' => $isValid,
                'next_pedido' => $nextInfo['next_pedido'],
            ]);
        });
    }

    public function store(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            //  FASE 1: Usar CrearOrdenUseCase
            $input = CrearOrdenInput::fromRequest($request);
            $output = $this->crearOrdenUseCase->execute($input);
            
            return response()->json($output->toResponse());
        });
    }

    public function update(Request $request, $pedido)
    {
        return $this->tryExec(function() use ($request, $pedido) {
            //  FASE 1: Usar ActualizarOrdenUseCase
            $input = ActualizarOrdenInput::fromRequest($request, $pedido);
            $output = $this->actualizarOrdenUseCase->execute($input);
            
            return response()->json($output->toResponse());
        });
    }

    public function destroy($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            //  FASE 1: Usar EliminarOrdenUseCase
            $output = $this->eliminarOrdenUseCase->execute($pedido);
            
            return response()->json($output->toResponse());
        });
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
                'message' => 'numero de pedido actualizado correctamente',
                'old_pedido' => $validatedData['old_pedido'],
                'new_pedido' => $validatedData['new_pedido']
            ]);
        });
    }

    /**
     * Obtener registros por orden (API para el modal de edicion)
     * Retorna las prendas desde la nueva arquitectura
     */
    public function getRegistrosPorOrden($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $input = ObtenerPrendasInput::fromNumeroPedido($pedido);
            $output = $this->obtenerPrendasUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }

    /**
     * Editar orden completa (actualiza tabla_original y registros_por_orden)
     */
    public function editFullOrder(Request $request, $pedido)
    {
        return $this->tryExec(function() use ($request, $pedido) {
            $input = ActualizarPrendasInput::fromRequest($request, $pedido);
            $output = $this->actualizarPrendasUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }

    /**
     * Actualizar descripcion y regenerar registros_por_orden basado en el contenido
     */
    public function updateDescripcionPrendas(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            $input = ActualizarDescripcionInput::fromRequest($request);
            $output = $this->actualizarDescripcionUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }

    /**
     * Obtener detalles de una orden especifica para el modal
     * GET /orders/{numero_pedido}
     */
    public function show($numeroPedido)
    {
        return $this->tryExec(function() use ($numeroPedido) {
            //  FASE 1: Usar ObtenerDetallesOrdenUseCase
            $output = $this->obtenerDetallesOrdenUseCase->execute($numeroPedido);
            return response()->json($output->toArray());
        });
    }

    /**
     * Obtener todas las opciones disponibles para filtros
     * GET /registros/filter-options
     */
    public function getFilterOptions()
    {
        return $this->tryExec(function() {
            //  FASE 2: Usar ObtenerOpcionesGeneralesUseCase
            $output = $this->obtenerOpcionesGeneralesUseCase->execute();
            return response()->json($output->toResponse());
        });
    }

    /**
     * Obtener opciones de una columna especifica con paginacion y busqueda
     * GET /registros/filter-column-options/{column}
     */
    public function getColumnFilterOptions($column, Request $request)
    {
        return $this->tryExec(function() use ($request, $column) {
            //  FASE 2: Usar ObtenerOpcionesColumnaUseCase
            $input = ObtenerOpcionesColumnaInput::fromRequest($request, $column);
            $output = $this->obtenerOpcionesColumnaUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }

    /**
     * Filtrar ordenes por criterios especificos
     * POST /registros/filter-orders
     */
    public function filterOrders(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            //  FASE 2: Usar FiltrarOrdenesUseCase
            $input = FiltrarOrdenesInput::fromRequest($request);
            $output = $this->filtrarOrdenesUseCase->execute($input);
            
            return response()->json($output->toResponse());
        });
    }

    /**
     *  búsqueda simple en tiempo real
     * POST /registros/search
     */
    public function searchOrders(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            //  FASE 2: Usar BuscarOrdenesUseCase
            $input = BuscarOrdenesInput::fromRequest($request);
            $output = $this->buscarOrdenesUseCase->execute($input);
            
            return response()->json($output->toResponse());
        });
    }

    /**
     * Actualizar novedades de una orden
     * POST /api/ordenes/{numero_pedido}/novedades
     */
    public function updateNovedades(Request $request, $numeroPedido)
    {
        return $this->tryExec(function() use ($request, $numeroPedido) {
            $input = ActualizarNoveadInput::fromRequest($request, $numeroPedido);
            $output = $this->actualizarNoveadUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }

    /**
     * Agrega una nueva novedad al final del campo (con usuario, fecha y hora)
     * Endpoint: POST /api/ordenes/{numero_pedido}/novedades/add
     */
    public function addNovedad(Request $request, $numeroPedido)
    {
        return $this->tryExec(function() use ($request, $numeroPedido) {
            $input = AgregarNoveadInput::fromRequest($request, $numeroPedido);
            $output = $this->agregarNoveadUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }

}

