<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\AgregarImagenTelaUseCaseContract;

use App\Application\Pedidos\DTOs\AgregarImagenTelaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedidoColorTela;

/**
 * Use Case para agregar imagen de tela a una combinación color-tela
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * Maneja la creación de registro en prenda_fotos_tela_pedido
 * Antes: 28 lineas | despues: ~20 lineas | Reducción: ~28%
 */
final class AgregarImagenTelaUseCase implements AgregarImagenTelaUseCaseContract
{
    use ManejaPedidosUseCase;

    public function execute(AgregarImagenTelaDTO $dto)
    {
        // CENTRALIZADO: Validar color-tela existe (trait)
        $colorTela = PrendaPedidoColorTela::find($dto->colorTelaId);
        $this->validarObjetoExiste($colorTela, 'Color-Tela', $dto->colorTelaId);

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

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {AgregarImagenTelaUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}




