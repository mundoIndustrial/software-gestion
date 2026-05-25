<?php

namespace App\Application\Services\Asesores;

use App\Models\ColorPrenda;
use App\Models\PedidoAnexoHistorial;
use App\Models\TelaPrenda;

class PrendaPedidoEdicionAuditoriaService
{
    public function registrarPrendaNueva(
        int $pedidoId,
        ?int $prendaId,
        string $nombrePrenda,
        bool $notificar = true
    ): void {
        PedidoAnexoHistorial::registrarPrendaNueva(
            $pedidoId,
            $prendaId,
            $nombrePrenda,
            $notificar
        );
    }

    public function registrarEppNuevo(
        int $pedidoId,
        int $pedidoEppId,
        int $eppIdNuevo,
        bool $notificar = true
    ): void {
        PedidoAnexoHistorial::registrarEppNuevo(
            $pedidoId,
            $pedidoEppId,
            $eppIdNuevo,
            $notificar
        );
    }

    public function registrarPrendaEditada(
        int $pedidoId,
        int $prendaId,
        string $nombrePrenda,
        string $seccion,
        ?string $detalle,
        bool $notificar = true
    ): void {
        PedidoAnexoHistorial::registrarPrendaEditada(
            $pedidoId,
            $prendaId,
            $nombrePrenda,
            $seccion,
            $detalle,
            $notificar
        );
    }

    public function registrarEppHomologado(
        int $pedidoId,
        int $pedidoEppId,
        int $eppIdHomologado,
        bool $notificar = true
    ): void {
        PedidoAnexoHistorial::registrarEppHomologado(
            $pedidoId,
            $pedidoEppId,
            $eppIdHomologado,
            $notificar
        );
    }

    public function obtenerNombresColores(array $colorIds): array
    {
        if (empty($colorIds)) {
            return [];
        }

        return ColorPrenda::query()
            ->whereIn('id', $colorIds)
            ->pluck('nombre')
            ->toArray();
    }

    public function obtenerNombresTelas(array $telaIds): array
    {
        if (empty($telaIds)) {
            return [];
        }

        return TelaPrenda::query()
            ->whereIn('id', $telaIds)
            ->pluck('nombre')
            ->toArray();
    }
}
