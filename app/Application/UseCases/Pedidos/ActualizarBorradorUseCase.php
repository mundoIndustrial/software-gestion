<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\EliminarProcesosListaUseCase;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Application\Services\Pedidos\ProcesarImagenesPrendaService;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Infrastructure\Services\Pedidos\PedidoDraftMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActualizarBorradorUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
        private TransactionManagerInterface $transactionManager,
        private PedidoDraftMutationService $pedidoDraftMutationService,
        private ActualizarPrendaCompletaUseCase $actualizarPrendaCompletaUseCase,
        private ProcesarImagenesPrendaService $procesarImagenesPrendaService,
        private EliminarProcesosListaUseCase $eliminarProcesosListaUseCase,
    ) {}

    public function ejecutar(ActualizarBorradorInput $input): ActualizarBorradorOutput
    {
        $inicioTotal = microtime(true);

        try {
            Log::info('[ActualizarBorradorUseCase] INICIANDO ACTUALIZACION', [
                'pedido_id' => $input->pedidoId,
                'asesor_id' => $input->asesorId,
                'timestamp' => now(),
            ]);

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

            $this->validarJsonSinFiles($input->datosFrontend);
            Log::info('[ActualizarBorradorUseCase] JSON validado');

            $this->transactionManager->run(function () use ($pedido, $input) {
                $this->pedidoRepository->actualizarDatosBasicos($pedido, [
                    'cliente' => trim($input->datosFrontend['cliente'] ?? ''),
                    'orden_compra' => $input->getOrdenCompra(),
                    'forma_de_pago' => $input->datosFrontend['forma_de_pago'] ?? '',
                    'observaciones' => $input->datosFrontend['observaciones'] ?? '',
                ]);

                Log::info('[ActualizarBorradorUseCase] Datos basicos actualizados', [
                    'pedido_id' => $input->pedidoId,
                    'cliente' => $pedido->cliente,
                ]);

                $this->pedidoDraftMutationService->actualizarEpps(
                    $input->pedidoId,
                    $input->datosFrontend['epps'] ?? [],
                    $input->request
                );

                $this->actualizarPrendasExistentes($pedido->id, $input);

                $nuevasPrendas = $input->datosFrontend['nuevas_prendas'] ?? [];
                $nuevasPrendasIds = $this->pedidoDraftMutationService->crearNuevasPrendas($pedido, $nuevasPrendas);
                $this->pedidoDraftMutationService->procesarImagenesNuevasPrendas(
                    $input->request,
                    $nuevasPrendasIds,
                    $nuevasPrendas
                );

                $this->pedidoDraftMutationService->procesarImagenesDeProcesos(
                    $input->request,
                    $input->pedidoId,
                    $input->datosFrontend['prendas'] ?? []
                );
            });

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);

            Log::info('[ActualizarBorradorUseCase] PEDIDO ACTUALIZADO EXITOSAMENTE', [
                'pedido_id' => $input->pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'estado' => $pedido->estado,
                'tiempo_total_ms' => $tiempoTotal,
            ]);

            return new ActualizarBorradorOutput(
                success: true,
                message: 'Pedido actualizado exitosamente',
                pedido_id: $input->pedidoId,
                numero_pedido: $pedido->numero_pedido,
                estado: $pedido->estado,
                redirect_url: route('asesores.pedidos.show', ['pedido' => $input->pedidoId]),
                tiempo_ms: $tiempoTotal,
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('[ActualizarBorradorUseCase] PEDIDO NO ENCONTRADO O NO AUTORIZADO', [
                'pedido_id' => $input->pedidoId,
                'asesor_id' => $input->asesorId,
            ]);

            return new ActualizarBorradorOutput(
                success: false,
                message: 'Pedido no encontrado o no tienes permiso para actualizarlo',
            );
        } catch (\Exception $e) {
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

    private function actualizarPrendasExistentes(int $pedidoId, ActualizarBorradorInput $input): void
    {
        $prendasExistentes = $input->datosFrontend['prendas_existentes'] ?? [];
        if (empty($prendasExistentes)) {
            return;
        }

        foreach ($prendasExistentes as $prendaIndex => $prendaPayload) {
            $prendaId = (int) ($prendaPayload['prenda_id'] ?? $prendaPayload['id'] ?? $prendaPayload['prenda_pedido_id'] ?? 0);
            if ($prendaId <= 0) {
                continue;
            }

            $prenda = \App\Models\PrendaPedido::query()
                ->where('pedido_produccion_id', $pedidoId)
                ->where('id', $prendaId)
                ->first();

            if (!$prenda) {
                throw new \RuntimeException("La prenda {$prendaId} no pertenece al pedido {$pedidoId}");
            }

            $subRequest = $this->crearSubRequestPrendaExistente($input->request, $prendaPayload, (int) $prendaIndex);

            $procesosAEliminar = $this->decodificarJsonArray($prendaPayload['procesos_a_eliminar'] ?? []);
            if (!empty($procesosAEliminar)) {
                $this->eliminarProcesosListaUseCase->ejecutar($procesosAEliminar);
            }

            $imagenes = $this->procesarImagenesPrendaService->procesarParaActualizar($subRequest, $pedidoId);

            $dto = ActualizarPrendaCompletaDTO::fromRequest(
                $prendaId,
                $prendaPayload,
                $imagenes['imagenes_guardadas'],
                $imagenes['imagenes_existentes'],
                $imagenes['fotos_telas_procesadas'],
                $imagenes['fotos_proceso_nuevo'],
                $imagenes['fotos_color_procesadas'],
                $imagenes['fotos_proceso_tallas_nuevo'],
            );

            $this->actualizarPrendaCompletaUseCase->ejecutar($dto);

            Log::info('[ActualizarBorradorUseCase] Prenda existente actualizada dentro del borrador', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'prenda_index' => $prendaIndex,
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

        $request->files->add($this->extraerArchivosPrendaExistente($requestOriginal, $prendaIndex));

        return $request;
    }

    private function extraerArchivosPrendaExistente(Request $requestOriginal, int $prendaIndex): array
    {
        $archivos = [];
        $prefijo = 'prenda_existente_' . $prendaIndex . '_';

        foreach ($requestOriginal->allFiles() as $key => $value) {
            if (!is_string($key) || strpos($key, $prefijo) !== 0) {
                continue;
            }

            $claveNormalizada = substr($key, strlen($prefijo));

            if (preg_match('/^imagenes(?:\[\])?$/', $claveNormalizada)) {
                $archivos['imagenes'] = is_array($value) ? $value : [$value];
                continue;
            }

            if (preg_match('/^fotos_tela\[(\d+)\]$/', $claveNormalizada, $matches)) {
                $archivos['fotos_tela[' . $matches[1] . ']'] = $value;
                continue;
            }

            if (preg_match('/^fotosProcesoNuevo_(\d+)(?:\[\])?$/', $claveNormalizada, $matches)) {
                $archivos['fotosProcesoNuevo_' . $matches[1]] = is_array($value) ? $value : [$value];
                continue;
            }

            if (preg_match('/^fotosProcesoTallasNuevo_(\d+)_([a-zA-Z]+)_(.+?)(?:\[\])?$/', $claveNormalizada, $matches)) {
                $archivos['fotosProcesoTallasNuevo_' . $matches[1] . '_' . $matches[2] . '_' . $matches[3]] = is_array($value) ? $value : [$value];
                continue;
            }

            if (preg_match('/^fotos_color\[(\d+)\]$/', $claveNormalizada, $matches)) {
                $archivos['fotos_color'][$matches[1]] = $value;
            }
        }

        return $archivos;
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

                throw new \Exception(
                    "Objeto no serializable en JSON en ruta: {$rutaActual}. " .
                    'Las imagenes deben enviarse por FormData, no por JSON.'
                );
            }
        }
    }
}
