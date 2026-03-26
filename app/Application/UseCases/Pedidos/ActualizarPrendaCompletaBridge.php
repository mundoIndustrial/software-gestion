<?php

namespace App\Application\UseCases\Pedidos;

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
        array $imagenesGuardadas,
        array $imagenesExistentes,
        array $fotosTelasProcesadas,
        array $fotosProcesoNuevo,
        array $fotosColorProcesadas,
        array $fotosProcesoTallasNuevo
    ): void {
        $dto = ActualizarPrendaCompletaDTO::fromRequest(
            $prendaId,
            $prendaPayload,
            $imagenesGuardadas,
            $imagenesExistentes,
            $fotosTelasProcesadas,
            $fotosProcesoNuevo,
            $fotosColorProcesadas,
            $fotosProcesoTallasNuevo,
        );

        $this->legacyUseCase->ejecutar($dto);
    }
}

