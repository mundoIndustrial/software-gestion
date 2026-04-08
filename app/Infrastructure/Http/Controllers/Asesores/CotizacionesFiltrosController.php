<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ObtenerValoresFiltrosCotizacionesAsesorUseCase;
use App\Http\Controllers\Controller;
use App\Helpers\EstadoHelper;
use Illuminate\Support\Facades\Auth;

class CotizacionesFiltrosController extends Controller
{
    public function __construct(
        private readonly ObtenerValoresFiltrosCotizacionesAsesorUseCase $obtenerValoresFiltrosCotizacionesAsesorUseCase
    ) {
    }

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

        $datos = $this->obtenerValoresFiltrosCotizacionesAsesorUseCase->ejecutar((int) $usuarioId);

        $datos['estados'] = collect($datos['estados'])
            ->map(fn($estado) => EstadoHelper::labelCotizacion($estado))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->unique()
            ->values()
            ->toArray();

        \Log::info('CotizacionesFiltrosController: Obteniendo valores de filtro', [
            'usuario_id' => $usuarioId,
            'total_cotizaciones' => $datos['total'] ?? 0,
            'estados_en_bd' => $datos['estados'] ?? [],
        ]);

        \Log::info('CotizacionesFiltrosController: Valores de filtro', $datos);

        return response()->json($datos);
    }
}
