<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPedidoCamposDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;

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

        if ($dto->novedades !== null && !$esBorrador) {
            $datosActualizar['novedades'] = $dto->novedades;
        }

        if (!$esBorrador && $dto->justificacion !== null && trim($dto->justificacion) !== '') {
            $novedadesActuales = $datosActualizar['novedades'] ?? ($pedido['novedades'] ?? '');
            $fechaActual = now()->format('d/m/Y H:i');
            $registroNovedad = "[{$dto->nombreUsuario} - {$dto->rolUsuario} - {$fechaActual}]\n{$dto->justificacion}";
            $datosActualizar['novedades'] = !empty($novedadesActuales)
                ? $novedadesActuales . "\n\n" . $registroNovedad
                : $registroNovedad;
        }

        if (!empty($datosActualizar)) {
            $this->pedidoReadRepository->actualizarDatosBasicos($dto->pedidoId, $datosActualizar);
        }

        return $this->pedidoReadRepository->obtenerPedidoPorId($dto->pedidoId) ?? $pedido;
    }
}
