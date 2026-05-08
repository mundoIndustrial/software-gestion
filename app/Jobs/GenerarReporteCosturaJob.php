<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class GenerarReporteCosturaJob implements ShouldQueue
{
    use Queueable;

    private $grouped;
    private $totalRecibos;
    private $filtros;
    private $diasAntiguedad;
    private $userId;

    public function __construct($grouped, $totalRecibos, $filtros, $diasAntiguedad, $userId)
    {
        $this->grouped = $grouped;
        $this->totalRecibos = $totalRecibos;
        $this->filtros = $filtros;
        $this->diasAntiguedad = $diasAntiguedad;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            $timestamp = now()->format('Ymd_His');
            $filename = "reporte_pendientes_costura_por_area_{$timestamp}.pdf";
            $filePath = "reportes/costura/{$this->userId}/{$filename}";

            $pdf = Pdf::loadView('supervisor-pedidos.reporte-pendientes-costura-pdf', [
                'grouped' => $this->grouped,
                'totalRecibos' => $this->totalRecibos,
                'filtros' => $this->filtros,
                'fechaGeneracion' => now(),
                'diasAntiguedad' => $this->diasAntiguedad,
            ])->setPaper('a4', 'landscape');

            Storage::disk('local')->put($filePath, $pdf->output());

            \Log::info('[GenerarReporteCostura] PDF generado exitosamente', [
                'user_id' => $this->userId,
                'file_path' => $filePath,
                'timestamp' => $timestamp,
            ]);
        } catch (\Exception $e) {
            \Log::error('[GenerarReporteCostura] Error al generar PDF', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
