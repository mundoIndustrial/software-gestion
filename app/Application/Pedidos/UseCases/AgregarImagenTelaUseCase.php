<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarImagenTelaDTO;
use App\Models\PrendaPedidoColorTela;

/**
 * Use Case para agregar imagen de tela a una combinación color-tela
 * 
 * Maneja la creación de registro en prenda_fotos_tela_pedido
 */
final class AgregarImagenTelaUseCase
{
    public function execute(AgregarImagenTelaDTO $dto)
    {
        $colorTela = PrendaPedidoColorTela::findOrFail($dto->colorTelaId);

        return $colorTela->fotos()->create([
            'ruta_original' => $dto->rutaOriginal,
            'ruta_webp' => $dto->rutaWebp ?? $this->generarRutaWebp($dto->rutaOriginal),
            'orden' => $dto->orden,
        ]);
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
