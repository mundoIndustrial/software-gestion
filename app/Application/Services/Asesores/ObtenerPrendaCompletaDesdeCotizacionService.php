<?php

namespace App\Application\Services\Asesores;

use App\Models\Cotizacion;
use App\Models\LogoCotizacionTecnicaPrendaFoto;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ObtenerPrendaCompletaDesdeCotizacionService
{
    public function obtener(int $cotizacionId, int $prendaId): array
    {
        $cotizacion = Cotizacion::with([
            'tipoCotizacion',
            'prendas' => function ($query) use ($prendaId) {
                $query->where('id', $prendaId)
                    ->with([
                        'telas' => function ($q) {
                            $q->with([
                                'color:id,nombre,codigo',
                                'tela:id,nombre,referencia,descripcion',
                            ]);
                        },
                        'fotos:id,prenda_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                        'telaFotos:id,prenda_cot_id,prenda_tela_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                        'variantes' => function ($q) {
                            $q->with([
                                'manga:id,nombre',
                                'broche:id,nombre',
                                'genero:id,nombre',
                            ]);
                        },
                        'tallas' => function ($q) {
                            $q->with(['genero:id,nombre']);
                        },
                        'logoCotizacionesTecnicas' => function ($q) {
                            $q->with([
                                'tipoLogo:id,nombre',
                                'fotos:id,logo_cotizacion_tecnica_prenda_id,ruta_original,ruta_webp,ruta_miniatura,orden,created_at,updated_at',
                            ]);
                        },
                        'logoCotizacionTelasPrenda',
                    ]);
            },
        ])->find($cotizacionId);

        if (!$cotizacion) {
            return ['status' => 'cotizacion_no_encontrada'];
        }

        if (count($cotizacion->prendas) === 0) {
            return ['status' => 'prenda_no_encontrada'];
        }

        $prenda = $cotizacion->prendas[0];
        $procesosFormato = [];

        $telasFormato = $this->procesarTelas($cotizacion, $prenda);
        $fotosFormato = $this->procesarFotosPrenda($prenda);
        [$tallasDisponibles, $tallasConCantidades, $generosPresentes] = $this->procesarTallasYGeneros($prenda);
        $telasFormato = $this->completarTelasDesdeMultiplesSiAplica($telasFormato, $prenda);
        $variantes = $this->procesarVariantes($prenda);
        $procesosFormato = $this->procesarTecnicasLogo($prenda, $procesosFormato);
        $procesosFormato = $this->agregarReflectivoSiAplica($prenda, $variantes, $procesosFormato);

        return [
            'status' => 'ok',
            'data' => [
                'cotizacion_id' => $cotizacionId,
                'numero_cotizacion' => $cotizacion->numero,
                'generosPresentes' => array_values(array_unique($generosPresentes)),
                'prenda' => [
                    'id' => $prenda->id,
                    'nombre_producto' => $prenda->nombre_producto,
                    'descripcion' => $prenda->descripcion ?? '',
                    'cantidad' => $prenda->cantidad,
                    'prenda_bodega' => $prenda->prenda_bodega,
                    'tallas_disponibles' => $tallasDisponibles,
                    'tallas' => $tallasConCantidades,
                    'telas' => $telasFormato,
                    'fotos' => $fotosFormato,
                    'variantes' => $variantes,
                    'logoCotizacionTelasPrenda' => $prenda->logoCotizacionTelasPrenda ? $prenda->logoCotizacionTelasPrenda->toArray() : [],
                ],
                'procesos' => $procesosFormato,
            ],
        ];
    }

    private function procesarTelas(Cotizacion $cotizacion, $prenda): array
    {
        $telasFormato = [];

        $esLogoCotizacion = $cotizacion->tipoCotizacion
            && in_array(strtolower((string) $cotizacion->tipoCotizacion->nombre), ['logo', 'bordado'], true);

        if ($esLogoCotizacion && $prenda->logoCotizacionTelasPrenda && count($prenda->logoCotizacionTelasPrenda) > 0) {
            foreach ($prenda->logoCotizacionTelasPrenda as $telaLogo) {
                $telaData = [
                    'id' => $telaLogo->id,
                    'nombre_tela' => $telaLogo->tela ?? 'SIN NOMBRE',
                    'color' => $telaLogo->color ?? '',
                    'referencia' => $telaLogo->ref ?? '',
                    'descripcion' => '',
                    'imagenes' => [],
                ];

                if ($telaLogo->img) {
                    $telaData['imagenes'][] = [
                        'ruta' => $telaLogo->img,
                        'ruta_webp' => $telaLogo->img,
                    ];
                }

                $telasFormato[] = $telaData;
            }

            return $telasFormato;
        }

        if ($prenda->telas && count($prenda->telas) > 0) {
            foreach ($prenda->telas as $tela) {
                $telaData = [
                    'id' => $tela->id,
                    'nombre_tela' => isset($tela->tela) ? $tela->tela->nombre : 'SIN NOMBRE',
                    'color' => isset($tela->color) ? $tela->color->nombre : '',
                    'referencia' => $tela->referencia ?? '',
                    'descripcion' => $tela->descripcion ?? '',
                    'imagenes' => [],
                ];

                if ($prenda->telaFotos && count($prenda->telaFotos) > 0) {
                    foreach ($prenda->telaFotos as $foto) {
                        if ($foto->prenda_tela_cot_id == $tela->id) {
                            $ruta = $this->normalizarRutaStorage($foto->ruta_original);
                            $rutaWebp = $this->normalizarRutaStorage($foto->ruta_webp);
                            $telaData['imagenes'][] = [
                                'ruta' => $ruta,
                                'ruta_webp' => $rutaWebp,
                            ];
                        }
                    }
                }

                $telasFormato[] = $telaData;
            }
        }

        return $telasFormato;
    }

    private function procesarFotosPrenda($prenda): array
    {
        $fotosFormato = [];
        if ($prenda->fotos && count($prenda->fotos) > 0) {
            foreach ($prenda->fotos as $foto) {
                $fotosFormato[] = [
                    'ruta' => $this->normalizarRutaStorage($foto->ruta_original),
                    'ruta_webp' => $this->normalizarRutaStorage($foto->ruta_webp),
                ];
            }
        }
        return $fotosFormato;
    }

    private function procesarTallasYGeneros($prenda): array
    {
        $tallasDisponibles = [];
        $tallasConCantidades = [];
        $generosPresentes = [];

        if ($prenda->variantes && count($prenda->variantes) > 0) {
            foreach ($prenda->variantes as $variante) {
                if (!$variante->genero_id) {
                    continue;
                }
                $generoIdRaw = $variante->genero_id;
                if (is_string($generoIdRaw)) {
                    $decoded = json_decode($generoIdRaw, true);
                    $generosVariante = is_array($decoded) ? $decoded : [$generoIdRaw];
                } else {
                    $generosVariante = is_array($generoIdRaw) ? $generoIdRaw : [$generoIdRaw];
                }
                foreach ($generosVariante as $generoId) {
                    if (!in_array($generoId, $generosPresentes, true)) {
                        $generosPresentes[] = $generoId;
                    }
                }
            }
        }

        if ($prenda->tallas && count($prenda->tallas) > 0) {
            foreach ($prenda->tallas as $tallaCot) {
                $tallasDisponibles[] = $tallaCot->talla;
                $tallasConCantidades[] = [
                    'talla' => $tallaCot->talla,
                    'cantidad' => $tallaCot->cantidad,
                    'genero_id' => $tallaCot->genero_id,
                    'genero' => $tallaCot->genero ? $tallaCot->genero->nombre : 'DAMA',
                    'color' => $tallaCot->color ?? '',
                ];
            }
        }

        return [$tallasDisponibles, $tallasConCantidades, $generosPresentes];
    }

    private function completarTelasDesdeMultiplesSiAplica(array $telasFormato, $prenda): array
    {
        if (!empty($telasFormato) || !$prenda->variantes || count($prenda->variantes) === 0) {
            return $telasFormato;
        }

        $varTelas = $prenda->variantes[0];
        $telasMultiplesRaw = $varTelas->telas_multiples ?? null;
        if (!$telasMultiplesRaw) {
            return $telasFormato;
        }

        $telasMultiples = is_string($telasMultiplesRaw)
            ? json_decode($telasMultiplesRaw, true)
            : (is_array($telasMultiplesRaw) ? $telasMultiplesRaw : []);

        if (!is_array($telasMultiples)) {
            return $telasFormato;
        }

        foreach ($telasMultiples as $telaMulti) {
            $nombreTela = $telaMulti['tela'] ?? '';
            if (empty($nombreTela)) {
                continue;
            }
            $telasFormato[] = [
                'nombre_tela' => strtoupper((string) $nombreTela),
                'color' => strtoupper((string) ($telaMulti['color'] ?? '')),
                'referencia' => $telaMulti['referencia'] ?? '',
                'imagenes' => [],
                'origen' => 'telas_multiples',
            ];
        }

        return $telasFormato;
    }

    private function procesarVariantes($prenda): array
    {
        if (!$prenda->variantes || count($prenda->variantes) === 0) {
            return [];
        }

        $var = $prenda->variantes[0];
        $generoId = $var->genero_id ?? null;
        if (is_string($generoId)) {
            $generoIdDecodificado = json_decode($generoId, true);
            $generoId = is_array($generoIdDecodificado) ? $generoIdDecodificado : $generoId;
        }

        return [
            'tipo_prenda' => $var->tipo_prenda ?? '',
            'es_jean_pantalon' => (bool) ($var->es_jean_pantalon ?? false),
            'tipo_jean_pantalon' => $var->tipo_jean_pantalon ?? '',
            'aplica_manga' => (bool) ($var->aplica_manga ?? false),
            'tipo_manga' => $var->manga ? $var->manga->nombre : ($var->tipo_manga ?? 'No aplica'),
            'tipo_manga_id' => $var->tipo_manga_id,
            'obs_manga' => $var->obs_manga ?? '',
            'tiene_bolsillos' => (bool) ($var->tiene_bolsillos ?? false),
            'obs_bolsillos' => $var->obs_bolsillos ?? '',
            'aplica_broche' => (bool) ($var->aplica_broche ?? false),
            'tipo_broche' => $var->broche ? $var->broche->nombre : ($var->tipo_broche ?? 'No aplica'),
            'tipo_broche_id' => $var->tipo_broche_id,
            'obs_broche' => $var->obs_broche ?? '',
            'tiene_reflectivo' => (bool) ($var->tiene_reflectivo ?? false),
            'obs_reflectivo' => $var->obs_reflectivo ?? '',
            'genero_id' => $generoId,
            'genero' => $var->genero ? $var->genero->nombre : 'UNISEX',
        ];
    }

    private function procesarTecnicasLogo($prenda, array $procesosFormato): array
    {
        if (!$prenda->logoCotizacionesTecnicas || count($prenda->logoCotizacionesTecnicas) === 0) {
            return $procesosFormato;
        }

        foreach ($prenda->logoCotizacionesTecnicas as $logoTecnica) {
            $nombreTecnica = $logoTecnica->tipoLogo ? $logoTecnica->tipoLogo->nombre : 'Tecnica desconocida';
            $slugTecnica = strtolower(str_replace(' ', '-', (string) $nombreTecnica));

            $ubicacionesLogo = [];
            $ubicacionesRaw = $logoTecnica->ubicaciones ?? null;
            if ($ubicacionesRaw) {
                if (is_string($ubicacionesRaw)) {
                    $ubicacionesRaw = json_decode($ubicacionesRaw, true);
                }
                if (is_array($ubicacionesRaw)) {
                    foreach ($ubicacionesRaw as $ub) {
                        if (is_array($ub)) {
                            $ubicacionesLogo[] = [
                                'ubicacion' => $ub['ubicacion'] ?? '',
                                'descripcion' => $ub['descripcion'] ?? '',
                            ];
                        } elseif (is_string($ub)) {
                            $ubicacionesLogo[] = ['ubicacion' => $ub, 'descripcion' => ''];
                        }
                    }
                }
            }

            $fotosRelacion = $logoTecnica->fotos ?? null;
            if (!$fotosRelacion || ($fotosRelacion instanceof EloquentCollection && count($fotosRelacion) === 0)) {
                $fotosRelacion = LogoCotizacionTecnicaPrendaFoto::where('logo_cotizacion_tecnica_prenda_id', $logoTecnica->id)
                    ->orderBy('orden')
                    ->get();
            }

            $fotosLogoFormato = [];
            if ($fotosRelacion && count($fotosRelacion) > 0) {
                foreach ($fotosRelacion as $foto) {
                    $fotosLogoFormato[] = [
                        'ruta' => $this->normalizarRutaStorage($foto->ruta_original),
                        'ruta_webp' => $this->normalizarRutaStorage($foto->ruta_webp),
                        'ruta_miniatura' => $this->normalizarRutaStorage($foto->ruta_miniatura),
                        'orden' => $foto->orden ?? 0,
                    ];
                }
            }

            $variacionesPrenda = [];
            if ($logoTecnica->variaciones_prenda) {
                $variacionesRaw = is_string($logoTecnica->variaciones_prenda)
                    ? json_decode($logoTecnica->variaciones_prenda, true)
                    : $logoTecnica->variaciones_prenda;
                if (is_array($variacionesRaw)) {
                    $variacionesPrenda = $variacionesRaw;
                }
            }

            $tallasFormatoProceso = [];
            if ($logoTecnica->talla_cantidad) {
                $tallaCantidad = is_array($logoTecnica->talla_cantidad)
                    ? $logoTecnica->talla_cantidad
                    : json_decode($logoTecnica->talla_cantidad, true);
                if (is_array($tallaCantidad)) {
                    foreach ($tallaCantidad as $talla => $cantidad) {
                        $tallasFormatoProceso[$talla] = $cantidad;
                    }
                }
            }

            $procesosFormato[$slugTecnica] = [
                'tipo' => $nombreTecnica,
                'slug' => $slugTecnica,
                'ubicaciones' => $ubicacionesLogo,
                'imagenes' => $fotosLogoFormato,
                'observaciones' => $logoTecnica->observaciones ?? '',
                'variaciones_prenda' => $variacionesPrenda,
                'talla_cantidad' => $tallasFormatoProceso,
            ];
        }

        return $procesosFormato;
    }

    private function agregarReflectivoSiAplica($prenda, array $variantes, array $procesosFormato): array
    {
        if (empty($variantes['tiene_reflectivo']) || !$variantes['tiene_reflectivo']) {
            return $procesosFormato;
        }

        $procesosFormato['reflectivo'] = [
            'tipo' => 'Reflectivo',
            'slug' => 'reflectivo',
            'ubicaciones' => [[
                'ubicacion' => $variantes['obs_reflectivo'] ?? '',
                'descripcion' => '',
            ]],
            'imagenes' => [],
            'observaciones' => '',
            'variaciones_prenda' => [],
            'talla_cantidad' => [],
        ];

        return $procesosFormato;
    }

    private function normalizarRutaStorage(?string $ruta): ?string
    {
        if (!$ruta) {
            return $ruta;
        }
        if (!str_starts_with($ruta, '/')) {
            return '/storage/' . $ruta;
        }
        return $ruta;
    }
}
