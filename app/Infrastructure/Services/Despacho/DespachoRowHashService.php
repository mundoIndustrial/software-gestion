<?php

namespace App\Infrastructure\Services\Despacho;

class DespachoRowHashService
{
    public static function generar(
        int $pedidoProduccionId,
        string $tipoItem,
        int $itemId,
        int $tallaId,
        ?int $tallaColorId,
        ?string $genero
    ): string {
        $generoNormalizado = strtoupper(trim((string) ($genero ?? '')));

        return md5(
            $pedidoProduccionId
            . '_'
            . strtolower(trim($tipoItem))
            . '_'
            . $itemId
            . '_'
            . $tallaId
            . '_'
            . ($tallaColorId ?? 0)
            . '_'
            . $generoNormalizado
        );
    }
}
