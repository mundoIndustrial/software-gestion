<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarImagenEppDTO;
use App\Models\PedidoEpp;

/**
 * Use Case para agregar imagen a un EPP
 * 
 * Maneja la creación de registro en pedido_epp_imagenes
 */
final class AgregarImagenEppUseCase
{
    public function execute(AgregarImagenEppDTO $dto)
    {
        $epp = PedidoEpp::findOrFail($dto->eppId);

        return $epp->imagenes()->create([
            'ruta_original' => $dto->rutaOriginal,
            'ruta_web' => $dto->rutaWeb ?? $this->generarRutaWeb($dto->rutaOriginal),
            'principal' => $dto->principal,
            'orden' => $dto->orden,
        ]);
    }

    private function generarRutaWeb(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
