<?php

namespace App\Http\Controllers;

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
        // Redirigir al nuevo controlador
        return redirect()->route('asesores.cotizacion.pdf.prenda', ['id' => $id]);
    }
}
