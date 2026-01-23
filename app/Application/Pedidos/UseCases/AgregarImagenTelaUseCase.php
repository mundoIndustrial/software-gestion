<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarImagenTelaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedidoColorTela;

/**
 * Use Case para agregar imagen de tela a una combinación color-tela
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Maneja la creación de registro en prenda_fotos_tela_pedido
 * 
 * Antes: 28 líneas | Después: ~20 líneas | Reducción: ~28%
 */
final class AgregarImagenTelaUseCase
{
    use ManejaPedidosUseCase;

    public function execute(AgregarImagenTelaDTO $dto)
    {
        // CENTRALIZADO: Validar color-tela existe (trait)
        $colorTela = $this->validarObjetoExiste(
            PrendaPedidoColorTela::find($dto->colorTelaId),
            "Color-Tela con ID {$dto->colorTelaId}"
        );

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
