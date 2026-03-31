<?php

namespace App\Application\Services\Asesores;

use Illuminate\Support\Facades\DB;

class ObtenerPedidoDetalleTransformadorService
{
    private const STORAGE_PREFIX = 'storage/';
    private const STORAGE_WEB_PREFIX = '/storage/';

    public function transformarPrendaParaEdicion($prenda): array
    {
        $prendaArray = $prenda->toArray();
        $prendaArray['origen'] = $prendaArray['de_bodega'] == 1 ? 'bodega' : 'confeccion';

        $prendaArray['procesos'] = $this->transformarProcesosDePrenda($prenda);
        [$prendaArray['imagenes'], $prendaArray['imagenes_tela']] = $this->transformarImagenesDePrenda($prenda);
        $this->agregarTallasDePrenda($prendaArray, (int) $prenda->id);
        $this->agregarColorTallaYAsignaciones($prendaArray, (int) $prenda->id);
        $prendaArray['variantes'] = $this->transformarVariantesDePrenda($prenda);
        $prendaArray['colores_telas'] = $this->transformarColoresTelasDePrenda((int) $prenda->id);

        return $prendaArray;
    }

    private function transformarProcesosDePrenda($prenda): array
    {
        if (!$prenda->procesos) {
            return [];
        }

        $procesos = [];
        foreach ($prenda->procesos as $proceso) {
            $procesos[] = $this->construirProcesoParaEdicion($proceso);
        }

        return $procesos;
    }

    private function transformarImagenesDePrenda($prenda): array
    {
        $imagenes = ($prenda->fotos && $prenda->fotos->count() > 0)
            ? $prenda->fotos->map(function ($foto) {
                $rutaWebp = $this->normalizarRutaStorage($foto->ruta_webp ?? '');
                $rutaOriginal = $this->normalizarRutaStorage($foto->ruta_original ?? '');
                return [
                    'id' => $foto->id,
                    'url' => $rutaWebp ?: $rutaOriginal,
                    'ruta_webp' => $rutaWebp,
                    'ruta_original' => $rutaOriginal,
                ];
            })->filter()->toArray()
            : [];

        $imagenesTela = ($prenda->fotosTelas && $prenda->fotosTelas->count() > 0)
            ? $prenda->fotosTelas->map(fn($foto) => $foto->ruta_webp ?? $foto->ruta_original ?? '')->filter()->toArray()
            : [];

        return [$imagenes, $imagenesTela];
    }

    private function agregarTallasDePrenda(array &$prendaArray, int $prendaId): void
    {
        $prendaArray['tallas_dama'] = $this->obtenerTallasPorGenero($prendaId, 'DAMA');
        $prendaArray['tallas_caballero'] = $this->obtenerTallasPorGenero($prendaId, 'CABALLERO');
        $prendaArray['tallas_unisex'] = $this->obtenerTallasPorGenero($prendaId, 'UNISEX');
        $prendaArray['tallas_sobremedida'] = $this->obtenerTallasPorGenero($prendaId, 'SOBREMEDIDA');
        $prendaArray['tallas_generico'] = $this->obtenerTallasPorGenero($prendaId, 'GENERICO');
    }

