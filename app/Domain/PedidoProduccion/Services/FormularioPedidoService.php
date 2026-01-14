<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio para cargar datos de formularios
 * Responsabilidad: Obtener y preparar datos para formularios de creación de pedidos
 */
class FormularioPedidoService
{
    public function __construct(
        private TransformadorCotizacionService $transformador
    ) {}

    /**
     * Obtener datos para formulario de creación desde cotización
     */
    public function obtenerDatosFormularioCrearDesdeCotizacion()
    {
        $cotizaciones = Cotizacion::where('asesor_id', Auth::id())
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->with([
                'asesor',
                'cliente',
                'prendasCotizaciones.variantes.color',
                'prendasCotizaciones.variantes.tela',
                'prendasCotizaciones.variantes.tipoManga',
                'prendasCotizaciones.variantes.tipoBroche',
                'logoCotizacion.fotos'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return $cotizaciones;
    }

    /**
     * Obtener datos para router de creación (soporta múltiples flujos)
     */
    public function obtenerDatosRouter(string $tipo = 'cotizacion'): array
    {
        $data = ['tipoInicial' => $tipo];

        if ($tipo === 'cotizacion') {
            $cotizaciones = Cotizacion::where('asesor_id', Auth::id())
                ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
                ->with(['cliente', 'asesor', 'prendasCotizaciones'])
                ->get();

            $data['cotizacionesData'] = $this->transformador->transformarCotizacionesParaFrontend($cotizaciones);
        }

        return $data;
    }
}
