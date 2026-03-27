<?php

namespace App\Application\Services\Asesores;

use App\Application\Pedidos\UseCases\EliminarImagenPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarPrendaPedidoUseCase;

final class GestionEliminarPrendaService
{
    public function __construct(
        private readonly EliminarPrendaPedidoUseCase $eliminarPrendaPedidoUseCase,
        private readonly EliminarImagenPedidoUseCase $eliminarImagenPedidoUseCase,
        private readonly PrendaPedidoEdicionAuditoriaService $prendaPedidoEdicionAuditoriaService,
    ) {
    }

    public function eliminarImagen(int $pedidoId, string $tipo, int $id): array
    {
        $tiposValidos = ['prenda', 'tela', 'proceso'];
        if (!in_array($tipo, $tiposValidos, true)) {
            throw new \InvalidArgumentException('Tipo de imagen no valido');
        }

        $resultado = $this->eliminarImagenPedidoUseCase->ejecutar($id, $tipo);

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $pedidoId,
            $id,
            strtoupper($tipo) . ' (foto eliminada)'
        );

        return $resultado;
    }

    public function eliminarPrenda(int $pedidoId, int $prendaId, string $motivo): array
    {
        return $this->eliminarPrendaPedidoUseCase->ejecutar($pedidoId, $prendaId, $motivo);
    }
}

