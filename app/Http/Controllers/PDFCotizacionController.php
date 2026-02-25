<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;

/**
 * PDFCotizacionController - DEPRECATED
 * 
 * Este controlador ha sido refactorizado.
 * Usa los controladores específicos según el tipo de PDF:
 * 
 * - PDFPrendaController → PDF de prendas
 * - PDFLogoController → PDF de logos/bordados
 * - PDFReflectivoController → PDF de reflectivos
 * 
 * Las rutas legacy aún apuntan aquí para compatibilidad,
 * pero redirigen al controlador correspondiente.
 */
class PDFCotizacionController extends Controller
{
    /**
     * Legacy method - Redirige al controlador de prenda
     * @deprecated Use PDFPrendaController::generate() instead
     */
    public function generarPDF($id, Request $request)
    {
        $tipoParam = $request->query('tipo');
        $tipo = is_null($tipoParam) ? null : strtolower(trim((string) $tipoParam));

        if (is_null($tipo) || $tipo === '' || $tipo === 'prenda') {
            $cotizacion = Cotizacion::with(['prendas', 'logoCotizacion'])->findOrFail($id);

            $tienePrendas = ($cotizacion->prendas && $cotizacion->prendas->count() > 0);
            $tieneLogo = !is_null($cotizacion->logoCotizacion);

            if ($tienePrendas && $tieneLogo) {
                $tipo = 'combinada';
            } elseif ($tieneLogo && !$tienePrendas) {
                $tipo = 'logo';
            } else {
                $tipo = 'prenda';
            }
        }

        $path = match ($tipo) {
            'logo' => "/cotizacion/{$id}/pdf/logo",
            'combinada' => "/cotizacion/{$id}/pdf/combinada",
            'epp' => "/cotizacion/{$id}/pdf/epp",
            default => "/cotizacion/{$id}/pdf/prenda",
        };

        return redirect($path);
    }
}
