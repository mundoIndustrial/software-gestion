<?php

namespace App\Application\InventarioTelas\UseCases;

use App\Domain\InventarioTelas\Repositories\InventarioTelaRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AjustarStockInventarioTelaUseCase
{
    public function __construct(
        private InventarioTelaRepositoryInterface $repository
    ) {}

    public function ejecutar(int $telaId, string $tipoAccion, float $cantidad, ?string $observaciones = null)
    {
        $tela = $this->repository->obtenerPorId($telaId);
        $stockAnterior = $tela->stock;

        // Calcular nuevo stock
        $nuevoStock = $tipoAccion === 'entrada' 
            ? $stockAnterior + $cantidad 
            : $stockAnterior - $cantidad;

        // Validar que el stock no sea negativo
        if ($nuevoStock < 0) {
            throw new \InvalidArgumentException('El stock no puede ser negativo');
        }

        // Actualizar stock
        $this->repository->actualizarStock($telaId, $nuevoStock);

        // Registrar en historial
        $this->repository->registrarMovimiento(
            telaId: $telaId,
            usuarioId: Auth::id(),
            tipoAccion: $tipoAccion,
            cantidad: $cantidad,
            stockAnterior: $stockAnterior,
            stockNuevo: $nuevoStock,
            observaciones: $observaciones
        );

        return [
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $nuevoStock
        ];
    }
}
