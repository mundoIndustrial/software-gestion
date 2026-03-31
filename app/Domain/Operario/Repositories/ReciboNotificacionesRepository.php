<?php

namespace App\Domain\Operario\Repositories;

use Illuminate\Support\Collection;

interface ReciboNotificacionesRepository
{
    /**
     * @return Collection<int, \App\Models\ConsecutivoReciboPedido>
     */
    public function listarNoVistas(
        int $userId,
        string $tipoRecibo,
        int $limit,
        ?\DateTimeInterface $since,
        ?string $areaFiltro,
        ?string $encargadoNormalizado,
        bool $soloAsignadosAlEncargado
    ): Collection;

    public function existeRecibo(int $reciboId, string $tipoRecibo): bool;

    public function marcarLeida(int $userId, int $reciboId, string $tipoRecibo, \DateTimeInterface $fecha): void;

    /**
     * Marca como leÃ­das todas las notificaciones no vistas segÃºn los mismos filtros de listar.
     *
     * @return int Cantidad de recibos marcados como vistos (segÃºn los ids encontrados)
     */
    public function marcarTodasLeidas(
        int $userId,
        string $tipoRecibo,
        ?string $areaFiltro,
        ?string $encargadoNormalizado,
        bool $soloAsignadosAlEncargado,
        \DateTimeInterface $fecha
    ): int;
}

