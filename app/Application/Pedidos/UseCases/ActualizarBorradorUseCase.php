<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Exceptions\ActualizarBorradorException;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Application\Services\Pedidos\ProcesarImagenesPrendaService;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Infrastructure\Services\Pedidos\PedidoDraftMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActualizarBorradorUseCase
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoRepository,
        private TransactionManagerInterface $transactionManager,
        private PedidoDraftMutationService $pedidoDraftMutationService,
        private ActualizarPrendaCompletaBridge $actualizarPrendaCompletaBridge,
        private ProcesarImagenesPrendaService $procesarImagenesPrendaService,
        private EliminarProcesosListaBridge $eliminarProcesosListaBridge,
        private EliminarPrendaPedidoUseCase $eliminarPrendaPedidoUseCase,
        private PrendaExistenteArchivosExtractor $prendaExistenteArchivosExtractor,
    ) {}

    public function ejecutar(ActualizarBorradorInput $input): ActualizarBorradorOutput
    {
        $inicioTotal = microtime(true);

        try {
            $this->registrarInicio($input);
            $pedido = $this->obtenerPedidoOFail($input);
            $this->registrarPedidoVerificado($input);
            $this->validarJsonSinFiles($input->datosFrontend);
            Log::info('[ActualizarBorradorUseCase] JSON validado');
            $nuevasPrendasMapeadas = $this->ejecutarActualizacionEnTransaccion($pedido, $input);

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);
            $this->registrarExito($input, $pedido, $tiempoTotal);

            return $this->crearOutputExitoso($input, $pedido, $tiempoTotal, $nuevasPrendasMapeadas);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('[ActualizarBorradorUseCase] PEDIDO NO ENCONTRADO O NO AUTORIZADO', [
                'pedido_id' => $input->pedidoId,
                'asesor_id' => $input->asesorId,
            ]);

            return new ActualizarBorradorOutput(
                success: false,
                message: 'Pedido no encontrado o no tienes permiso para actualizarlo',
            );
        } catch (\Throwable $e) {
            Log::error('[ActualizarBorradorUseCase] ERROR CRITICO', [
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

    private function registrarInicio(ActualizarBorradorInput $input): void
    {
        Log::info('[ActualizarBorradorUseCase] INICIANDO ACTUALIZACION', [
            'pedido_id' => $input->pedidoId,
            'asesor_id' => $input->asesorId,
            'timestamp' => now(),
        ]);
    }

    private function obtenerPedidoOFail(ActualizarBorradorInput $input): mixed
    {
        $pedido = $this->pedidoRepository->obtenerPorIdYAsesor(
            $input->pedidoId,
            $input->asesorId
        );

        if (!$pedido) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        return $pedido;
    }

    private function registrarPedidoVerificado(ActualizarBorradorInput $input): void
    {
        Log::info('[ActualizarBorradorUseCase] Pedido verificado', [
            'pedido_id' => $input->pedidoId,
            'asesor_id' => $input->asesorId,
        ]);
    }

    private function ejecutarActualizacionEnTransaccion(mixed $pedido, ActualizarBorradorInput $input): array
    {
        $nuevasPrendasMapeadas = [];

        $this->transactionManager->run(function () use ($pedido, $input, &$nuevasPrendasMapeadas) {
            $this->actualizarDatosBasicos($pedido->pedidoId, $input);

            $this->pedidoDraftMutationService->actualizarEpps(
                $input->pedidoId,
                $input->datosFrontend['epps'] ?? [],
                $input->request
            );

            $this->eliminarPrendasMarcadas($pedido->pedidoId, $input);
            $this->actualizarPrendasExistentes($pedido->pedidoId, $input);
            $nuevasPrendasMapeadas = $this->procesarNuevasPrendas($pedido, $input);

            $this->pedidoDraftMutationService->procesarImagenesDeProcesos(
                $input->request,
                $input->pedidoId,
                $input->datosFrontend['prendas'] ?? []
            );
        });

        return $nuevasPrendasMapeadas;
    }

    private function actualizarDatosBasicos(int $pedidoId, ActualizarBorradorInput $input): void
    {
        $cliente = trim($input->datosFrontend['cliente'] ?? '');

        $this->pedidoRepository->actualizarDatosBasicos($pedidoId, [
            'cliente' => $cliente,
            'orden_compra' => $input->getOrdenCompra(),
            'forma_de_pago' => $input->datosFrontend['forma_de_pago'] ?? '',
            'observaciones' => $input->datosFrontend['observaciones'] ?? '',
        ]);

        Log::info('[ActualizarBorradorUseCase] Datos basicos actualizados', [
            'pedido_id' => $input->pedidoId,
            'cliente' => $cliente,
        ]);
    }

    private function procesarNuevasPrendas(mixed $pedido, ActualizarBorradorInput $input): array
    {
        $nuevasPrendas = $input->datosFrontend['nuevas_prendas'] ?? [];
        if (empty($nuevasPrendas) || !is_array($nuevasPrendas)) {
            return [];
        }

        $nuevasPrendasIds = $this->pedidoDraftMutationService->crearNuevasPrendas($pedido, $nuevasPrendas);

        $this->pedidoDraftMutationService->procesarImagenesNuevasPrendas(
            $input->request,
            $nuevasPrendasIds,
            $nuevasPrendas
        );

        $mapeadas = [];
        foreach ($nuevasPrendasIds as $idx => $nuevoId) {
            $localId = trim((string) (
                $nuevasPrendas[$idx]['local_id']
                ?? $nuevasPrendas[$idx]['_local_id']
                ?? ''
            ));

            if ($localId === '') {
                continue;
            }

            $mapeadas[] = [
                'local_id' => $localId,
                'prenda_pedido_id' => (int) $nuevoId,
            ];
        }

        if (!empty($mapeadas)) {
            Log::info('[ActualizarBorradorUseCase] Mapeo de nuevas prendas generado', [
                'pedido_id' => $input->pedidoId,
                'total' => count($mapeadas),
            ]);
        }

        return $mapeadas;
    }

    private function registrarExito(ActualizarBorradorInput $input, mixed $pedido, float $tiempoTotal): void
    {
        Log::info('[ActualizarBorradorUseCase] PEDIDO ACTUALIZADO EXITOSAMENTE', [
            'pedido_id' => $input->pedidoId,
            'numero_pedido' => $pedido->numeroPedido,
            'estado' => $pedido->estado,
            'tiempo_total_ms' => $tiempoTotal,
        ]);
    }

    private function crearOutputExitoso(
        ActualizarBorradorInput $input,
        mixed $pedido,
        float $tiempoTotal,
        array $nuevasPrendasMapeadas
    ): ActualizarBorradorOutput
    {
        return new ActualizarBorradorOutput(
            success: true,
            message: 'Pedido actualizado exitosamente',
            pedido_id: $input->pedidoId,
            numero_pedido: $pedido->numeroPedido,
            estado: $pedido->estado,
            redirect_url: route('asesores.pedidos.show', ['id' => $input->pedidoId]),
            tiempo_ms: $tiempoTotal,
            nuevas_prendas_mapeadas: $nuevasPrendasMapeadas,
        );
    }

    private function actualizarPrendasExistentes(int $pedidoId, ActualizarBorradorInput $input): void
    {
        $prendasExistentes = $input->datosFrontend['prendas_existentes'] ?? [];
        if (empty($prendasExistentes)) {
            return;
        }

        foreach ($prendasExistentes as $prendaIndex => $prendaPayload) {
            $prendaId = (int) ($prendaPayload['prenda_id'] ?? $prendaPayload['id'] ?? $prendaPayload['prenda_pedido_id'] ?? 0);
            
            Log::debug('[ActualizarBorradorUseCase] Validando prenda existente', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'prenda_index' => $prendaIndex,
                'es_valido' => $prendaId > 0,
            ]);
            
            if ($prendaId <= 0) {
                Log::warning('[ActualizarBorradorUseCase] ID inválido, ignorando prenda en prendas_existentes', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'prenda_index' => $prendaIndex,
                ]);
                continue;
            }

            $prendaRef = $this->pedidoRepository->obtenerPrendaDelPedido($pedidoId, $prendaId);

            if (!$prendaRef) {
                Log::error('[ActualizarBorradorUseCase] Prenda no encontrada en pedido', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'prenda_index' => $prendaIndex,
                    'prenda_ref_es_null' => true,
                ]);
                throw ActualizarBorradorException::prendaNoPerteneceAlPedido($prendaId, $pedidoId);
            }

            $subRequest = $this->crearSubRequestPrendaExistente($input->request, $prendaPayload, (int) $prendaIndex);

            $procesosAEliminar = $this->decodificarJsonArray($prendaPayload['procesos_a_eliminar'] ?? []);
            if (!empty($procesosAEliminar)) {
                $this->eliminarProcesosListaBridge->ejecutar($procesosAEliminar);
            }

            $imagenes = $this->procesarImagenesPrendaService->procesarParaActualizar($subRequest, $pedidoId);

            $this->actualizarPrendaCompletaBridge->ejecutarDesdePayload(
                $prendaId,
                $prendaPayload,
                $imagenes,
            );

            Log::info('[ActualizarBorradorUseCase] Prenda existente actualizada dentro del borrador', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'prenda_index' => $prendaIndex,
            ]);
        }
    }

    private function eliminarPrendasMarcadas(int $pedidoId, ActualizarBorradorInput $input): void
    {
        $prendasEliminadas = $input->datosFrontend['prendas_eliminadas'] ?? [];
        if (empty($prendasEliminadas) || !is_array($prendasEliminadas)) {
            return;
        }

        $prendasProcesadas = [];

        foreach ($prendasEliminadas as $prendaEliminada) {
            $prendaId = (int) ($prendaEliminada['prenda_id'] ?? $prendaEliminada['id'] ?? 0);
            if ($prendaId <= 0 || in_array($prendaId, $prendasProcesadas, true)) {
                continue;
            }

            $prendaRef = $this->pedidoRepository->obtenerPrendaDelPedido($pedidoId, $prendaId);
            if (!$prendaRef) {
                Log::warning('[ActualizarBorradorUseCase] Prenda eliminada no pertenece al pedido o ya no existe', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                ]);
                continue;
            }

            $motivo = trim((string) ($prendaEliminada['motivo'] ?? 'Eliminada desde guardado de borrador'));
            $this->eliminarPrendaPedidoUseCase->ejecutar($pedidoId, $prendaId, $motivo !== '' ? $motivo : 'Eliminada desde guardado de borrador');
            $prendasProcesadas[] = $prendaId;
        }

        if (!empty($prendasProcesadas)) {
            Log::info('[ActualizarBorradorUseCase] Prendas eliminadas dentro del borrador', [
                'pedido_id' => $pedidoId,
                'prendas_eliminadas' => $prendasProcesadas,
                'total' => count($prendasProcesadas),
            ]);
        }
    }

    private function crearSubRequestPrendaExistente(Request $requestOriginal, array $prendaPayload, int $prendaIndex): Request
    {
        $request = new Request();
        $request->replace([
            'prenda_id' => $prendaPayload['prenda_id'] ?? $prendaPayload['id'] ?? $prendaPayload['prenda_pedido_id'] ?? null,
            'nombre_prenda' => $prendaPayload['nombre_prenda'] ?? '',
            'descripcion' => $prendaPayload['descripcion'] ?? '',
            'origen' => $prendaPayload['origen'] ?? null,
            'de_bodega' => $prendaPayload['de_bodega'] ?? null,
            'tallas' => isset($prendaPayload['tallas']) ? json_encode($prendaPayload['tallas']) : null,
            'variantes' => isset($prendaPayload['variantes']) ? json_encode($prendaPayload['variantes']) : null,
            'colores_telas' => isset($prendaPayload['colores_telas']) ? json_encode($prendaPayload['colores_telas']) : null,
            'fotos_telas' => isset($prendaPayload['fotos_telas']) ? json_encode($prendaPayload['fotos_telas']) : null,
            'procesos' => isset($prendaPayload['procesos']) ? json_encode($prendaPayload['procesos']) : null,
            'novedad' => $prendaPayload['novedad'] ?? 'Actualizacion desde guardado de borrador',
            'asignaciones_colores' => array_key_exists('asignaciones_colores', $prendaPayload)
                ? json_encode($prendaPayload['asignaciones_colores'])
                : null,
            'imagenes_existentes' => isset($prendaPayload['imagenes_existentes']) ? json_encode($prendaPayload['imagenes_existentes']) : null,
            'imagenes_a_eliminar' => isset($prendaPayload['imagenes_a_eliminar']) ? json_encode($prendaPayload['imagenes_a_eliminar']) : null,
            'procesos_a_eliminar' => isset($prendaPayload['procesos_a_eliminar']) ? json_encode($prendaPayload['procesos_a_eliminar']) : null,
        ]);

        $request->files->add($this->prendaExistenteArchivosExtractor->extraer($requestOriginal, $prendaIndex));

        return $request;
    }

    private function decodificarJsonArray(mixed $valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        if (!is_string($valor) || trim($valor) === '') {
            return [];
        }

        $decodificado = json_decode($valor, true);

        return is_array($decodificado) ? $decodificado : [];
    }

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
                    'tipo' => get_class($valor),
                ]);

                throw ActualizarBorradorException::objetoNoSerializableEnJson($rutaActual);
            }
        }
    }
}
