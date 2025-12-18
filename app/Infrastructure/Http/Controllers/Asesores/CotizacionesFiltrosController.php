<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Helpers\EstadoHelper;
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
            ->with(['tipoCotizacion', 'cliente'])
            ->get();

        \Log::info('CotizacionesFiltrosController: Obteniendo valores de filtro', [
            'usuario_id' => $usuarioId,
            'total_cotizaciones' => $cotizaciones->count(),
            'cotizaciones_ids' => $cotizaciones->pluck('id')->toArray(),
            'estados_en_bd' => $cotizaciones->pluck('estado')->unique()->toArray(),
        ]);

        // Mapeo de códigos a nombres legibles
        $tiposMap = [
            'PL' => 'Combinada',
            'L' => 'Logo',
            'RF' => 'Reflectivo',
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
            'clientes' => $cotizaciones->map(function($cot) {
                    if (is_object($cot->cliente)) {
                        return $cot->cliente->nombre ?? '';
                    }
                    return $cot->cliente ?? '';
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray(),
            'tipos' => $cotizaciones->map(function($c) use ($tiposMap) {
                    $codigo = $c->tipoCotizacion?->codigo ?? 'PL'; // Default a Combinada
                    return $tiposMap[$codigo] ?? $codigo;
                })
                ->filter(fn($v) => $v !== null && $v !== '')
                ->unique()
                ->values()
                ->toArray(),
            'estados' => $cotizaciones->pluck('estado')
                ->map(fn($e) => EstadoHelper::labelCotizacion($e))
                ->filter(fn($v) => $v !== null && $v !== '')
                ->unique()
                ->values()
                ->toArray(),
        ];

        \Log::info('CotizacionesFiltrosController: Valores de filtro', $datos);

        return response()->json($datos);
    }
}
