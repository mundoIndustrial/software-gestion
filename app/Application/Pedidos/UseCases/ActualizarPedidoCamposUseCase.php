<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPedidoCamposDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Models\News;

final class ActualizarPedidoCamposUseCase
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoReadRepository
    ) {}

    public function ejecutar(ActualizarPedidoCamposDTO $dto): array
    {
        $pedido = $this->pedidoReadRepository->obtenerPedidoPorId($dto->pedidoId);
        if ($pedido === null) {
            throw new \RuntimeException('Pedido no encontrado', 404);
        }

        $estado = strtolower((string) ($pedido['estado'] ?? ''));
        $esBorrador = empty($pedido['numero_pedido']) || $estado === 'borrador';

        $datosActualizar = [];

        if ($dto->cliente !== null) {
            $datosActualizar['cliente'] = $dto->cliente;
        }

        if ($dto->formaDePago !== null) {
            $datosActualizar['forma_de_pago'] = $dto->formaDePago;
        }

        if ($dto->ordenCompra !== null) {
            $datosActualizar['orden_compra'] = $dto->ordenCompra;
        }

        if ($dto->observaciones !== null) {
            $datosActualizar['observaciones'] = $dto->observaciones;
        }

        if ($dto->novedades !== null && !$esBorrador) {
            $datosActualizar['novedades'] = $dto->novedades;
        }

        if (!$esBorrador && $dto->justificacion !== null && trim($dto->justificacion) !== '') {
            $novedadesActuales = $datosActualizar['novedades'] ?? ($pedido['novedades'] ?? '');
            $fechaActual = now()->format('d/m/Y H:i');
            $detalleCambios = $this->construirDetalleCamposCambiados($pedido, $dto);
            $registroNovedad = "[{$dto->nombreUsuario} - {$dto->rolUsuario} - {$fechaActual}]\n{$dto->justificacion}";

            if ($detalleCambios !== '') {
                // Usar salto simple para no partir la misma novedad en dos tarjetas
                // cuando la UI separa entradas por doble salto de línea.
                $registroNovedad .= "\nCambios realizados:\n" . $detalleCambios;
            }

            $datosActualizar['novedades'] = !empty($novedadesActuales)
                ? $novedadesActuales . "\n\n" . $registroNovedad
                : $registroNovedad;

            if ($detalleCambios !== '') {
                $detalleNotificacion = $this->formatearDetalleCambiosParaNotificacion($detalleCambios);

                News::create([
                    'event_type' => 'order_updated',
                    'table_name' => 'pedidos_produccion',
                    'record_id' => (int) $dto->pedidoId,
                    'description' => "{$dto->nombreUsuario} actualizo datos generales en Pedido #{$pedido['numero_pedido']}: {$detalleNotificacion}",
                    'user_id' => auth()->id(),
                    'pedido' => (string) ($pedido['numero_pedido'] ?? $dto->pedidoId),
                    'metadata' => [
                        'tipo' => 'datos_generales_actualizados',
                        'pedido_id' => (int) $dto->pedidoId,
                        'detalle_cambios' => $detalleCambios,
                    ],
                ]);
            }
        }

        if (!empty($datosActualizar)) {
            $this->pedidoReadRepository->actualizarDatosBasicos($dto->pedidoId, $datosActualizar);
        }

        return $this->pedidoReadRepository->obtenerPedidoPorId($dto->pedidoId) ?? $pedido;
    }

    private function construirDetalleCamposCambiados(array $pedidoActual, ActualizarPedidoCamposDTO $dto): string
    {
        $campos = [
            'cliente' => ['label' => 'Cliente', 'nuevo' => $dto->cliente],
            'forma_de_pago' => ['label' => 'Forma de pago', 'nuevo' => $dto->formaDePago],
            'orden_compra' => ['label' => 'Orden de compra', 'nuevo' => $dto->ordenCompra],
            'observaciones' => ['label' => 'Observaciones', 'nuevo' => $dto->observaciones],
        ];

        $lineas = [];
        foreach ($campos as $key => $config) {
            if ($config['nuevo'] === null) {
                continue;
            }

            $anterior = $this->normalizarTexto((string)($pedidoActual[$key] ?? ''));
            $nuevo = $this->normalizarTexto((string)$config['nuevo']);

            if ($anterior === $nuevo) {
                continue;
            }

            $lineas[] = "- {$config['label']}: \"{$anterior}\" -> \"{$nuevo}\"";
        }

        return implode("\n", $lineas);
    }

    private function normalizarTexto(string $valor): string
    {
        $valor = trim($valor);
        return $valor === '' ? '(vacio)' : $valor;
    }

    private function extraerCamposActualizados(string $detalleCambios): array
    {
        $lineas = preg_split('/\r\n|\r|\n/', $detalleCambios) ?: [];
        $campos = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || !str_starts_with($linea, '- ')) {
                continue;
            }

            $sinGuion = substr($linea, 2);
            $partes = explode(':', $sinGuion, 2);
            $campo = trim($partes[0] ?? '');

            if ($campo !== '') {
                $campos[] = $campo;
            }
        }

        return array_values(array_unique($campos));
    }

    private function formatearDetalleCambiosParaNotificacion(string $detalleCambios): string
    {
        $lineas = preg_split('/\r\n|\r|\n/', $detalleCambios) ?: [];
        $partes = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }

            if (str_starts_with($linea, '- ')) {
                $linea = substr($linea, 2);
            }

            $partes[] = $linea;
        }

        return implode('; ', $partes);
    }
}
