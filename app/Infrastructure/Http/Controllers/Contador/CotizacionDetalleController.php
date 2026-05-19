<?php

namespace App\Infrastructure\Http\Controllers\Contador;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class CotizacionDetalleController extends Controller
{
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $allowedRoles = ['contador', 'admin', 'aprobador_cotizaciones', 'visualizador_cotizaciones_logo', 'asesor'];
            $hasAccess = false;
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                return response()->json(['error' => 'No tienes permiso para acceder a esta cotización'], 403);
            }

            $cotizacionModelo = Cotizacion::with([
                'tipoCotizacion',
                'cliente',
                'asesor',
                'prendas' => function ($query) {
                    $query->with([
                        'fotos',
                        'telas',
                        'telas.color',
                        'telas.tela',
                        'telaFotos',
                        'tallas',
                        'detalle',
                        'variantes' => function ($q) {
                            $q->with(['manga', 'broche']);
                        },
                    ]);
                },
                'logoCotizacion' => function ($query) {
                    $query->with(['fotos', 'tecnicasPrendas' => function ($q) {
                        $q->with(['prenda', 'tipoLogo', 'fotos']);
                    }]);
                },
            ])->findOrFail($id);

            if ($user->hasRole('asesor') && (int) $cotizacionModelo->asesor_id !== (int) $user->id) {
                return response()->json(['error' => 'No tienes permiso para acceder a esta cotización'], 403);
            }

            $logoObservacionesPorPrenda = $this->obtenerLogoObservacionesPorPrenda((int) $cotizacionModelo->id);

            $datos = [
                'cotizacion' => [
                    'id' => $cotizacionModelo->id,
                    'numero_cotizacion' => $cotizacionModelo->numero_cotizacion,
                    'asesora_nombre' => $cotizacionModelo->asesor ? $cotizacionModelo->asesor->name : 'N/A',
                    'empresa' => $cotizacionModelo->empresa_solicitante ?? 'N/A',
                    'nombre_cliente' => $cotizacionModelo->cliente ? $cotizacionModelo->cliente->nombre : 'N/A',
                    'created_at' => $cotizacionModelo->created_at,
                    'estado' => $cotizacionModelo->estado,
                    'tipo_venta' => $cotizacionModelo->tipo_venta ?? 'N/A',
                    'tipo_cotizacion_id' => $cotizacionModelo->tipo_cotizacion_id ?? null,
                    'tipo_codigo' => ($cotizacionModelo->tipoCotizacion?->codigo) ?? ($cotizacionModelo->obtenerTipoCotizacion() ?? null),
                    'especificaciones' => $this->parseEspecificaciones($cotizacionModelo->especificaciones),
                    'iva' => $cotizacionModelo->iva ?? 0,
                    'cliente_nit' => $cotizacionModelo->cliente_nit ?? null,
                    'cliente_direccion' => $cotizacionModelo->cliente_direccion ?? null,
                    'cliente_telefono' => $cotizacionModelo->cliente_telefono ?? null,
                ],
                'logo_observaciones_prenda' => $logoObservacionesPorPrenda,
                'prendas_cotizaciones' => $cotizacionModelo->prendas->map(function ($prenda, $index) use ($logoObservacionesPorPrenda) {
                    $descripcionFormateada = method_exists($prenda, 'generarDescripcionDetallada')
                        ? $prenda->generarDescripcionDetallada($index + 1)
                        : (string) ($prenda->descripcion ?? '');

                    return [
                        'id' => $prenda->id,
                        'nombre_prenda' => $prenda->nombre_producto ?? 'Prenda sin nombre',
                        'cantidad' => $prenda->cantidad ?? 0,
                        'prenda_bodega' => (bool) ($prenda->prenda_bodega ?? false),
                        'detalle' => $prenda->detalle ? [
                            'disponibilidad' => $prenda->detalle->disponibilidad,
                            'ultima_venta' => $prenda->detalle->ultima_venta,
                        ] : null,
                        'logo_observacion' => $logoObservacionesPorPrenda[$prenda->id] ?? null,
                        'descripcion' => $prenda->descripcion ?? null,
                        'descripcion_formateada' => $descripcionFormateada,
                        'detalles_proceso' => $prenda->descripcion ?? null,
                        'fotos' => $prenda->fotos ? $prenda->fotos
                            ->filter(function ($foto) {
                                $ruta = $foto->ruta_webp ?? $foto->ruta_original ?? '';
                                return !str_contains(strtolower($ruta), 'logo');
                            })
                            ->map(function ($foto) {
                                return $foto->url;
                            })
                            ->values()
                            ->toArray() : [],
                        'telas' => $prenda->telas ? $prenda->telas->map(function ($tela) {
                            return [
                                'id' => $tela->id,
                                'color' => $tela->color ?? null,
                                'nombre_tela' => $tela->tela->nombre ?? null,
                                'referencia' => $tela->tela->referencia ?? null,
                                'url_imagen' => $tela->url_imagen ?? '',
                            ];
                        })->toArray() : [],
                        'tela_fotos' => $prenda->telaFotos ? $prenda->telaFotos->map(function ($foto) {
                            return $foto->url;
                        })->toArray() : [],
                        'tallas' => $prenda->tallas ? $prenda->tallas->map(function ($talla) {
                            return [
                                'id' => $talla->id,
                                'talla' => $talla->talla,
                                'color' => $talla->color,
                                'genero_id' => $talla->genero_id,
                                'cantidad' => $talla->cantidad,
                            ];
                        })->toArray() : [],
                        'texto_personalizado_tallas' => $prenda->texto_personalizado_tallas ?? null,
                        'variantes' => $prenda->variantes ? $prenda->variantes->map(function ($variante) {
                            $nombreTipoManga = null;
                            if ($variante->tipo_manga_id && $variante->relationLoaded('manga') && $variante->manga) {
                                $nombreTipoManga = $variante->manga->nombre;
                            }
                            $nombreTipoBroche = null;
                            if ($variante->tipo_broche_id && $variante->relationLoaded('broche') && $variante->broche) {
                                $nombreTipoBroche = $variante->broche->nombre;
                            }
                            return [
                                'id' => $variante->id,
                                'tipo_prenda' => $variante->tipo_prenda ?? null,
                                'es_jean_pantalon' => $variante->es_jean_pantalon ?? null,
                                'tipo_jean_pantalon' => $variante->tipo_jean_pantalon ?? null,
                                'genero_id' => $variante->genero_id ?? null,
                                'color' => $variante->color ?? null,
                                'tiene_bolsillos' => $variante->tiene_bolsillos ?? null,
                                'obs_bolsillos' => $variante->obs_bolsillos ?? null,
                                'aplica_manga' => $variante->aplica_manga ?? null,
                                'tipo_manga_id' => $variante->tipo_manga_id ?? null,
                                'tipo_manga_nombre' => $nombreTipoManga,
                                'tipo_manga' => $variante->tipo_manga ?? null,
                                'obs_manga' => $variante->obs_manga ?? null,
                                'aplica_broche' => $variante->aplica_broche ?? null,
                                'tipo_broche_id' => $variante->tipo_broche_id ?? null,
                                'tipo_broche_nombre' => $nombreTipoBroche,
                                'obs_broche' => $variante->obs_broche ?? null,
                                'tiene_reflectivo' => $variante->tiene_reflectivo ?? null,
                                'obs_reflectivo' => $variante->obs_reflectivo ?? null,
                                'descripcion_adicional' => $variante->descripcion_adicional ?? null,
                            ];
                        })->toArray() : [],
                    ];
                })->toArray(),
            ];

            $logoCotizacion = null;
            if ($cotizacionModelo->logoCotizacion) {
                $logoFotos = $cotizacionModelo->logoCotizacion->fotos ? $cotizacionModelo->logoCotizacion->fotos->map(function ($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url,
                        'orden' => $foto->orden,
                    ];
                })->toArray() : [];

                if (empty($logoFotos) && !empty($cotizacionModelo->logoCotizacion->imagenes)) {
                    $logoFotos = collect($cotizacionModelo->logoCotizacion->imagenes)
                        ->filter()
                        ->values()
                        ->map(function ($ruta, $idx) {
                            $url = $ruta;
                            if (is_string($ruta) && !str_starts_with($ruta, 'http')) {
                                $url = str_starts_with($ruta, '/storage/') ? $ruta : '/storage/' . ltrim($ruta, '/');
                            }
                            return [
                                'id' => null,
                                'url' => $url,
                                'orden' => $idx + 1,
                            ];
                        })
                        ->toArray();
                }

                $telasPrendas = $cotizacionModelo->logoCotizacion->telasPrendas ? $cotizacionModelo->logoCotizacion->telasPrendas->map(function ($telaPrenda) {
                    return [
                        'id' => $telaPrenda->id,
                        'prenda_cot_id' => $telaPrenda->prenda_cot_id,
                        'tela' => $telaPrenda->tela,
                        'color' => $telaPrenda->color,
                        'ref' => $telaPrenda->ref,
                        'img' => $telaPrenda->img,
                    ];
                })->toArray() : [];

                $logoCotizacion = [
                    'id' => $cotizacionModelo->logoCotizacion->id,
                    'descripcion' => $cotizacionModelo->logoCotizacion->descripcion ?? null,
                    'tipo_venta' => $cotizacionModelo->logoCotizacion->tipo_venta ?? null,
                    'tecnicas' => $cotizacionModelo->logoCotizacion->tecnicas ?? [],
                    'secciones' => $cotizacionModelo->logoCotizacion->secciones ?? [],
                    'observaciones_tecnicas' => $cotizacionModelo->logoCotizacion->observaciones_tecnicas ?? null,
                    'observaciones_generales' => $cotizacionModelo->logoCotizacion->observaciones_generales ?? [],
                    'fotos' => $logoFotos,
                    'telas_prendas' => $telasPrendas,
                    'tecnicas_prendas' => $cotizacionModelo->logoCotizacion->tecnicasPrendas ? $cotizacionModelo->logoCotizacion->tecnicasPrendas->map(function ($tecnicaPrenda) {
                        return [
                            'id' => $tecnicaPrenda->id,
                            'prenda_id' => $tecnicaPrenda->prenda_cot_id,
                            'prenda_nombre' => $tecnicaPrenda->prenda ? $tecnicaPrenda->prenda->nombre_producto : 'Sin nombre',
                            'tipo_logo_nombre' => $tecnicaPrenda->tipoLogo ? $tecnicaPrenda->tipoLogo->nombre : 'Logo',
                            'variaciones_prenda' => $tecnicaPrenda->variaciones_prenda ?? null,
                            'ubicaciones' => $tecnicaPrenda->ubicaciones ?? null,
                            'talla_cantidad' => $tecnicaPrenda->talla_cantidad ?? null,
                            'observaciones' => $tecnicaPrenda->observaciones ?? null,
                            'grupo_combinado' => $tecnicaPrenda->grupo_combinado ?? null,
                            'fotos' => $tecnicaPrenda->fotos ? $tecnicaPrenda->fotos->map(function ($foto) {
                                return [
                                    'id' => $foto->id,
                                    'url' => $foto->url ?? null,
                                    'orden' => $foto->orden ?? 1,
                                ];
                            })->toArray() : [],
                        ];
                    })->toArray() : [],
                ];
            }

            $datos['logo_cotizacion'] = $logoCotizacion;
            $datos['tiene_logo'] = !is_null($logoCotizacion);
            $datos['tiene_prendas'] = count($datos['prendas_cotizaciones']) > 0;

            $tipoCodigo = ($datos['cotizacion']['tipo_codigo'] ?? null);
            $tipoCodigoUpper = is_string($tipoCodigo) ? strtoupper(trim($tipoCodigo)) : null;
            if ($tipoCodigoUpper === 'EPP') {
                $eppCot = DB::table('epp_cotizacion')->where('cotizacion_id', $cotizacionModelo->id)->first();
                $eppItems = DB::table('epp_items_cot')->where('cotizacion_id', $cotizacionModelo->id)->orderBy('id')->get();

                $eppValoresUnitarios = DB::table('epp_valor_unitario')
                    ->whereIn('epp_item_id', $eppItems->pluck('id')->all())
                    ->get()
                    ->keyBy('epp_item_id');

                $imagenes = DB::table('epp_img_cot')
                    ->whereIn('epp_item_id', $eppItems->pluck('id')->all())
                    ->orderBy('id')
                    ->get()
                    ->groupBy('epp_item_id');

                $datos['epp_cotizacion'] = $eppCot ? [
                    'tipo_venta' => $eppCot->tipo_venta ?? null,
                    'observaciones_generales' => $eppCot->observaciones_generales ?? null,
                ] : null;

                $datos['epp_items'] = $eppItems->map(function ($it) use ($imagenes) {
                    $imgs = $imagenes->get($it->id, collect());
                    $urls = $imgs->map(function ($imgRow) {
                        $ruta = $imgRow->ruta ?? null;
                        if (!$ruta) {
                            return null;
                        }
                        try {
                            return Storage::disk('public')->url($ruta);
                        } catch (\Exception $e) {
                            return str_starts_with($ruta, '/') ? $ruta : ('/storage/' . ltrim($ruta, '/'));
                        }
                    })->filter()->values()->all();

                    return [
                        'id' => $it->id,
                        'nombre' => $it->nombre ?? 'Sin nombre',
                        'cantidad' => (int) ($it->cantidad ?? 1),
                        'observaciones' => $it->observaciones ?? null,
                        'imagenes' => $urls,
                    ];
                })->map(function ($it) use ($eppValoresUnitarios) {
                    $vu = $eppValoresUnitarios[$it['id']] ?? null;
                    $it['valor_unitario'] = $vu ? $vu->valor_unitario : null;
                    return $it;
                })->values()->all();
            }

            return response()->json($datos);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Cotización no encontrada'], 404);
        } catch (\Throwable $e) {
            \Log::error('Error en CotizacionDetalleController@show', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cotización: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function parseEspecificaciones($especificaciones): array
    {
        if (!$especificaciones) {
            return [];
        }

        if (is_string($especificaciones)) {
            try {
                $parsed = json_decode($especificaciones, true);
                if (is_string($parsed)) {
                    $parsed = json_decode($parsed, true);
                }
                if (is_array($parsed)) {
                    return $parsed;
                }

                $unescaped = stripslashes($especificaciones);
                $parsed = json_decode($unescaped, true);
                if (is_string($parsed)) {
                    $parsed = json_decode($parsed, true);
                }
                if (is_array($parsed)) {
                    return $parsed;
                }
            } catch (\Exception $e) {
                return [];
            }
        }

        if (is_array($especificaciones)) {
            return $especificaciones;
        }

        return [];
    }

    private function obtenerLogoObservacionesPorPrenda(int $cotizacionId): array
    {
        if (!Schema::hasTable('logo_observacion_prenda_cot')) {
            return [];
        }

        return DB::table('logo_observacion_prenda_cot')
            ->where('cotizacion_id', $cotizacionId)
            ->get(['prenda_cot_id', 'observacion'])
            ->keyBy('prenda_cot_id')
            ->map(fn ($row) => $row ? $row->observacion : null)
            ->toArray();
    }
}
