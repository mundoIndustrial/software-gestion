<?php

namespace App\Support\Operario;

class OperarioDashboardViewHelper
{
    public static function estadoClass(?string $estado): string
    {
        $valor = strtolower(trim((string) $estado));
        if (str_contains($valor, 'ejecución') || str_contains($valor, 'proceso')) {
            return 'en-proceso';
        }
        if (str_contains($valor, 'completada') || str_contains($valor, 'completado')) {
            return 'completada';
        }

        return 'pendiente';
    }

    public static function seleccionarRecibo(array $recibos, string $tipoRecibo, bool $preferirParcial = false): ?array
    {
        $tipoRecibo = strtoupper(trim($tipoRecibo));

        $recibosFiltrados = array_values(array_filter($recibos, function ($recibo) use ($tipoRecibo) {
            return strtoupper(trim((string) ($recibo['tipo_recibo'] ?? ''))) === $tipoRecibo;
        }));

        if (empty($recibosFiltrados)) {
            return null;
        }

        if ($preferirParcial) {
            foreach ($recibosFiltrados as $recibo) {
                if (!empty($recibo['pedido_parcial_id'])) {
                    return $recibo;
                }
            }
        }

        return $recibosFiltrados[0];
    }
}
