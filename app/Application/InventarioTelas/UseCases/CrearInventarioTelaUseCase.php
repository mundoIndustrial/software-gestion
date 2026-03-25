<?php

namespace App\Application\InventarioTelas\UseCases;

use App\Domain\InventarioTelas\Repositories\InventarioTelaRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CrearInventarioTelaUseCase
{
    public function __construct(
        private InventarioTelaRepositoryInterface $repository
    ) {}

    public function ejecutar(array $datos)
    {
        $tela = $this->repository->crear($datos);

        // Registrar en historial si hay stock inicial
        if (isset($datos['stock']) && $datos['stock'] > 0) {
            $this->repository->registrarMovimiento(
                telaId: $tela->id,
                usuarioId: Auth::id(),
                tipoAccion: 'entrada',
                cantidad: $datos['stock'],
                stockAnterior: 0,
                stockNuevo: $datos['stock'],
                observaciones: 'Stock inicial al crear la tela'
            );
        }

        return $tela;
    }
}
