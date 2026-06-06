<?php

namespace App\Application\Services\Asesores;

use Illuminate\Support\Facades\DB;

class ObtenerDatosPrendaPedidoService
{
    public function __construct(
        private readonly PrendaEdicionBloqueoService $prendaEdicionBloqueoService,
    ) {
    }

    public function obtenerParaEdicion(int $pedidoId, int $prendaId): ?array
    {
        \Log::info('[PRENDA-DATOS] Cargando datos de prenda para edición', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
        ]);

        $prenda = DB::table('prendas_pedido')
            ->where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->where('deleted_at', null)
            ->first();

        if (!$prenda) {
            \Log::warning('[PRENDA-DATOS] Prenda no encontrada', [
                'prenda_id' => $prendaId,
                'pedido_id' => $pedidoId,
            ]);

            return null;
        }

        $imagenesPrenda = $this->obtenerImagenesPrenda($prendaId);
        $telasAgregadas = $this->obtenerTelasAgregadas($prendaId);
        $variantesFormateadas = $this->obtenerVariantes($prendaId);
        $procesos = $this->obtenerProcesos($prendaId);
        $tallas = $this->obtenerTallas($prendaId);
        $generos = array_keys($tallas);
        $bloqueoEdicion = $this->prendaEdicionBloqueoService->evaluar($pedidoId, $prendaId);

        $datos = [
            'id' => $prenda->id,
            'prenda_pedido_id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'nombre' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion ?? '',
            'origen' => $prenda->de_bodega ? 'bodega' : 'cliente',
            'de_bodega' => (bool) $prenda->de_bodega,
            'imagenes' => $imagenesPrenda,
            'telasAgregadas' => $telasAgregadas,
            'tallas' => $tallas,
            'generos' => $generos,
            'variantes' => $variantesFormateadas,
            'procesos' => $procesos,
            'puede_editar' => $bloqueoEdicion['puede_editar'],
            'bloqueo_edicion' => $bloqueoEdicion,
        ];

        \Log::info('[PRENDA-DATOS] Datos compilados exitosamente', [
            'prenda_id' => $prendaId,
            'imagenes_count' => count($imagenesPrenda),
            'telas_count' => count($telasAgregadas),
            'procesos_count' => count($procesos),
            'variantes_count' => count($variantesFormateadas),
        ]);

