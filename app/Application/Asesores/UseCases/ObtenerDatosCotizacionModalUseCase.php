<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Cotizacion\Repositories\CotizacionDetalleRepositoryInterface;

final class ObtenerDatosCotizacionModalUseCase
{
    public function __construct(
        private readonly CotizacionDetalleRepositoryInterface $cotizacionDetalleRepository
    ) {}

    public function ejecutar(int $cotizacionId): ?array
    {
        $cotizacion = $this->cotizacionDetalleRepository->obtenerCotizacionParaModal($cotizacionId);

        if ($cotizacion === null) {
            return null;
        }

        return [
            'cotizacion' => [
                'id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'asesora_nombre' => $cotizacion->asesor ? $cotizacion->asesor->name : 'N/A',
                'empresa' => $cotizacion->empresa_solicitante ?? 'N/A',
                'nombre_cliente' => $cotizacion->cliente ? $cotizacion->cliente->nombre : 'N/A',
                'created_at' => $cotizacion->created_at,
                'estado' => $cotizacion->estado,
            ],
            'prendas_cotizaciones' => $cotizacion->prendas->map(function ($prenda, $index) {
                $descripcionFormateada = method_exists($prenda, 'generarDescripcionDetallada')
                    ? $prenda->generarDescripcionDetallada($index + 1)
                    : ($prenda->descripcion ?? null);

                return [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_producto ?? 'Prenda sin nombre',
                    'cantidad' => $prenda->cantidad ?? 0,
                    'descripcion' => $prenda->descripcion ?? null,
                    'descripcion_formateada' => $descripcionFormateada,
                    'detalles_proceso' => $prenda->descripcion ?? null,
                    'fotos' => $prenda->fotos ? $prenda->fotos->map(fn($foto) => $foto->url)->toArray() : [],
                    'telas' => $prenda->telas ? $prenda->telas->map(function ($tela) {
                        return [
                            'id' => $tela->id,
                            'color' => $tela->color ?? null,
                            'nombre_tela' => $tela->tela->nombre ?? null,
                            'referencia' => $tela->tela->referencia ?? null,
                            'url_imagen' => $tela->url ?? asset($tela->ruta_webp),
                        ];
                    })->toArray() : [],
                    'tela_fotos' => $prenda->telaFotos ? $prenda->telaFotos->map(fn($foto) => $foto->url)->toArray() : [],
                    'tallas' => $prenda->tallas ? $prenda->tallas->map(function ($talla) {
                        return [
                            'id' => $talla->id,
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                        ];
                    })->toArray() : [],
                    'variantes' => $prenda->variantes ? $prenda->variantes->map(function ($variante) {
                        return [
                            'id' => $variante->id,
                            'tipo_prenda' => $variante->tipo_prenda ?? null,
                            'es_jean_pantalon' => $variante->es_jean_pantalon ?? null,
                            'tipo_jean_pantalon' => $variante->tipo_jean_pantalon ?? null,
                            'genero_id' => $variante->genero_id ?? null,
                            'color' => $variante->color ?? null,
                            'tiene_bolsillos' => $variante->tiene_bolsillos ?? null,
                            'aplica_manga' => $variante->aplica_manga ?? null,
                            'tipo_manga' => $variante->tipo_manga ?? null,
                            'aplica_broche' => $variante->aplica_broche ?? null,
                            'tipo_broche_id' => $variante->tipo_broche_id ?? null,
                            'tiene_reflectivo' => $variante->tiene_reflectivo ?? null,
                            'descripcion_adicional' => $variante->descripcion_adicional ?? null,
                            'telas_multiples' => $variante->telas_multiples ? json_decode($variante->telas_multiples, true) : null,
                        ];
                    })->toArray() : [],
                ];
            })->toArray(),
        ];
    }
}

