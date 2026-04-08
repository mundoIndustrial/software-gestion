<?php

namespace App\Infrastructure\Http\Controllers\Pdf;

use App\Application\Services\Pdf\GenerateEppPdfService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class EppPdfController extends Controller
{
    public function __construct(
        private readonly GenerateEppPdfService $generateEppPdfService
    ) {
    }

    public function show(int $id, Request $request)
    {
        return $this->generate($id, $request);
    }

    public function generate(int $id, Request $request)
    {
        try {
            $scale = $request->query->has('scale') && is_numeric($request->query('scale'))
                ? (float) $request->query('scale')
                : null;

            $result = $this->generateEppPdfService->generate($id, $scale);
            $dispositionType = $request->boolean('download') ? 'attachment' : 'inline';

            return response($result['pdfContent'])
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', $dispositionType . '; filename="' . $result['filename'] . '"')
                ->header('Content-Length', strlen($result['pdfContent']))
                ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                ->header('Pragma', 'public')
                ->header('Expires', '0');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada',
            ], 404);
        } catch (\Throwable $e) {
            \Log::error('Error al generar PDF de EPP', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}