        return $datos;
    }

    private function obtenerImagenesPrenda(int $prendaId): array
    {
        try {
            $fotosGuardadas = DB::table('prenda_fotos_pedido')
                ->where('prenda_pedido_id', $prendaId)
                ->where('deleted_at', null)
                ->orderBy('orden')
                ->select('ruta_webp')
                ->get();

            $imagenes = $fotosGuardadas->map(function ($foto) {
                $ruta = str_replace('\\', '/', $foto->ruta_webp);
                if (strpos($ruta, '/storage/') === 0) {
                    return $ruta;
                }
                if (strpos($ruta, 'storage/') === 0) {
                    return '/' . $ruta;
                }
                if (strpos($ruta, '/') !== 0) {
                    return '/storage/' . $ruta;
                }
                return $ruta;
            })->toArray();

            \Log::info('[PRENDA-DATOS] Imágenes de prenda encontradas', [
                'prenda_id' => $prendaId,
                'cantidad' => count($imagenes),
            ]);

            return $imagenes;
        } catch (\Exception $e) {
            \Log::debug('[PRENDA-DATOS] Error en prenda_fotos_pedido: ' . $e->getMessage());
            return [];
        }
    }

    private function obtenerTelasAgregadas(int $prendaId): array
    {
        try {
            $colorTelaRecords = DB::table('prenda_pedido_colores_telas')
                ->where('prenda_pedido_id', $prendaId)
                ->join('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                ->join('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                ->select(
                    'prenda_pedido_colores_telas.id as color_tela_id',
                    'colores_prenda.nombre as color_nombre',
                    'telas_prenda.nombre as tela_nombre',
                    'prenda_pedido_colores_telas.referencia'
                )
                ->get();

            $telasAgregadas = [];
            foreach ($colorTelaRecords as $colorTela) {
                $fotosTelaDB = DB::table('prenda_fotos_tela_pedido')
                    ->where('prenda_pedido_colores_telas_id', $colorTela->color_tela_id)
                    ->where('deleted_at', null)
                    ->orderBy('orden')
                    ->select('ruta_webp', 'ruta_original')
                    ->get();

                $imagenesTelaFormato = $fotosTelaDB->map(function ($foto) {
                    $ruta = str_replace('\\', '/', $foto->ruta_webp ?? $foto->ruta_original);
                    if (strpos($ruta, '/storage/') === 0) {
                        return $ruta;
                    }
                    if (strpos($ruta, 'storage/') === 0) {
                        return '/' . $ruta;
                    }
                    if (strpos($ruta, '/') !== 0) {
                        return '/storage/' . $ruta;
                    }
                    return $ruta;
                })->toArray();

                $telasAgregadas[] = [
                    'tela' => $colorTela->tela_nombre,
                    'color' => $colorTela->color_nombre,
                    'referencia' => $colorTela->referencia ?? '',
                    'imagenes' => $imagenesTelaFormato,
                ];
            }

            \Log::info('[PRENDA-DATOS] Telas encontradas', [
                'prenda_id' => $prendaId,
                'cantidad' => count($telasAgregadas),
            ]);

            return $telasAgregadas;
        } catch (\Exception $e) {
            \Log::debug('[PRENDA-DATOS] Error en prenda_pedido_colores_telas: ' . $e->getMessage());
            return [];
        }
    }

    private function obtenerVariantes(int $prendaId): array
    {
        try {
            $variantes = DB::table('prenda_pedido_variantes')
                ->where('prenda_pedido_id', $prendaId)
                ->leftJoin('tipos_manga', 'prenda_pedido_variantes.tipo_manga_id', '=', 'tipos_manga.id')
                ->leftJoin('tipos_broche_boton', 'prenda_pedido_variantes.tipo_broche_boton_id', '=', 'tipos_broche_boton.id')
                ->select(
                    'tipos_manga.nombre as manga_nombre',
                    'tipos_broche_boton.nombre as broche_nombre',
                    'prenda_pedido_variantes.manga_obs',
                    'prenda_pedido_variantes.bolsillos_obs',
                    'prenda_pedido_variantes.broche_boton_obs',
                    'prenda_pedido_variantes.tiene_bolsillos'
                )
                ->get();

            $variantesFormateadas = [];
            foreach ($variantes as $variante) {
                $variantesFormateadas[] = [
                    'manga' => $variante->manga_nombre ?? '',
                    'obs_manga' => $variante->manga_obs ?? '',
                    'tiene_bolsillos' => (bool) $variante->tiene_bolsillos,
                    'obs_bolsillos' => $variante->bolsillos_obs ?? '',
                    'broche' => $variante->broche_nombre ?? '',
                    'obs_broche' => $variante->broche_boton_obs ?? '',
                ];
            }

            \Log::info('[PRENDA-DATOS] Variantes encontradas', [
                'prenda_id' => $prendaId,
                'cantidad' => count($variantesFormateadas),
            ]);

            return $variantesFormateadas;
        } catch (\Exception $e) {
            \Log::debug('[PRENDA-DATOS] Error en prenda_pedido_variantes: ' . $e->getMessage());
            return [];
        }
    }

    private function obtenerProcesos(int $prendaId): array
    {
        try {
            $procesosDB = DB::table('pedidos_procesos_prenda_detalles')
                ->where('prenda_pedido_id', $prendaId)
                ->where('deleted_at', null)
                ->join('tipos_procesos', 'pedidos_procesos_prenda_detalles.tipo_proceso_id', '=', 'tipos_procesos.id')
                ->select(
                    'pedidos_procesos_prenda_detalles.id as proceso_id',
                    'tipos_procesos.id as tipo_id',
                    'tipos_procesos.nombre as tipo_nombre',
                    'pedidos_procesos_prenda_detalles.ubicaciones',
                    'pedidos_procesos_prenda_detalles.observaciones',
                    'pedidos_procesos_prenda_detalles.tallas_dama',
                    'pedidos_procesos_prenda_detalles.tallas_caballero',
                    'pedidos_procesos_prenda_detalles.estado'
                )
                ->get();

            $procesos = [];
            foreach ($procesosDB as $procesoRow) {
                $imagenesProc = DB::table('pedidos_procesos_imagenes')
                    ->where('proceso_prenda_detalle_id', $procesoRow->proceso_id)
                    ->where('deleted_at', null)
                    ->orderBy('orden')
                    ->select('id', 'ruta_webp', 'ruta_original', 'es_principal')
                    ->get();

                $imagenesFormato = $imagenesProc->map(function ($img) {
                    $rutaWebp = str_replace('\\', '/', $img->ruta_webp ?? '');
                    $rutaOriginal = str_replace('\\', '/', $img->ruta_original ?? '');

                    if ($rutaWebp && strpos($rutaWebp, '/storage/') !== 0) {
                        if (strpos($rutaWebp, 'storage/') === 0) {
                            $rutaWebp = '/' . $rutaWebp;
                        } elseif (strpos($rutaWebp, '/') !== 0) {
                            $rutaWebp = '/storage/' . $rutaWebp;
                        }
                    }

                    if ($rutaOriginal && strpos($rutaOriginal, '/storage/') !== 0) {
                        if (strpos($rutaOriginal, 'storage/') === 0) {
                            $rutaOriginal = '/' . $rutaOriginal;
                        } elseif (strpos($rutaOriginal, '/') !== 0) {
                            $rutaOriginal = '/storage/' . $rutaOriginal;
                        }
                    }

                    return [
                        'id' => $img->id,
                        'ruta_webp' => $rutaWebp,
                        'ruta_original' => $rutaOriginal,
                        'url' => $rutaWebp ?: $rutaOriginal,
                        'es_principal' => $img->es_principal ?? false,
                    ];
                })->toArray();

                $ubicaciones = [];
                if ($procesoRow->ubicaciones) {
                    $ubicaciones = is_array($procesoRow->ubicaciones)
                        ? $procesoRow->ubicaciones
                        : json_decode($procesoRow->ubicaciones, true) ?? [];
                }

                $tallasRelacionales = \App\Models\PedidosProcesosPrendaTalla::where(
                    'proceso_prenda_detalle_id',
                    $procesoRow->proceso_id
                )->get();

                $tallasDama = [];
                $tallasCaballero = [];
                $tallasUnisex = [];
                $tallasSobremedida = [];
                foreach ($tallasRelacionales as $tallaRec) {
                    if ((int) $tallaRec->cantidad <= 0) {
                        continue;
                    }

                    $genero = strtoupper(trim((string) ($tallaRec->genero ?? '')));
                    $esSobremedida = !empty($tallaRec->es_sobremedida);

                    if ($esSobremedida) {
                        if (in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                            $tallasSobremedida[$genero] = (int) $tallaRec->cantidad;
                        }
                        continue;
                    }

                    $talla = trim((string) ($tallaRec->talla ?? ''));
                    if ($talla === '') {
                        continue;
                    }

                    if ($genero === 'DAMA') {
                        $tallasDama[$talla] = (int) $tallaRec->cantidad;
                    } elseif ($genero === 'CABALLERO') {
                        $tallasCaballero[$talla] = (int) $tallaRec->cantidad;
                    } elseif ($genero === 'UNISEX') {
                        $tallasUnisex[$talla] = (int) $tallaRec->cantidad;
                    }
                }

                $procesos[] = [
                    'id' => $procesoRow->proceso_id,
                    'tipo_id' => $procesoRow->tipo_id,
                    'tipo_nombre' => $procesoRow->tipo_nombre,
                    'ubicaciones' => $ubicaciones,
                    'observaciones' => $procesoRow->observaciones ?? '',
                    'tallas_dama' => $tallasDama,
                    'tallas_caballero' => $tallasCaballero,
                    'tallas_unisex' => $tallasUnisex,
                    'tallas_sobremedida' => $tallasSobremedida,
                    'estado' => $procesoRow->estado ?? 'PENDIENTE',
                    'imagenes' => $imagenesFormato,
                ];
            }

            \Log::info('[PRENDA-DATOS] Procesos encontrados', [
                'prenda_id' => $prendaId,
                'cantidad' => count($procesos),
            ]);

            return $procesos;
        } catch (\Exception $e) {
            \Log::debug('[PRENDA-DATOS] Error en pedidos_procesos_prenda_detalles: ' . $e->getMessage());
            return [];
        }
    }

    private function obtenerTallas(int $prendaId): array
    {
        try {
            $tallasDB = DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prendaId)
                ->select('genero', 'talla', 'cantidad')
                ->get();

            $tallas = [];
            foreach ($tallasDB as $tallaRow) {
                $genero = $tallaRow->genero;
                if (!isset($tallas[$genero])) {
                    $tallas[$genero] = [];
                }
                $tallas[$genero][$tallaRow->talla] = $tallaRow->cantidad;
            }

            \Log::info('[PRENDA-DATOS] Tallas encontradas', [
                'prenda_id' => $prendaId,
                'cantidad' => $tallasDB->count(),
            ]);

            return $tallas;
        } catch (\Exception $e) {
            \Log::debug('[PRENDA-DATOS] Error en prenda_pedido_tallas: ' . $e->getMessage());
            return [];
        }
    }
}
