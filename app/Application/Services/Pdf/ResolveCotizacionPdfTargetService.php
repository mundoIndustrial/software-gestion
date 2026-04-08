<?php

namespace App\Application\Services\Pdf;

use App\Models\Cotizacion;

final class ResolveCotizacionPdfTargetService
{
    public function resolvePath(int $cotizacionId, ?string $tipo, array $query): string
    {
        $resolvedTipo = $this->resolveTipo($cotizacionId, $tipo);

        $path = match ($resolvedTipo) {
            'logo' => "/cotizacion/{$cotizacionId}/pdf/logo",
            'combinada' => "/cotizacion/{$cotizacionId}/pdf/combinada",
            'epp' => "/cotizacion/{$cotizacionId}/pdf/epp",
            default => "/cotizacion/{$cotizacionId}/pdf/prenda",
        };

        unset($query['tipo']);
        if (!empty($query)) {
            $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
        }

        return $path;
    }

    private function resolveTipo(int $cotizacionId, ?string $tipo): string
    {
        if ($tipo !== null && $tipo !== '' && $tipo !== 'prenda') {
            return strtolower(trim($tipo));
        }

        $cotizacion = Cotizacion::with(['prendas', 'logoCotizacion'])->findOrFail($cotizacionId);
        $tienePrendas = ($cotizacion->prendas && $cotizacion->prendas->count() > 0);
        $tieneLogo = !is_null($cotizacion->logoCotizacion);

        if ($tienePrendas && $tieneLogo) {
            return 'combinada';
        }

        if ($tieneLogo && !$tienePrendas) {
            return 'logo';
        }

        return 'prenda';
    }
}

