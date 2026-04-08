<?php

namespace App\Application\Services\Asesores;

use App\Models\Cotizacion;

class ObtenerDatosCotizacionService
{
    public function obtenerParaAsesor(int $cotizacionId, int $asesorId): ?array
    {
        \Log::info('[obtenerDatosCotizacion] Iniciando carga', [
            'cotizacion_id' => $cotizacionId,
            'usuario_id' => $asesorId,
            'timestamp' => now(),
        ]);

        $cotizacion = Cotizacion::query()
            ->where('id', $cotizacionId)
            ->where('asesor_id', $asesorId)
            ->first();

        if (!$cotizacion) {
            \Log::warning('[obtenerDatosCotizacion] cotizacion no encontrada o sin permisos', [
                'cotizacion_id' => $cotizacionId,
                'usuario_id' => $asesorId,
            ]);

            return null;
        }

        $cotizacionConRelaciones = Cotizacion::with([
            'tipoCotizacion:id,nombre',
            'prendas' => function ($query) {
                $query->with([
                    'telas' => function ($q) {
                        $q->with(['color:id,nombre', 'tela:id,nombre']);
                    },
                    'fotos:id,prenda_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                    'telaFotos:id,prenda_tela_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                    'tallas:id,prenda_cot_id,talla,cantidad',
                    'variantes:id,prenda_cot_id,tipo_prenda,es_jean_pantalon,tipo_jean_pantalon,genero_id,color,tipo_manga_id,tipo_broche_id,obs_broche,tiene_bolsillos,obs_bolsillos,aplica_manga,tipo_manga,obs_manga,aplica_broche,tiene_reflectivo,obs_reflectivo,descripcion_adicional,telas_multiples',
                    'variantes.manga:id,nombre',
                    'variantes.broche:id,nombre',
                    'variantes.genero:id,nombre',
                ]);
            },
            'logoCotizacion',
        ])->find($cotizacionId);

        if (!$cotizacionConRelaciones) {
            throw new \RuntimeException('Error al cargar datos completos de la cotizacion');
        }

        $prendas = $cotizacionConRelaciones->prendas->map(function ($prenda) {
            $telas = [];
            if ($prenda->telas) {
                $telas = $prenda->telas->map(function ($tela) use ($prenda) {
                    $fotosTela = [];
                    if ($prenda->telaFotos) {
                        $fotosTela = $prenda->telaFotos
                            ->where('prenda_tela_cot_id', $tela->id)
                            ->map(function ($foto) {
                                $ruta = $foto->ruta_webp;
                                if ($ruta && !str_starts_with($ruta, '/')) {
                                    $ruta = '/storage/' . $ruta;
                                }
                                return $ruta;
                            })
                            ->toArray();
                    }

                    return [
                        'id' => $tela->id,
                        'color' => $tela->color ? [
                            'id' => $tela->color->id,
                            'nombre' => $tela->color->nombre,
                        ] : null,
                        'tela' => $tela->tela ? [
                            'id' => $tela->tela->id,
                            'nombre' => $tela->tela->nombre,
                        ] : null,
                        'referencia' => $tela->referencia ?? '',
                        'fotos' => $fotosTela,
                    ];
                })->toArray();
            }

            $fotos = [];
            if ($prenda->fotos) {
                $fotos = $prenda->fotos->map(function ($foto) {
                    $ruta = $foto->ruta_webp;
                    if ($ruta && !str_starts_with($ruta, '/')) {
                        $ruta = '/storage/' . $ruta;
                    }
                    return $ruta;
                })->toArray();
            }

            $variantes = $prenda->variantes ? $prenda->variantes->map(function ($var) {
                return [
                    'id' => $var->id,
                    'tipo_manga_id' => $var->tipo_manga_id,
                    'tipo_manga_nombre' => $var->manga ? $var->manga->nombre : null,
                    'tipo_broche_id' => $var->tipo_broche_id,
                    'tipo_broche_nombre' => $var->broche ? $var->broche->nombre : null,
                    'tiene_bolsillos' => $var->tiene_bolsillos ?? false,
                    'aplica_manga' => $var->aplica_manga ?? false,
                    'aplica_broche' => $var->aplica_broche ?? false,
                    'tiene_reflectivo' => $var->tiene_reflectivo ?? false,
                    'obs_manga' => $var->obs_manga,
                    'obs_bolsillos' => $var->obs_bolsillos,
                    'obs_broche' => $var->obs_broche,
                    'obs_reflectivo' => $var->obs_reflectivo,
                ];
            })->toArray() : [];

            $tallasFormateadas = [];
            if ($prenda->tallas) {
                $tallasFormateadas = $prenda->tallas->map(function ($talla) {
                    return [
                        'id' => $talla->id,
                        'talla' => $talla->talla,
                        'cantidad' => $talla->cantidad,
                    ];
                })->toArray();
            }

            $genero = null;
            if ($prenda->variantes && $prenda->variantes->isNotEmpty()) {
                $primeraVariante = $prenda->variantes->first();
                if ($primeraVariante->genero) {
                    $genero = [
                        'id' => $primeraVariante->genero->id,
                        'nombre' => $primeraVariante->genero->nombre,
                    ];
                }
            }

            return [
                'id' => $prenda->id,
                'nombre' => $prenda->nombre_producto ?? 'Prenda sin nombre',
                'nombre_producto' => $prenda->nombre_producto,
                'descripcion' => $prenda->descripcion ?? '',
                'cantidad' => $prenda->cantidad ?? 1,
                'texto_personalizado_tallas' => $prenda->texto_personalizado_tallas,
                'prenda_bodega' => $prenda->prenda_bodega ?? 0,
                'telas' => $telas,
                'fotos' => $fotos,
                'variantes' => $variantes,
                'tallas' => $tallasFormateadas,
                'genero' => $genero,
                'tipo' => 'prenda',
            ];
        })->toArray();

        $logo = null;
        if ($cotizacionConRelaciones->logoCotizacion) {
            $logo = [
                'id' => $cotizacionConRelaciones->logoCotizacion->id,
                'tipo_logo' => $cotizacionConRelaciones->logoCotizacion->tipo_logo ?? 'N/A',
                'tipo' => 'logo',
            ];
        }

        return [
            'prendas' => $prendas,
            'logo' => $logo,
            'tiene_prendas' => count($prendas) > 0,
            'tiene_logo' => $logo !== null,
        ];
    }
}
