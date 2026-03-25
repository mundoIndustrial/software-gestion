<?php

namespace App\Application\UseCases\Pedidos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Domain\Clientes\Services\ClienteService;
use App\Domain\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Services\PedidoWebService;
use App\Infrastructure\Services\Pedidos\PedidoImagenesService;
use App\Infrastructure\Services\Pedidos\MapeoImagenesService;

/**
 * GuardarBorradorUseCase
 * 
 * REFACTOR FASE 7 (Marzo 2026): Extraído del Controller
 * 
 * Orquesta el guardado transaccional de borradores de pedidos con imágenes.
 * Responsabilidades:
 * 1. Validar JSON del frontend
 * 2. Obtener/crear cliente
 * 3. Normalizar pedido (DTO)
 * 4. Crear pedido en BD
 * 5. Crear carpetas de almacenamiento
 * 6. Mapear y procesar todas las imágenes
 * 7. Procesar imágenes específicas de EPPs
 * 8. Procesar imágenes de procesos productivos
 * 
 * Transaccional: Rollback automático si algo falla
 * 
 * @package App\Application\UseCases\Pedidos
 */
class GuardarBorradorUseCase
{
    public function __construct(
        private ClienteService $clienteService,
        private PedidoNormalizadorDTO $normalizador,
        private PedidoWebService $pedidoWebService,
        private PedidoImagenesService $pedidoImagenesService,
        private MapeoImagenesService $mapeoImagenes,
        private PedidoRepository $pedidoRepository,
    ) {}

    /**
     * Ejecutar el caso de uso: Guardar borrador
     * 
     * @param GuardarBorradorInput $input
     * @return GuardarBorradorOutput
     * @throws \Exception
     */
    public function ejecutar(GuardarBorradorInput $input): GuardarBorradorOutput
    {
        $inicioTotal = microtime(true);
        $pedidoId = null;

        try {
            Log::info('[GuardarBorradorUseCase] INICIANDO', [
                'asesor_id' => $input->asesorId,
                'timestamp' => now(),
            ]);

            // ====== PASO 1: Validar JSON del frontend ======
            $this->validarJsonSinFiles($input->datosFrontend);
            Log::info('[GuardarBorradorUseCase] JSON validado');

            // ====== PASO 2: Obtener/crear cliente ======
            $clienteNombre = trim($input->datosFrontend['cliente'] ?? '');
            $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);

            Log::info('[GuardarBorradorUseCase] Cliente obtenido/creado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);

            // ====== PASO 3: Normalizar usando DTO ======
            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $input->datosFrontend,
                $cliente->id
            );

            Log::info('[GuardarBorradorUseCase] Pedido normalizado (DTO)', [
                'cliente_id' => $dtoPedido->cliente_id,
                'prendas' => count($dtoPedido->prendas),
                'epps' => count($dtoPedido->epps),
            ]);

            // ====== PASO 4: Iniciar transacción ======
            DB::beginTransaction();

            // ====== PASO 5: Crear pedido borrador (sin número) ======
            $datosParaServicio = [
                'cliente' => $dtoPedido->cliente,
                'orden_compra' => $input->getOrdenCompra(),
                'asesora' => $dtoPedido->asesora,
                'forma_de_pago' => $dtoPedido->forma_de_pago,
                'observaciones' => $dtoPedido->observaciones,
                'cliente_id' => $dtoPedido->cliente_id,
                'items' => $dtoPedido->prendas,
                'epps' => $dtoPedido->epps,
            ];

            $pedido = $this->pedidoWebService->crearPedidoBorrador(
                $datosParaServicio,
                $input->asesorId
            );

            $pedidoId = $pedido->id;

            $nuevasPrendas = $input->datosFrontend['nuevas_prendas'] ?? [];
            if (!empty($nuevasPrendas)) {
                $nuevasPrendasIds = [];

                foreach ($nuevasPrendas as $index => $itemData) {
                    $prendaCreada = $this->pedidoWebService->agregarItemAPedido($pedido, $itemData, (int) $index);
                    $nuevasPrendasIds[] = $prendaCreada->id;
                }

                $this->pedidoImagenesService->procesarImagenesNuevasPrendas(
                    $input->request,
                    $nuevasPrendasIds,
                    $nuevasPrendas
                );

                Log::info('[GuardarBorradorUseCase] Nuevas prendas agregadas al borrador', [
                    'pedido_id' => $pedidoId,
                    'cantidad' => count($nuevasPrendasIds),
                ]);
            }

            Log::info('[GuardarBorradorUseCase] Pedido borrador creado', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL',
                'estado' => $pedido->estado,
            ]);