    private function obtenerTallasPorGenero(int $prendaId, string $genero): array
    {
        return DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prendaId)
            ->where('genero', $genero)
            ->get(['talla', 'cantidad'])
            ->toArray();
    }

    private function agregarColorTallaYAsignaciones(array &$prendaArray, int $prendaId): void
    {
        $tallaColores = DB::table('prenda_pedido_talla_colores as ptc')
            ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
            ->where('pt.prenda_pedido_id', $prendaId)
            ->select([
                'ptc.id',
                'ptc.prenda_pedido_talla_id',
                'pt.genero',
                'pt.talla',
                'ptc.tela_id',
                'ptc.tela_nombre',
                'ptc.color_id',
                'ptc.color_nombre',
                'ptc.cantidad',
                'ptc.referencia',
                'ptc.observaciones',
                'ptc.imagen_ruta'
            ])
            ->get()
            ->toArray();

        $prendaArray['talla_colores'] = array_map(function ($color) {
            if ($color->imagen_ruta) {
                $ruta = str_replace('\\', '/', $color->imagen_ruta);
                if (!str_starts_with($ruta, self::STORAGE_WEB_PREFIX)) {
                    if (str_starts_with($ruta, self::STORAGE_PREFIX)) {
                        $ruta = '/' . $ruta;
                    } elseif (!str_starts_with($ruta, '/')) {
                        $ruta = self::STORAGE_WEB_PREFIX . $ruta;
                    }
                }
                $color->imagen_ruta = $ruta;
            }
            return $color;
        }, $tallaColores);

        $mapaColorTelaId = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prendaId)
            ->get(['id', 'color_id', 'tela_id'])
            ->reduce(function (array $carry, $rel) {
                $clave = ($rel->color_id ?? '') . ':' . ($rel->tela_id ?? '');
                if ($clave !== ':') {
                    $carry[$clave] = (int) $rel->id;
                }
                return $carry;
            }, []);

        $prendaArray['asignacionesColoresPorTalla'] = $this->construirAsignacionesColores($prendaArray['talla_colores'], $mapaColorTelaId);
    }

    private function construirAsignacionesColores(array $tallaColores, array $mapaColorTelaId): array
    {
        $asignaciones = [];
        foreach ($tallaColores as $color) {
            $tipoTalla = preg_match('/^\d+$/', $color->talla) ? 'Número' : 'Letra';
            $generoLower = strtolower($color->genero);
            $clave = $generoLower . '-' . $tipoTalla . '-' . $color->talla;
            $claveRelacion = ($color->color_id ?? '') . ':' . ($color->tela_id ?? '');
            $colorTelaId = $mapaColorTelaId[$claveRelacion] ?? null;

            if (!isset($asignaciones[$clave])) {
                $asignaciones[$clave] = [
                    'genero' => $generoLower,
                    'tela' => $color->tela_nombre ?? 'SIN_TELA',
                    'tela_id' => $color->tela_id ? (int) $color->tela_id : null,
                    'tipo' => $tipoTalla,
                    'talla' => $color->talla,
                    'prenda_pedido_colores_telas_id' => $colorTelaId,
                    'colores' => []
                ];
            }

            $asignaciones[$clave]['colores'][] = [
                'nombre' => $color->color_nombre,
                'cantidad' => $color->cantidad,
                'referencia' => $color->referencia,
                'observaciones' => $color->observaciones,
                'imagen_ruta' => $color->imagen_ruta,
                'prenda_pedido_colores_telas_id' => $colorTelaId,
                'color_id' => $color->color_id ? (int) $color->color_id : null,
                'tela_id' => $color->tela_id ? (int) $color->tela_id : null,
            ];
        }

        return $asignaciones;
    }

    private function transformarVariantesDePrenda($prenda): array
    {
        if (!$prenda->variantes) {
            return [];
        }

        $variantes = [];
        foreach ($prenda->variantes as $var) {
            $variantes[] = [
                'id' => $var->id,
                'tipo_manga' => $var->tipoManga?->nombre ?? 'Sin especificar',
                'tipo_manga_id' => $var->tipo_manga_id,
                'tipo_broche_boton' => $var->tipoBrocheBoton?->nombre ?? 'Sin especificar',
                'tipo_broche_boton_id' => $var->tipo_broche_boton_id,
                'tiene_bolsillos' => (bool) $var->tiene_bolsillos,
                'manga_obs' => $var->manga_obs,
                'broche_boton_obs' => $var->broche_boton_obs,
                'bolsillos_obs' => $var->bolsillos_obs
            ];
        }

        return $variantes;
    }

    private function transformarColoresTelasDePrenda(int $prendaId): array
    {
        $coloresTelas = [];
        $relaciones = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prendaId)
            ->get(['id', 'color_id', 'tela_id', 'referencia'])
            ->toArray();

        foreach ($relaciones as $rel) {
            $color = DB::table('colores_prenda')->find($rel->color_id);
            $tela = DB::table('telas_prenda')->where('id', $rel->tela_id)->first(['id', 'nombre', 'referencia']);

            $telaNombre = $tela->nombre ?? null;
            $telaReferencia = $tela->referencia ?? null;
            if (!$telaNombre) {
                $telaNombre = DB::table('prenda_pedido_talla_colores as ptc')
                    ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                    ->where('pt.prenda_pedido_id', $prendaId)
                    ->where('ptc.tela_id', $rel->tela_id)
                    ->value('ptc.tela_nombre');
            }
            if (!$telaNombre && $rel->tela_id) {
                $telaNombre = 'Tela #' . $rel->tela_id;
            }

            $fotos = DB::table('prenda_fotos_tela_pedido')
                ->where('prenda_pedido_colores_telas_id', $rel->id)
                ->get(['ruta_original', 'ruta_webp', 'orden'])
                ->toArray();

            $coloresTelas[] = [
                'id' => $rel->id,
                'color_id' => $rel->color_id,
                'color_nombre' => $color->nombre ?? 'Sin color',
                'color_codigo' => $color->codigo ?? '',
                'tela_id' => $rel->tela_id,
                'tela_nombre' => $telaNombre ?? 'Tela desconocida',
                'tela_referencia' => $rel->referencia ?? $telaReferencia ?? '',
                'fotos_tela' => array_map(function ($f) {
                    return [
                        'ruta_original' => $f->ruta_original,
                        'ruta_webp' => $f->ruta_webp,
                        'url' => $f->ruta_webp ?? $f->ruta_original
                    ];
                }, $fotos)
            ];
        }

        return $coloresTelas;
    }

    private function construirProcesoParaEdicion($proceso): array
    {
        $tallasProceso = $this->construirTallasProcesoRelacional($proceso->id);
        $tallas = $tallasProceso['tallas'] ?? [];
        $datosExtendidos = $tallasProceso['datosExtendidos'] ?? [];

        return [
            'id' => $proceso->id,
            'tipo' => $proceso->tipoProceso?->nombre ?? 'Tipo desconocido',
            'ubicaciones' => $proceso->ubicaciones ? (is_array($proceso->ubicaciones) ? $proceso->ubicaciones : (json_decode($proceso->ubicaciones, true) ?? [$proceso->ubicaciones])) : [],
            'observaciones' => $proceso->observaciones ?? '',
            'modo_tallas' => $proceso->modo_tallas ?? 'general',
            'tallas' => $tallas,
            'datosExtendidos' => $datosExtendidos,
            'imagenes' => $proceso->imagenes->map(function($img) {
                $ruta_webp = str_replace('\\', '/', $img->ruta_webp ?? '');
                $ruta_original = str_replace('\\', '/', $img->ruta_original ?? '');
                if ($ruta_webp) {
                    if (strpos($ruta_webp, self::STORAGE_WEB_PREFIX) !== 0) {
                        if (strpos($ruta_webp, self::STORAGE_PREFIX) === 0) {
                            $ruta_webp = '/' . $ruta_webp;
                        } elseif (strpos($ruta_webp, '/') !== 0) {
                            $ruta_webp = self::STORAGE_WEB_PREFIX . $ruta_webp;
                        }
                    }
                }
                if ($ruta_original) {
                    if (strpos($ruta_original, self::STORAGE_WEB_PREFIX) !== 0) {
                        if (strpos($ruta_original, self::STORAGE_PREFIX) === 0) {
                            $ruta_original = '/' . $ruta_original;
                        } elseif (strpos($ruta_original, '/') !== 0) {
                            $ruta_original = self::STORAGE_WEB_PREFIX . $ruta_original;
                        }
                    }
                }
                return [
                    'id' => $img->id,
                    'ruta_webp' => $ruta_webp,
                    'ruta_original' => $ruta_original,
                    'url' => $ruta_webp ?: $ruta_original,
                    'es_principal' => $img->es_principal ?? false
                ];
            })->filter(function($img) {
                return $img['ruta_webp'] || $img['ruta_original'];
            })->toArray() ?? [],
        ];
    }

    private function construirTallasProcesoRelacional($procesoPrendaDetalleId)
    {
        $tallas = [];
        $datosExtendidos = [];
        $tallasRelacionales = \App\Models\PedidosProcesosPrendaTalla::where(
            'proceso_prenda_detalle_id',
            $procesoPrendaDetalleId
        )->get();

        foreach ($tallasRelacionales as $tallaRecord) {
            $genero = strtolower($tallaRecord->genero);
            $tallas[$genero] = $tallas[$genero] ?? [];
            $datosExtendidos[$genero] = $datosExtendidos[$genero] ?? [];
            $this->agregarDatosTallaRelacional($tallaRecord, $tallas[$genero], $datosExtendidos[$genero]);
        }

        return [
            'tallas' => $tallas,
            'datosExtendidos' => $datosExtendidos
        ];
    }

    private function agregarDatosTallaRelacional($tallaRecord, array &$tallasGenero, array &$datosExtendidosGenero): void
    {
        $coloresAsociados = DB::table('pedidos_procesos_prenda_talla_colores')
            ->where('pedidos_procesos_prenda_talla_id', $tallaRecord->id)
            ->get();

        if ($coloresAsociados->count() > 0) {
            $this->agregarTallaConColores($tallaRecord, $coloresAsociados, $tallasGenero, $datosExtendidosGenero);
            return;
        }

        $this->agregarTallaSimple($tallaRecord, $tallasGenero, $datosExtendidosGenero);
    }

    private function agregarTallaConColores($tallaRecord, $coloresAsociados, array &$tallasGenero, array &$datosExtendidosGenero): void
    {
        $imagenesPorTalla = $this->obtenerImagenesPorTalla((int) $tallaRecord->id);

        foreach ($coloresAsociados as $colorRecord) {
            $tallaColorKey = $tallaRecord->talla . '__' . $colorRecord->color_nombre;
            $cantidadColor = $colorRecord->cantidad ?? $tallaRecord->cantidad;

            if ($cantidadColor > 0) {
                $tallasGenero[$tallaColorKey] = $cantidadColor;
            }

            $datosExtendidosGenero[$tallaColorKey] = [
                'cantidadSeleccionada' => $cantidadColor,
                'ubicaciones' => $this->normalizarUbicacionesArray($colorRecord->ubicaciones ?? null),
                'observaciones' => $colorRecord->observaciones ?? '',
                'imagenes' => $imagenesPorTalla,
            ];
        }
    }

    private function agregarTallaSimple($tallaRecord, array &$tallasGenero, array &$datosExtendidosGenero): void
    {
        if ($tallaRecord->cantidad > 0) {
            $tallasGenero[$tallaRecord->talla] = $tallaRecord->cantidad;
        }

        $tallaKey = $tallaRecord->talla;
        $datosExtendidosGenero[$tallaKey] = [
            'cantidadSeleccionada' => $tallaRecord->cantidad,
            'ubicaciones' => $this->normalizarUbicacionesArray($tallaRecord->ubicaciones ?? null),
            'observaciones' => $tallaRecord->observaciones ?? '',
            'imagenes' => $this->obtenerImagenesPorTalla((int) $tallaRecord->id),
        ];
    }

    private function obtenerImagenesPorTalla(int $tallaRecordId): array
    {
        return DB::table('pedidos_procesos_imagenes')
            ->where('proceso_prenda_talla_id', $tallaRecordId)
            ->whereNull('deleted_at')
            ->orderBy('orden', 'asc')
            ->get()
            ->map(fn($img) => $this->mapearImagenProceso($img))
            ->filter(fn($img) => $img['ruta_webp'] || $img['ruta_original'])
            ->values()
            ->toArray();
    }

    private function mapearImagenProceso($img): array
    {
        $rutaWebp = $this->normalizarRutaStorage($img->ruta_webp ?? '');
        $rutaOriginal = $this->normalizarRutaStorage($img->ruta_original ?? '');

        return [
            'id' => $img->id,
            'ruta_webp' => $rutaWebp,
            'ruta_original' => $rutaOriginal,
            'url' => $rutaWebp ?: $rutaOriginal,
            'es_principal' => $img->es_principal ?? false,
        ];
    }

    private function normalizarUbicacionesArray(mixed $ubicaciones): array
    {
        if (!$ubicaciones) {
            return [];
        }
        if (is_array($ubicaciones)) {
            return $ubicaciones;
        }
        $decoded = json_decode((string) $ubicaciones, true);
        return is_array($decoded) ? $decoded : [(string) $ubicaciones];
    }

    private function normalizarRutaStorage(string $ruta): string
    {
        if ($ruta === '') {
            return '';
        }
        if (!str_starts_with($ruta, self::STORAGE_WEB_PREFIX)) {
            $ruta = self::STORAGE_WEB_PREFIX . ltrim($ruta, '/');
        }
        return $ruta;
    }
}
