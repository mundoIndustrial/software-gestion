<?php

namespace App\Application\UseCases\Pedidos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PedidoEpp;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Domain\Pedidos\Services\PedidoImagenesService;

/**
 * ActualizarBorradorUseCase
 * 
 * REFACTOR FASE 7 (Marzo 2026): Extrae lógica de actualizarBorrador del Controller
 * 
 * Orquesta la actualización transaccional de borradores de pedidos con imágenes.
 * Responsabilidades:
 * 1. Validar seguridad (asesor_id)
 * 2. Obtener pedido existente
 * 3. Validar JSON del frontend
 * 4. Actualizar datos básicos del pedido
 * 5. Actualizar EPPs (cantidad, observaciones, imágenes)
 * 6. Procesar imágenes de procesos productivos
 * 
 * Transaccional: Rollback automático si algo falla
 * 
 * @package App\Application\UseCases\Pedidos
 */
class ActualizarBorradorUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
        private PedidoImagenesService $pedidoImagenesService,
    ) {}

    /**
     * Ejecutar el caso de uso: Actualizar borrador
     * 
     * @param ActualizarBorradorInput $input
     * @return ActualizarBorradorOutput
     */
    public function ejecutar(ActualizarBorradorInput $input): ActualizarBorradorOutput
    {
        $inicioTotal = microtime(true);

        try {
            Log::info('[ActualizarBorradorUseCase] INICIANDO ACTUALIZACIÓN', [
                'pedido_id' => $input->pedidoId,
                'asesor_id' => $input->asesorId,
                'timestamp' => now(),
            ]);

            // ====== PASO 1: SEGURIDAD - Obtener pedido verificando asesor ======
            $pedido = $this->pedidoRepository->obtenerPorIdYAsesor(
                $input->pedidoId,
                $input->asesorId
            );

            if (!$pedido) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            Log::info('[ActualizarBorradorUseCase] Pedido verificado', [
                'pedido_id' => $input->pedidoId,
                'asesor_id' => $input->asesorId,
            ]);

            // ====== PASO 2: Validar JSON del frontend ======
            $this->validarJsonSinFiles($input->datosFrontend);
            Log::info('[ActualizarBorradorUseCase] JSON validado');

            // ====== PASO 3: Iniciar transacción ======
            DB::beginTransaction();

            // ====== PASO 4: Actualizar datos básicos ======
            $this->pedidoRepository->actualizarDatosBasicos($pedido, [
                'cliente' => trim($input->datosFrontend['cliente'] ?? ''),
                'forma_de_pago' => $input->datosFrontend['forma_de_pago'] ?? '',
                'observaciones' => $input->datosFrontend['observaciones'] ?? '',
            ]);

            Log::info('[ActualizarBorradorUseCase] Datos básicos actualizados', [
                'pedido_id' => $input->pedidoId,
                'cliente' => $pedido->cliente,
            ]);

            // ====== PASO 5: Actualizar EPPs ======
            $this->actualizarEpps($input->pedidoId, $input->datosFrontend['epps'] ?? [], $input->request);

            // ====== PASO 6: Procesar imágenes de procesos ======
            $this->procesarImagenesDeProcesos(
                $input->request,
                $input->pedidoId,
                $input->datosFrontend['prendas'] ?? []
            );

            // ====== Confirmar transacción ======
            DB::commit();

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);

            Log::info('[ActualizarBorradorUseCase] ✅ PEDIDO ACTUALIZADO EXITOSAMENTE', [
                'pedido_id' => $input->pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'estado' => $pedido->estado,
                'tiempo_total_ms' => $tiempoTotal,
            ]);

            return new ActualizarBorradorOutput(
                success: true,
                message: '✅ Pedido actualizado exitosamente',
                pedido_id: $input->pedidoId,
                numero_pedido: $pedido->numero_pedido,
                estado: $pedido->estado,
                redirect_url: route('asesores.pedidos.show', ['pedido' => $input->pedidoId]),
                tiempo_ms: $tiempoTotal,
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('[ActualizarBorradorUseCase] ❌ PEDIDO NO ENCONTRADO O NO AUTORIZADO', [
                'pedido_id' => $input->pedidoId,
                'asesor_id' => $input->asesorId,
            ]);

            return new ActualizarBorradorOutput(
                success: false,
                message: 'Pedido no encontrado o no tienes permiso para actualizarlo',
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ActualizarBorradorUseCase] ❌ ERROR CRÍTICO', [
                'pedido_id' => $input->pedidoId,
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return new ActualizarBorradorOutput(
                success: false,
                message: 'Error al actualizar pedido: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Actualizar EPPs del pedido (cantidad, observaciones, imágenes)
     * 
     * @param int $pedidoId
     * @param array $eppsCrudos
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function actualizarEpps(int $pedidoId, array $eppsCrudos, $request): void
    {
        if (empty($eppsCrudos)) {
            return;
        }

        foreach ($eppsCrudos as $eppIndex => $eppData) {
            $eppId = $eppData['epp_id'] ?? null;
            $cantidad = $eppData['cantidad'] ?? 1;
            $observaciones = $eppData['observaciones'] ?? '';

            if (!$eppId) continue;

            // Obtener el registro PedidoEpp existente (via Repository)
            $pedidoEpp = $this->pedidoRepository->obtenerEppConImagenes($pedidoId, $eppId);

            if ($pedidoEpp) {
                // ELIMINAR IMÁGENES ANTIGUAS (usando Repository)
                $cantidadEliminada = $this->pedidoRepository->eliminarImagenesEpp($pedidoEpp->id);

                if ($cantidadEliminada > 0) {
                    Log::info('[ActualizarBorradorUseCase] Imágenes antiguas eliminadas', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'epp_id' => $eppId,
                        'imagenes_eliminadas' => $cantidadEliminada,
                    ]);
                }

                // Actualizar cantidad y observaciones (via Repository)
                $this->pedidoRepository->actualizarDatosBasicos($pedidoEpp, [
                    'cantidad' => $cantidad,
                    'observaciones' => $observaciones,
                ]);

                Log::info('[ActualizarBorradorUseCase] EPP actualizado', [
                    'pedido_id' => $pedidoId,
                    'epp_id' => $eppId,
                    'cantidad' => $cantidad,
                ]);
            }
        }

        // Procesar nuevas imágenes de EPPs
        $this->pedidoImagenesService->procesarImagenesDeEpps($request, $pedidoId, $eppsCrudos);
    }

    /**
     * Procesar imágenes de procesos productivos
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $pedidoId
     * @param array $prendasData
     * @return void
     */
    private function procesarImagenesDeProcesos($request, int $pedidoId, array $prendasData): void
    {
        if (empty($prendasData)) {
            return;
        }

        foreach ($prendasData as $prendaIndex => $prendaData) {
            $procesos = $prendaData['procesos'] ?? [];
            if (!empty($procesos)) {
                $this->pedidoImagenesService->procesarImagenesDeProcesos(
                    $request,
                    $pedidoId,
                    $procesos,
                    $prendaIndex
                );
            }
        }
    }

    /**
     * Validar que el JSON no contiene objetos File (que no deben estar en JSON)
     * 
     * @param array $datos
     * @param string $ruta
     * @return void
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
                Log::error('[ActualizarBorradorUseCase] ERROR: Objeto en JSON', [
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
}