            // ====== PASO 6: Crear carpetas ======
            $this->pedidoImagenesService->crearCarpetasPedido($pedidoId);

            // ====== PASO 7: Mapear y procesar imágenes ======
            $this->mapeoImagenes->mapearYCrearFotos(
                $dtoPedido,
                $pedidoId,
                $input->request
            );

            Log::info('[GuardarBorradorUseCase] Imágenes mapeadas', [
                'pedido_id' => $pedidoId,
                'imagenes_mapeadas' => count($dtoPedido->imagen_uid_a_ruta),
            ]);

            // ====== PASO 8: Procesar imágenes de EPPs ======
            $eppsCrudos = $input->datosFrontend['epps'] ?? [];
            if (!empty($eppsCrudos)) {
                $this->pedidoImagenesService->procesarImagenesDeEpps(
                    $input->request,
                    $pedidoId,
                    $eppsCrudos
                );
            }

            // ====== PASO 9: Procesar imágenes de procesos ======
            $procesosData = $input->datosFrontend['prendas'] ?? [];
            if (!empty($procesosData)) {
                foreach ($procesosData as $prendaIndex => $prendaData) {
                    $procesos = $prendaData['procesos'] ?? [];
                    if (!empty($procesos)) {
                        $this->procesarImagenesDeProcesos(
                            $input->request,
                            $pedidoId,
                            $procesos,
                            $prendaIndex
                        );
                    }
                }
            }

            // ====== Confirmar transacción ======
            $cantidadTotalPrendas = $this->pedidoRepository->calcularCantidadTotalPrendas($pedidoId);
            $cantidadTotalEpps = $this->pedidoRepository->calcularCantidadTotalEpps($pedidoId);
            $pedido->update([
                'cantidad_total' => $cantidadTotalPrendas + $cantidadTotalEpps,
            ]);

            DB::commit();

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);

            Log::info('[GuardarBorradorUseCase]  BORRADOR GUARDADO EXITOSAMENTE', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL (Borrador)',
                'estado' => $pedido->estado,
                'tiempo_total_ms' => $tiempoTotal,
            ]);

            return new GuardarBorradorOutput(
                success: true,
                message: ' Borrador guardado exitosamente',
                pedido_id: $pedidoId,
                numero_pedido: $pedido->numero_pedido ?? null,
                estado: $pedido->estado,
                redirect_url: route('asesores.pedidos.show', ['pedido' => $pedidoId]),
                tiempo_ms: $tiempoTotal,
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('[GuardarBorradorUseCase]  Errores de validación', [
                'pedido_id' => $pedidoId,
                'errores' => $e->errors(),
            ]);

            return new GuardarBorradorOutput(
                success: false,
                message: 'Errores de validación',
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[GuardarBorradorUseCase]  ERROR CRÍTICO', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return new GuardarBorradorOutput(
                success: false,
                message: 'Error al guardar borrador: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Validar que el JSON no contiene objetos File (deben venir por FormData)
     * 
     * @param array $datos
     * @param string $ruta
     * @throws \Exception
     */
    private function validarJsonSinFiles(array $datos, $ruta = ''): void
    {
        foreach ($datos as $key => $valor) {
            $rutaActual = $ruta ? "{$ruta}.{$key}" : $key;
            
            if (is_array($valor)) {
                $this->validarJsonSinFiles($valor, $rutaActual);
            }
            
            if (is_object($valor)) {
                Log::error('[GuardarBorradorUseCase] ERROR: Objeto en JSON (File no serializado)', [
                    'ruta' => $rutaActual,
                    'tipo' => get_class($valor)
                ]);
                
                throw new \Exception(
                    "Objeto no serializable en JSON en ruta: {$rutaActual}. " .
                    "Las imágenes deben enviarse por FormData, no por JSON."
                );
            }
        }
    }

    /**
     * Procesar imágenes de procesos productivos
     * 
     * Delegado a PedidoImagenesService por ahora
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $pedidoId
     * @param array $procesos
     * @param int $prendaIndex
     */
    private function procesarImagenesDeProcesos($request, int $pedidoId, array $procesos, int $prendaIndex): void
    {
        // TODO: Implementar si es necesario
        // Por ahora se delega a un método que se llamaría en PedidoImagenesService
    }
}
