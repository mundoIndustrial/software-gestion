<?php

namespace App\Infrastructure\Repositories\Pedidos;

use App\Models\Cotizacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Repositorio legacy de acceso a cotizaciones usado por flujos de Pedidos.
 *
 * Mientras exista esta variante, debe vivir en infraestructura porque depende
 * de Eloquent y del contexto de autenticación.
 */
class CotizacionRepository
{
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
                'prendasCotizaciones.variantes.tipoBrocheBoton',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function obtenerCotizacionCompleta(int $cotizacionId): ?Cotizacion
    {
        return Cotizacion::with([
            'tipoCotizacion',
            'cliente',
            'asesor',
            'prendasCotizaciones' => function ($query) {
                $query->with([
                    'variantes.genero',
                    'variantes.color',
                    'variantes.tela',
                    'variantes.tipoManga',
                    'variantes.tipoBrocheBoton',
                    'variantes.tipoPuno',
                    'tallas',
                    'procesos',
                ])->orderBy('orden');
            },
            'logoCotizacion' => function ($query) {
                $query->with([
                    'prendasTecnicas.tallas',
                    'prendasTecnicas.procesos',
                    'fotos',
                ]);
            },
        ])->find($cotizacionId);
    }

    public function esCotizacionLogo(Cotizacion $cotizacion): bool
    {
        return $cotizacion->tipoCotizacion
            && $cotizacion->tipoCotizacion->codigo === 'L';
    }

    public function esCotizacionReflectivo(Cotizacion $cotizacion): bool
    {
        return $cotizacion->tipoCotizacion
            && $cotizacion->tipoCotizacion->codigo === 'RF';
    }

    public function obtenerPrendasCotizacion(int $cotizacionId): Collection
    {
        $cotizacion = $this->obtenerCotizacionCompleta($cotizacionId);

        if (!$cotizacion) {
            return collect([]);
        }

        return $cotizacion->prendasCotizaciones;
    }

    public function obtenerEspecificaciones(Cotizacion $cotizacion): array
    {
        return $cotizacion->especificaciones ?? [];
    }

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
            'prendas.detalle',
            'logoCotizacion.fotos',
            'logoCotizacion.prendas.tipoLogo',
            'logoCotizacion.prendas.fotos',
            'reflectivo.fotos',
        ])->findOrFail($cotizacionId);

        $especificacionesConvertidas = $this->convertirEspecificacionesAlFormatoNuevo(
            $cotizacion->especificaciones ?? []
        );

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
