<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use Carbon\Carbon;
use DomainException;

final class ConfirmarCorreccionPedidoUseCase
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoReadRepository
    ) {}

    public function ejecutar(int $pedidoId, string $nombreUsuario): array
    {
        $pedido = $this->pedidoReadRepository->obtenerPedidoPorId($pedidoId);

        if ($pedido === null) {
            throw new DomainException('Pedido no encontrado.');
        }

        if (trim((string) $pedido['estado']) !== 'DEVUELTO_A_ASESORA') {
            throw new DomainException(
                'El pedido no está en estado "Devuelto a Asesora". Estado actual: ' . $pedido['estado']
            );
        }

        $separador = str_repeat('=', 50);
        $novedad = "CONFIRMACIÓN DE CORRECCIÓN DE PEDIDO:\n";
        $novedad .= $separador . "\n";
        $novedad .= 'Fecha de confirmación: ' . Carbon::now('UTC')->format('Y-m-d H:i:s') . "\n";
        $novedad .= 'Usuario que confirma: ' . $nombreUsuario . "\n";
        $novedad .= "Estado anterior: DEVUELTO_A_ASESORA\n";
        $novedad .= "Estado nuevo: PENDIENTE_SUPERVISOR\n";
        $novedad .= $separador . "\n";
        $novedad .= "El pedido ha sido corregido y está listo para supervisión.\n";

        $novedadesActualizadas = !empty($pedido['novedades'])
            ? ((string) $pedido['novedades']) . "\n" . $novedad
            : $novedad;

        $this->pedidoReadRepository->actualizarDatosBasicos($pedidoId, [
            'estado' => 'PENDIENTE_SUPERVISOR',
            'motivo_revision' => null,
            'fecha_revision' => null,
            'usuario_revision' => null,
            'novedades' => $novedadesActualizadas,
        ]);

        $actualizado = $this->pedidoReadRepository->obtenerPedidoPorId($pedidoId);

        if ($actualizado === null) {
            throw new DomainException('No fue posible confirmar la corrección del pedido.');
        }

        return [
            'pedido_id' => $actualizado['id'],
            'numero_pedido' => $actualizado['numero_pedido'],
            'estado' => $actualizado['estado'],
        ];
    }
}
