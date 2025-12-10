<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Auth;

class CotizacionesFiltrosController extends Controller
{
    /**
     * Obtener valores únicos para los filtros
     */
    public function valores()
    {
        $usuarioId = Auth::id();

        \Log::info('CotizacionesFiltrosController: Iniciando', [
            'usuario_id' => $usuarioId,
            'usuario_autenticado' => Auth::check(),
        ]);

        // Obtener cotizaciones del usuario con relación tipoCotizacion
        $cotizaciones = Cotizacion::where('asesor_id', $usuarioId)
            ->with('tipoCotizacion')
            ->get();

        \Log::info('CotizacionesFiltrosController: Obteniendo valores de filtro', [
            'usuario_id' => $usuarioId,
            'total_cotizaciones' => $cotizaciones->count(),
            'cotizaciones_ids' => $cotizaciones->pluck('id')->toArray(),
        ]);

        // Mapeo de códigos a nombres legibles
        $tiposMap = [
            'P' => 'Prenda',
            'L' => 'Logo',
            'PL' => 'Prenda/Logo',
        ];

        // Mapeo de estados a nombres legibles
        $estadosMap = [
            'BORRADOR' => 'Borrador',
            'ENVIADA_CONTADOR' => 'Enviada a Contador',
            'APROBADA_CONTADOR' => 'Aprobada por Contador',
            'ENVIADA_APROBADOR' => 'Enviada a Aprobador',
            'APROBADA_APROBADOR' => 'Aprobada por Aprobador',
            'ACEPTADA' => 'Aceptada',
            'RECHAZADA' => 'Rechazada',
        ];

        $datos = [
            'fechas' => $cotizaciones->pluck('created_at')
                ->map(fn($f) => $f->format('d/m/Y'))
                ->unique()
                ->values()
                ->toArray(),
            'codigos' => $cotizaciones->pluck('numero_cotizacion')
                ->filter()
                ->unique()
                ->values()
                ->toArray(),
            'clientes' => $cotizaciones->pluck('cliente')
                ->unique()
                ->values()
                ->toArray(),
            'tipos' => $cotizaciones->map(fn($c) => $tiposMap[$c->tipoCotizacion?->codigo ?? 'P'] ?? 'Prenda')
                ->unique()
                ->values()
                ->toArray(),
            'estados' => $cotizaciones->pluck('estado')
                ->map(fn($e) => $estadosMap[$e] ?? $e)
                ->unique()
                ->values()
                ->toArray(),
        ];

        \Log::info('CotizacionesFiltrosController: Valores de filtro', $datos);

        return response()->json($datos);
    }
}
