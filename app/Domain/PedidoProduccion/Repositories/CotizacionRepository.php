<?php

namespace App\Domain\PedidoProduccion\Repositories;

use App\Models\Cotizacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Repositorio para acceso a datos de Cotizaciones
 * Responsabilidad única: Encapsular consultas a la base de datos
 */
class CotizacionRepository
{
    /**
     * Obtener cotizaciones aprobadas del asesor actual
     */
    public function obtenerCotizacionesAprobadas(): Collection
    {
        return Cotizacion::where('asesor_id', Auth::id())
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->with([
                'asesor',
                'cliente',
                'prendasCotizaciones.variantes.color',
                'prendasCotizaciones.variantes.tela',
                'prendasCotizaciones.variantes.tipoManga',
                'prendasCotizaciones.variantes.tipoBroche'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener cotización por ID con relaciones completas
     */
    public function obtenerCotizacionCompleta(int $cotizacionId): ?Cotizacion
    {
        return Cotizacion::with([
            'tipoCotizacion',
            'cliente',
            'asesor',
            'prendasCotizaciones' => function($query) {
                $query->with([
                    'variantes.genero',
                    'variantes.color',
                    'variantes.tela',
                    'variantes.tipoManga',
                    'variantes.tipoBroche',
                    'variantes.tipoPuno',
                    'tallas',
                    'procesos'
                ])->orderBy('orden');
            },
            'logoCotizacion' => function($query) {
                $query->with([
                    'prendasTecnicas.tallas',
                    'prendasTecnicas.procesos',
                    'fotos'
                ]);
            }
        ])->find($cotizacionId);
    }

    /**
     * Verificar si una cotización es de tipo LOGO
     */
    public function esCotizacionLogo(Cotizacion $cotizacion): bool
    {
        return $cotizacion->tipoCotizacion && 
               $cotizacion->tipoCotizacion->codigo === 'L';
    }

    /**
     * Verificar si una cotización es de tipo REFLECTIVO
     */
    public function esCotizacionReflectivo(Cotizacion $cotizacion): bool
    {
        return $cotizacion->tipoCotizacion && 
               $cotizacion->tipoCotizacion->codigo === 'RF';
    }

    /**
     * Obtener prendas de una cotización con sus relaciones
     */
    public function obtenerPrendasCotizacion(int $cotizacionId): Collection
    {
        $cotizacion = $this->obtenerCotizacionCompleta($cotizacionId);
        
        if (!$cotizacion) {
            return collect([]);
        }

        return $cotizacion->prendasCotizaciones;
    }

    /**
     * Obtener especificaciones de una cotización
     */
    public function obtenerEspecificaciones(Cotizacion $cotizacion): array
    {
        return $cotizacion->especificaciones ?? [];
    }

    /**
     * Obtener datos completos de una cotización para AJAX
     * Incluye todas las relaciones necesarias
     */
    public function obtenerDatosCompletosParaAjax(int $cotizacionId): array
    {
        $cotizacion = Cotizacion::with([
            'cliente',
            'asesor',
            'tipoCotizacion',
            'prendas.variantes.manga',
            'prendas.variantes.broche',
            'prendas.variantes.genero',
            'prendas.tallas',
            'prendas.fotos',
            'prendas.telas',
            'prendas.telaFotos',
            'logoCotizacion.fotos',
            'logoCotizacion.prendas.tipoLogo',
            'logoCotizacion.prendas.fotos',
            'reflectivo.fotos',
        ])->findOrFail($cotizacionId);

        // Convertir especificaciones del formato antiguo al nuevo
        $especificacionesConvertidas = $this->convertirEspecificacionesAlFormatoNuevo(
            $cotizacion->especificaciones ?? []
        );

        // Extraer forma de pago
        $formaPago = '';
        if (!empty($especificacionesConvertidas['forma_pago']) && is_array($especificacionesConvertidas['forma_pago'])) {
            if (count($especificacionesConvertidas['forma_pago']) > 0) {
                $formaPago = $especificacionesConvertidas['forma_pago'][0]['valor'] ?? '';
            }
        }

        return [
            'id' => $cotizacion->id,
            'numero' => $cotizacion->numero_cotizacion,
            'cliente' => $cotizacion->cliente,
            'asesor' => $cotizacion->asesor,
            'tipo_cotizacion' => $cotizacion->tipoCotizacion,
            'estado' => $cotizacion->estado,
            'forma_pago' => $formaPago,
            'especificaciones' => $especificacionesConvertidas,
            'prendas' => $cotizacion->prendas,
            'logo' => $cotizacion->logoCotizacion,
            'reflectivo' => $cotizacion->reflectivo,
        ];
    }

    /**
     * Convertir especificaciones del formato antiguo al nuevo
     */
    private function convertirEspecificacionesAlFormatoNuevo(array $especificaciones): array
    {
        if (empty($especificaciones)) {
            return [];
        }

        $especificacionesNuevas = [];

        foreach ($especificaciones as $key => $value) {
            if (strpos($key, 'tabla_orden[') === 0) {
                $campo = str_replace(['tabla_orden[', ']'], '', $key);
                $especificacionesNuevas[$campo] = $value;
            } else {
                $especificacionesNuevas[$key] = $value;
            }
        }

        return $especificacionesNuevas;
    }
}
