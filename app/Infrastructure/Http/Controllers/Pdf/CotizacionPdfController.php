<?php

namespace App\Infrastructure\Http\Controllers\Pdf;

use App\Application\Services\Pdf\ResolveCotizacionPdfTargetService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class CotizacionPdfController extends Controller
{
    public function __construct(
        private readonly ResolveCotizacionPdfTargetService $resolveCotizacionPdfTargetService
    ) {
    }

    public function show(int $id, Request $request)
    {
        return $this->generarPDF($id, $request);
    }

    public function generarPDF(int $id, Request $request)
    {
        $tipoParam = $request->query('tipo');
        $tipo = is_null($tipoParam) ? null : (string) $tipoParam;

        $path = $this->resolveCotizacionPdfTargetService->resolvePath($id, $tipo, $request->query());

        return redirect($path);
    }
}

