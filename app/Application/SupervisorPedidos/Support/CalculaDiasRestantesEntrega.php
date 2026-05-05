<?php

namespace App\Application\SupervisorPedidos\Support;

use Carbon\Carbon;

trait CalculaDiasRestantesEntrega
{
    protected function calcularDiasRestantesEntrega(
        mixed $aprobadoPorCarteraEn,
        ?int $diaDeEntrega,
        mixed $fechaEstimadaDeEntrega
    ): ?int {
        if (empty($fechaEstimadaDeEntrega) || empty($aprobadoPorCarteraEn) || empty($diaDeEntrega) || $diaDeEntrega <= 0) {
            return null;
        }

        try {
            $fechaAprobacion = Carbon::parse($aprobadoPorCarteraEn)->startOfDay();
            $hoy = now()->startOfDay();
            $cursor = $fechaAprobacion->copy()->addDay();
            $diasHabilesTranscurridos = 0;

            while ($cursor->lte($hoy)) {
                if ($cursor->isBusinessDay()) {
                    $diasHabilesTranscurridos++;
                }
                $cursor->addDay();
            }

            return max(0, $diaDeEntrega - $diasHabilesTranscurridos);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

