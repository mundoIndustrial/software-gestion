<?php

namespace App\Domain\PedidosLogo\Policies;

use App\Domain\PedidosLogo\Enums\AreaProcesoLogo;

final class AreasPermitidasPolicy
{
    public function areasPermitidas(string $filtro): array
    {
        $filtro = $filtro === 'estampado' ? 'estampado' : 'bordado';

        if ($filtro === 'estampado') {
            return [
                AreaProcesoLogo::CREACION_DE_ORDEN,
                AreaProcesoLogo::PENDIENTE_DISENO,
                AreaProcesoLogo::DISENO,
                AreaProcesoLogo::PENDIENTE_CONFIRMAR,
                AreaProcesoLogo::HACIENDO_MUESTRA,
                AreaProcesoLogo::ESTAMPANDO,
                AreaProcesoLogo::ENTREGADO,
                AreaProcesoLogo::ANULADO,
                AreaProcesoLogo::PENDIENTE,
            ];
        }

        return [
            AreaProcesoLogo::CREACION_DE_ORDEN,
            AreaProcesoLogo::PENDIENTE_DISENO,
            AreaProcesoLogo::DISENO,
            AreaProcesoLogo::PENDIENTE_CONFIRMAR,
            AreaProcesoLogo::CORTE_Y_APLIQUE,
            AreaProcesoLogo::HACIENDO_MUESTRA,
            AreaProcesoLogo::BORDANDO,
            AreaProcesoLogo::ENTREGADO,
            AreaProcesoLogo::ANULADO,
            AreaProcesoLogo::PENDIENTE,
        ];
    }

    public function esAreaPermitida(string $area, string $filtro): bool
    {
        return in_array($area, $this->areasPermitidas($filtro), true);
    }
}
