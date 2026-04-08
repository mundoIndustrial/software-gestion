<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase as LegacyActualizarPrendaCompletaUseCase;

class ActualizarPrendaCompletaBridge
{
    public function __construct(
        private LegacyActualizarPrendaCompletaUseCase $legacyUseCase,
    ) {}

    public function ejecutarDesdePayload(
        int $prendaId,
        array $prendaPayload,
        array $imagenesProcesadas
    ): void {
        $dto = ActualizarPrendaCompletaDTO::fromRequest(
            $prendaId,
            $prendaPayload,
            $imagenesProcesadas['imagenes_guardadas'] ?? [],
            $imagenesProcesadas['imagenes_existentes'] ?? [],
            $imagenesProcesadas['fotos_telas_procesadas'] ?? [],
            $imagenesProcesadas['fotos_proceso_nuevo'] ?? [],
            $imagenesProcesadas['fotos_color_procesadas'] ?? [],
            $imagenesProcesadas['fotos_proceso_tallas_nuevo'] ?? [],
        );

        $this->legacyUseCase->ejecutar($dto);
    }
}

