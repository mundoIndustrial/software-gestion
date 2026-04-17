<?php

namespace App\Application\Pedidos\UseCases\RegistroOrden;

use App\Infrastructure\Repositories\PedidoProduccionTrackingRepository;
use App\Infrastructure\Repositories\ConsecutivosRecibosRepository;
use App\Models\ProcesoPrenda;
use App\Exceptions\GetConsecutivoCosturaException;

/**
 * GetConsecutivoCosturaUseCase
 * Obtener consecutivo de costura para un pedido y prenda
 * Cumple DDD: Application Layer - UseCase
 * Nota: Las excepciones son manejadas por el Handler que renderiza
 * respuestas JSON automáticamente. El UseCase solo lanza excepciones.
 */
class GetConsecutivoCosturaUseCase
{
    private PedidoProduccionTrackingRepository $pedidoRepository;
    private ConsecutivosRecibosRepository $consecutivosRepository;

    public function __construct(
        PedidoProduccionTrackingRepository $pedidoRepository,
        ConsecutivosRecibosRepository $consecutivosRepository
    ) {
        $this->pedidoRepository = $pedidoRepository;
        $this->consecutivosRepository = $consecutivosRepository;
    }

    /**
     * Ejecutar use case
     * GET /registros/{pedido}/consecutivo-costura
     * @param string $pedido ID o número de pedido
     * @param string|null $prendaId ID de la prenda (opcional)
     * @return array Datos del consecutivo de costura
     * @throws GetConsecutivoCosturaException
     */
    public function execute(string $pedido, ?string $prendaId = null, ?string $numeroRecibo = null): array
    {
        // Validar entrada
        if (empty($pedido)) {
            throw GetConsecutivoCosturaException::pedidoInvalido();
        }

        try {
            $pedidoModel = $this->pedidoRepository->obtenerPorIdONumero($pedido);

            if (!$pedidoModel) {
                throw GetConsecutivoCosturaException::pedidoNoEncontrado($pedido);
            }

            $numeroPedido = $pedidoModel->numero_pedido ?? $pedido;
            $pedidoId = $pedidoModel->id;

            \Log::info('[GetConsecutivoCosturaUseCase] Obteniendo consecutivo', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $numeroPedido,
                'prenda_id' => $prendaId
            ]);

            // Obtener consecutivo y área
            $consecutivoData = $this->obtenerConsecutivoYArea($pedidoId, $prendaId, $numeroRecibo);

            if (!$consecutivoData['consecutivo'] && !$pedidoModel->created_at) {
                throw GetConsecutivoCosturaException::sinDatos($pedido);
            }

            // Obtener encargado y fechas del proceso
            $procesoPrenda = $this->obtenerProcesoYEncargado(
                $numeroPedido,
                $prendaId,
                $consecutivoData['consecutivo']
            );

            return $this->construirRespuesta($consecutivoData, $procesoPrenda, $pedidoModel->created_at);

        } catch (GetConsecutivoCosturaException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('[GetConsecutivoCosturaUseCase] Error: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw GetConsecutivoCosturaException::errorConsulta($e);
        }
    }

    /**
     * Obtener consecutivo y área del registro
     * @param int $pedidoId
     * @param string|null $prendaId
     * @return array ['consecutivo' => ?string, 'area' => ?string, 'dia_de_entrega' => ?int, 'fecha_estimada_de_entrega' => ?string]
     */
    private function obtenerConsecutivoYArea(int $pedidoId, ?string $prendaId, ?string $numeroRecibo): array
    {
        $registro = null;

        if ($prendaId && $numeroRecibo) {
            $registro = \DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_id', (int) $prendaId)
                ->where('consecutivo_actual', (int) $numeroRecibo)
                ->whereRaw('UPPER(tipo_recibo) = ?', ['COSTURA'])
                ->where('activo', 1)
                ->first();
        }

        if (!$registro) {
            $registro = $prendaId
                ? $this->consecutivosRepository->obtenerCosinturaPorPrenda($pedidoId, (int) $prendaId)
                : $this->consecutivosRepository->obtenerCosturaDelPedido($pedidoId);
        }

        return [
            'consecutivo'              => $registro->consecutivo_actual ?? null,
            'area'                     => $registro->area ?? null,
            'dia_de_entrega'           => isset($registro->dia_de_entrega) ? (int) $registro->dia_de_entrega : null,
            'fecha_estimada_de_entrega' => $registro->fecha_estimada_de_entrega ?? null,
        ];
    }

    /**
     * Obtener proceso, encargado y fechas asociadas
     * @param string $numeroPedido
     * @param string|null $prendaId
     * @param string|null $consecutivo
     * @return array ['id' => ?int, 'encargado' => ?string, 'fecha_inicio' => ?, 'fecha_fin' => ?]
     */
    private function obtenerProcesoYEncargado(string $numeroPedido, ?string $prendaId, ?string $consecutivo): array
    {
        $data = [
            'id' => null,
            'encargado' => null,
            'fecha_inicio' => null,
            'fecha_fin' => null
        ];

        if (!$consecutivo) {
            return $data;
        }

        $ultimoProceso = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->when($prendaId, fn($q) => $q->where('prenda_pedido_id', $prendaId))
            ->where('numero_recibo', (int) $consecutivo)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        if ($ultimoProceso) {
            $data = [
                'id' => $ultimoProceso->id,
                'encargado' => $ultimoProceso->encargado,
                'fecha_inicio' => $ultimoProceso->fecha_inicio,
                'fecha_fin' => $ultimoProceso->fecha_fin
            ];
        }

        return $data;
    }

    /**
     * Construir respuesta final
     * @param array $consecutivoData
     * @param array $procesoData
     * @param mixed $fechaCreacion
     * @return array Respuesta completa
     */
    private function construirRespuesta(array $consecutivoData, array $procesoData, $fechaCreacion): array
    {
        \Log::info('[GetConsecutivoCosturaUseCase] Datos encontrados', [
            'consecutivo' => $consecutivoData['consecutivo'],
            'area' => $consecutivoData['area'],
            'encargado' => $procesoData['encargado']
        ]);

        return [
            'consecutivo' => $consecutivoData['consecutivo'],
            'area' => $consecutivoData['area'],
            'dia_de_entrega' => $consecutivoData['dia_de_entrega'] ?? null,
            'fecha_estimada_de_entrega' => $consecutivoData['fecha_estimada_de_entrega'] ?? null,
            'encargado' => $procesoData['encargado'],
            'proceso_id' => $procesoData['id'],
            'fecha_inicio' => $procesoData['fecha_inicio'],
            'fecha_fin' => $procesoData['fecha_fin'],
            'fecha_creacion' => $fechaCreacion
        ];
    }
}
