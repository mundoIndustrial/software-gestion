<?php

namespace App\Domain\Operario\Repositories;

interface TablaOriginalBodegaNovedadesRepository
{
    public function appendNovedadesPorNumeroPedido(int $numeroPedido, string $novedadFormato): void;
}

