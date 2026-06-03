<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PedidoPrendaDetalleBuilder
{
    public function __construct(private readonly PedidoDetalleReadService $pedidoDetalleReadService)
    {
    }

    public function construirPrendasCompletas($modeloEloquent, ?string $estadoPedido = null, bool $filtrarProcesosPendientes = false, bool $modoBodega = false): array
    {
        try {
            if (!$modeloEloquent || !$modeloEloquent->prendas) {
                Log::warning('Pedido sin prendas', ['pedido_id' => $modeloEloquent?->id]);
                return [];
            }

            $prendasArray = [];

            foreach ($modeloEloquent->prendas as $prenda) {
                $tallasEstructuradas = $this->construirEstructuraTallas($prenda);
                $variantes = $this->obtenerVariantes($prenda);
                $colorTela = $this->obtenerColorYTela($prenda);
                $imagenes = $modoBodega ? [] : $this->obtenerImagenesPrenda($prenda);
                $imagenesTela = $modoBodega ? [] : $this->obtenerImagenesTela($prenda);
                $coloresTelas = $this->obtenerColoresTelasCompletos($prenda);
                $procesos = $this->obtenerProcesosDelaPrenda($prenda, $estadoPedido, $filtrarProcesosPendientes);
                $recibosPrenda = $this->pedidoDetalleReadService->getConsecutivosPrenda((int) $modeloEloquent->id, (int) $prenda->id);
                $numeroReciboUnico = $this->extraerNumeroReciboUnico($recibosPrenda);

                // En modo bodega, ancho y metraje sin detalles pesados
                $anchoMetraje = [];
                if ($numeroReciboUnico !== null) {
                    $ancho = $this->pedidoDetalleReadService->getAnchoPrenda((int) $modeloEloquent->id, (int) $prenda->id, $numeroReciboUnico);
                    if ($ancho) {
                        $anchoMetraje = [
                            'ancho' => $ancho->ancho,
                            'metraje' => $ancho->metraje,
                            'metrajes_por_color' => $this->obtenerMetrajesPorColorPrenda((int) $modeloEloquent->id, (int) $prenda->id, $numeroReciboUnico),
                            'tipo_modo' => $ancho->tipo_modo,
                            'contenido_mano' => $ancho->contenido_mano,
                            'observaciones' => $ancho->observaciones ?? null,
                            'numero_recibo' => $numeroReciboUnico,
                        ];
                    }
                }

                $prendasArray[] = [
                    'id' => $prenda->id,
                    'prenda_pedido_id' => $prenda->id,
                    'nombre' => $prenda->nombre_prenda,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'numero' => $prenda->numero ?? null,
                    'tela' => $colorTela['tela'] ?? null,
                    'color' => $colorTela['color'] ?? null,
                    'ref' => $colorTela['ref_tela'] ?? null,
                    'origen' => $prenda->origen ?? null,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => (bool) $prenda->de_bodega,
                    'tallas' => $tallasEstructuradas,
                    'talla_colores' => $this->obtenerTallaColoresDelaPrenda($prenda),
                    'variantes' => $variantes,
                    'imagenes' => $imagenes,
                    'imagenes_disenos_logo' => $this->obtenerImagenesDiseñosLogo($prenda),
                    'imagenes_tela' => $imagenesTela,
                    'colores_telas' => $coloresTelas,
                    'telas_array' => $coloresTelas,
                    'procesos' => $procesos,
                    'manga' => $variantes[0]['manga'] ?? null,
                    'obs_manga' => $variantes[0]['manga_obs'] ?? null,
                    'broche' => $variantes[0]['broche'] ?? null,
                    'obs_broche' => $variantes[0]['broche_obs'] ?? null,
                    'tiene_bolsillos' => isset($variantes[0]) ? (bool) $variantes[0]['bolsillos'] : false,
                    'obs_bolsillos' => $variantes[0]['bolsillos_obs'] ?? null,
                    'tiene_reflectivo' => false,
                    'ancho_metraje' => $anchoMetraje,
                ];
            }

            return $prendasArray;
        } catch (\Exception $e) {
            Log::error('Error obteniendo prendas completas: ' . $e->getMessage(), [
                'pedido_id' => $modeloEloquent?->id,
            ]);
            return [];
        }
    }

    private function obtenerMetrajesPorColorPrenda(int $pedidoId, int $prendaId, ?int $numeroRecibo = null): array
    {
        try {
            return $this->pedidoDetalleReadService
                ->getMetrajesPrenda($pedidoId, $prendaId, $numeroRecibo)
                ->map(fn($metraje) => [
                    'color' => $metraje->color ?? null,
                    'metraje' => $metraje->metraje ?? null,
                ])
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('[PedidoPrendaDetalleBuilder] Error obteniendo metrajes por color', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function extraerNumeroReciboUnico($recibosPrenda): ?int
    {
        if (!$recibosPrenda || !is_iterable($recibosPrenda)) {
            return null;
        }

        $numeros = [];

        foreach ($recibosPrenda as $clave => $recibo) {
            if ($clave === 'parciales' && is_iterable($recibo)) {
                foreach ($recibo as $parcial) {
                    $numero = (int) ($parcial['consecutivo_actual'] ?? $parcial['numero_recibo'] ?? 0);
                    if ($numero > 0) {
                        $numeros[$numero] = true;
                    }
                }
                continue;
            }

            if (is_array($recibo)) {
                $numero = (int) ($recibo['consecutivo_actual'] ?? $recibo['numero_recibo'] ?? 0);
                if ($numero > 0) {
                    $numeros[$numero] = true;
                }
            }
        }

        $numeros = array_keys($numeros);

        return count($numeros) === 1 ? (int) $numeros[0] : null;
    }

    private function construirEstructuraTallas($prenda): array
    {
        $tallasPorGenero = [
            'DAMA' => [],
            'CABALLERO' => [],
            'UNISEX' => [],
        ];

        $tallasColores = $this->pedidoDetalleReadService->getTallasColoresPrenda((int) $prenda->id);

        if ($tallasColores->isNotEmpty()) {
            $this->aplicarTallasDesdeFlujo2($tallasPorGenero, $tallasColores);
        } else {
            $this->aplicarTallasDesdeFlujo1($tallasPorGenero, $prenda);
        }

        return $tallasPorGenero;
    }

    private function aplicarTallasDesdeFlujo2(array &$tallasPorGenero, $tallasColores): void
    {
        $tallasAgrupadas = [];
        foreach ($tallasColores as $tallaColor) {
            $genero = strtoupper($tallaColor->genero);
            $talla = $tallaColor->talla;
            $cantidad = (int) $tallaColor->cantidad;

            $key = $genero . '|' . $talla;
            if (!isset($tallasAgrupadas[$key])) {
                $tallasAgrupadas[$key] = [
                    'genero' => $genero,
                    'talla' => $talla,
                    'cantidad' => 0,
                ];
            }
            $tallasAgrupadas[$key]['cantidad'] += $cantidad;
        }

        foreach ($tallasAgrupadas as $item) {
            $tallasPorGenero[$item['genero']][$item['talla']] = $item['cantidad'];
        }
    }

    private function aplicarTallasDesdeFlujo1(array &$tallasPorGenero, $prenda): void
    {
        try {
            if (!$prenda->tallas) {
                return;
            }

            foreach ($prenda->tallas as $talla) {
                $genero = strtoupper($talla->genero ?? 'GENERAL');
                $tallasPorGenero[$genero][$talla->talla] = (int) $talla->cantidad;
            }
        } catch (\Exception $e) {
            Log::warning('Error construyendo estructura de tallas (flujo 1)', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function obtenerVariantes($prenda): array
    {
        $variantes = [];

        try {
            $especificaciones = $this->obtenerEspecificacionesVariante($prenda);
            $coloresPorTalla = $this->obtenerColoresPorTalla($prenda);
            $variantes = $this->construirVariantesDesdeTallas($prenda, $coloresPorTalla, $especificaciones);
        } catch (\Exception $e) {
            Log::warning('Error obteniendo variantes', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $variantes;
    }

    private function obtenerEspecificacionesVariante($prenda): array
    {
        $especificaciones = [
            'tipo_manga_id' => null,
            'manga' => null,
            'manga_obs' => '',
            'tipo_broche_boton_id' => null,
            'broche' => null,
            'broche_obs' => '',
            'tiene_bolsillos' => false,
            'bolsillos' => false,
            'bolsillos_obs' => '',
        ];

        if (!$prenda->variantes || $prenda->variantes->count() === 0) {
            return $especificaciones;
        }

        $primeraVariante = $prenda->variantes->first();

        if ($primeraVariante->tipo_manga_id) {
            $especificaciones['tipo_manga_id'] = $primeraVariante->tipo_manga_id;
            $especificaciones['manga'] = $primeraVariante->tipoManga->nombre ?? null;
        }

        if ($primeraVariante->tipo_broche_boton_id) {
            $especificaciones['tipo_broche_boton_id'] = $primeraVariante->tipo_broche_boton_id;
            $especificaciones['broche'] = $primeraVariante->tipoBroche->nombre ?? null;
        }

        $especificaciones['manga_obs'] = $primeraVariante->manga_obs ?? '';
        $especificaciones['broche_obs'] = $primeraVariante->broche_boton_obs ?? '';
        $especificaciones['tiene_bolsillos'] = (bool) ($primeraVariante->tiene_bolsillos ?? false);
        $especificaciones['bolsillos'] = (bool) ($primeraVariante->tiene_bolsillos ?? false);
        $especificaciones['bolsillos_obs'] = $primeraVariante->bolsillos_obs ?? '';

        return $especificaciones;
    }

    private function obtenerColoresPorTalla($prenda): array
    {
        $coloresPorTalla = [];

        try {
            $coloresTalla = $this->pedidoDetalleReadService->getColoresPorTallaPrenda((int) $prenda->id);

            foreach ($coloresTalla as $colorTalla) {
                $clave = $colorTalla->talla_id;
                $coloresPorTalla[$clave] ??= [];
                $coloresPorTalla[$clave][] = [
                    'talla_color_id' => $colorTalla->talla_color_id,
                    'color' => $colorTalla->color_nombre,
                    'cantidad' => $colorTalla->cantidad,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo colores por talla', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $coloresPorTalla;
    }

    private function construirVariantesDesdeTallas($prenda, array $coloresPorTalla, array $especificaciones): array
    {
        $variantes = [];
        $tallas = $prenda->tallas ?? null;

        if (!$tallas || $tallas->count() === 0) {
            return $variantes;
        }

        // Agrupar tallas por tipo:
        // - SOBREMEDIDA: agrupar por genero (sin talla especifica)
        // - Resto (incluye UNISEX): agrupar por genero y talla
        $tallasAgrupadas = [];
        foreach ($tallas as $talla) {
            $genero = strtoupper($talla->genero ?? 'GENERAL');
            $esSobremedida = (bool)($talla->es_sobremedida ?? false);
            $tallaSize = $talla->talla ?? '-';
            
            // Determinar clave de agrupacion
            if ($esSobremedida) {
                // SOBREMEDIDA: agrupar por genero sin talla especifica
                $groupKey = "SOBREMEDIDA_{$genero}";
            } else {
                // Todos los generos (incluye UNISEX): agrupar por genero y talla
                $groupKey = "{$genero}_{$tallaSize}";
            }
            
            
            if (!isset($tallasAgrupadas[$groupKey])) {
                $tallasAgrupadas[$groupKey] = [
                    'talla' => $talla,
                    'cantidad_total' => 0,
                    'coloresEspecificos' => [],
                    'es_sobremedida' => $esSobremedida,
                ];
            }
            
            // Sumar cantidad
            $tallasAgrupadas[$groupKey]['cantidad_total'] += (int)$talla->cantidad;
            
            // Agregar colores especificos (merged)
            $coloresEspecificos = $coloresPorTalla[$talla->id] ?? [];
            foreach ($coloresEspecificos as $color) {
                $tallasAgrupadas[$groupKey]['coloresEspecificos'][] = $color;
            }
        }

        // Crear variantes agrupadas
        foreach ($tallasAgrupadas as $groupData) {
            $talla = $groupData['talla'];
            $talla->cantidad = $groupData['cantidad_total'];
            
            // Si es sobremedida: mostrar "SOBREMEDIDA" en la talla (sin importar genero)
            if ($groupData['es_sobremedida']) {
                $talla->talla = 'SOBREMEDIDA';
            }
            
            $colores = $groupData['coloresEspecificos'];
            
            // Si hay colores definidos pero la suma es menor al total de la talla, 
            // completamos con un registro "Sin color" para que no se pierdan unidades en la UI.
            if (!empty($colores)) {
                $sumaColores = 0;
                foreach ($colores as $c) {
                    $sumaColores += (int)($c['cantidad'] ?? 0);
                }
                
                if ($sumaColores < (int)$talla->cantidad) {
                    $colores[] = [
                        'talla_color_id' => null,
                        'color' => 'SIN COLOR DEFINIDO',
                        'cantidad' => (int)$talla->cantidad - $sumaColores,
                    ];
                }
            }
            
            $variantes[] = $this->crearVariantePorTalla($talla, $colores, $especificaciones);
        }

        return $variantes;
    }

    private function crearVariantePorTalla($talla, array $coloresEspecificos, array $especificaciones): array
    {
        return [
            'talla_id' => $talla->id,
            'talla' => $talla->talla,
            'genero' => $talla->genero,
            'cantidad' => (int) $talla->cantidad,
            'es_sobremedida' => (bool) ($talla->es_sobremedida ?? false),
            'color_info' => $this->construirColorInfo($coloresEspecificos),
            'colores_detalle' => $coloresEspecificos,
            'tipo_manga_id' => $especificaciones['tipo_manga_id'] ?? null,
            'manga' => $especificaciones['manga'],
            'manga_obs' => $especificaciones['manga_obs'],
            'tipo_broche_boton_id' => $especificaciones['tipo_broche_boton_id'] ?? null,
            'broche' => $especificaciones['broche'],
            'broche_obs' => $especificaciones['broche_obs'],
            'tiene_bolsillos' => $especificaciones['tiene_bolsillos'] ?? false,
            'bolsillos' => $especificaciones['bolsillos'],
            'bolsillos_obs' => $especificaciones['bolsillos_obs'],
        ];
    }

    private function construirColorInfo(array $coloresEspecificos): string
    {
        if ($coloresEspecificos === []) {
            return '';
        }

        $partesColor = [];
        foreach ($coloresEspecificos as $color) {
            $partesColor[] = "{$color['cantidad']}-{$color['color']}";
        }

        return implode(', ', $partesColor);
    }

    private function obtenerColorYTela($prenda): array
    {
        $colorTela = ['color' => null, 'tela' => null, 'ref_tela' => null];

        try {
            if ($prenda->coloresTelas && $prenda->coloresTelas->first()) {
                $ct = $prenda->coloresTelas->first();

                if ($ct->color) {
                    $colorTela['color'] = $ct->color->nombre;
                }

                if ($ct->tela) {
                    $colorTela['tela'] = $ct->tela->nombre;
                    $colorTela['ref_tela'] = $ct->tela->referencia;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo color y tela', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return $colorTela;
    }

    private function obtenerImagenesTela($prenda): array
    {
        try {
            $imagenes = array_merge(
                $this->obtenerImagenesTelaDesdeColoresTelas($prenda),
                $this->obtenerImagenesTelaDesdeTallaColor($prenda)
            );

            $imagenes = $this->deduplicarImagenesPorUrl($imagenes);
            $this->ordenarImagenesPorOrden($imagenes);

            return $imagenes;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function obtenerImagenesTelaDesdeColoresTelas($prenda): array
    {
        $imagenes = [];
        $coloresTelas = $prenda->coloresTelas ?? null;

        if (!$coloresTelas || $coloresTelas->count() === 0) {
            return $imagenes;
        }

        foreach ($coloresTelas as $ct) {
            if (!$ct) {
                continue;
            }

            $this->asegurarFotosColorTela($ct);

            foreach (($ct->fotos ?? []) as $foto) {
                $rutaWebp = $foto->ruta_webp ?? $foto->url ?? null;
                $rutaOriginal = $foto->ruta_original ?? $rutaWebp ?? null;

                $imagen = $this->crearRegistroImagen(
                    $foto->id ?? null,
                    $rutaWebp,
                    $rutaOriginal,
                    (int) ($foto->orden ?? 0),
                    (bool) ($foto->es_principal ?? false)
                );

                if ($imagen !== null) {
                    $imagenes[] = $imagen;
                }
            }
        }

        return $imagenes;
    }

    private function asegurarFotosColorTela($colorTela): void
    {
        if (isset($colorTela->fotos) && $colorTela->fotos) {
            return;
        }

        try {
            $colorTela->load('fotos');
        } catch (\Exception $e) {
            // silencioso
        }
    }

    private function obtenerImagenesTelaDesdeTallaColor($prenda): array
    {
        $imagenes = [];
        $rutas = $this->obtenerRutasImagenesDesdeTallaColor((int) ($prenda->id ?? 0));

        foreach ($rutas as $ruta) {
            $imagen = $this->crearRegistroImagen(null, $ruta, $ruta, 0, false);
            if ($imagen !== null) {
                $imagenes[] = $imagen;
            }
        }

        return $imagenes;
    }

    private function obtenerRutasImagenesDesdeTallaColor(int $prendaId): array
    {
        if ($prendaId <= 0) {
            return [];
        }

        try {
            $raw = $this->pedidoDetalleReadService
                ->getImagenRutasTallaColorPrenda($prendaId)
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }

        $rutas = [];
        foreach ($raw as $item) {
            $rutas = array_merge($rutas, $this->parsearCampoImagenRuta($item));
        }

        return $rutas;
    }

    private function parsearCampoImagenRuta($item): array
    {
        if (!is_string($item)) {
            return [];
        }

        $trim = trim($item);
        if ($trim === '') {
            return [];
        }

        $resultado = [$trim];
        $esJson = isset($trim[0]) && ($trim[0] === '[' || $trim[0] === '{');

        if ($esJson) {
            $decoded = json_decode($trim, true);
            if (is_array($decoded)) {
                $rutas = array_values(array_filter($decoded, 'is_string'));
                if ($rutas !== []) {
                    $resultado = $rutas;
                }
            }
        }

        return $resultado;
    }

    private function crearRegistroImagen($id, ?string $rutaWebp, ?string $rutaOriginal, int $orden, bool $esPrincipal): ?array
    {
        $rutaOriginal = $rutaOriginal ?? $rutaWebp;
        $url = $this->normalizarRutaImagen($rutaWebp ?: $rutaOriginal);

        if (!$url) {
            return null;
        }

        return [
            'id' => $id,
            'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
            'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
            'url' => $url,
            'orden' => $orden,
            'es_principal' => $esPrincipal,
        ];
    }

    private function deduplicarImagenesPorUrl(array $imagenes): array
    {
        $seen = [];
        $dedup = [];

        foreach ($imagenes as $imagen) {
            $key = $imagen['url'] ?? null;
            if (!$key || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $dedup[] = $imagen;
        }

        return $dedup;
    }

    private function ordenarImagenesPorOrden(array &$imagenes): void
    {
        usort($imagenes, function ($a, $b) {
            return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
        });
    }

    private function obtenerProcesosDelaPrenda($prenda, ?string $estadoPedido = null, bool $filtrarProcesosPendientes = false): array
    {
        $procesosOut = [];

        try {
            if (!$this->debeOmitirProcesosPorEstadoPedido($estadoPedido)) {
                foreach (($prenda->procesos ?? []) as $proceso) {
                    $procesoMapeado = $this->mapearProcesoSiAplica($proceso, $filtrarProcesosPendientes);
                    if ($procesoMapeado !== null) {
                        $procesosOut[] = $procesoMapeado;
                    }
                }
            }
        } catch (\Exception $e) {
            $procesosOut = [];
        }

        return $procesosOut;
    }

    private function debeOmitirProcesosPorEstadoPedido(?string $estadoPedido): bool
    {
        $estadoPedidoNorm = strtolower(trim((string) ($estadoPedido ?? '')));
        return $estadoPedidoNorm === 'pendiente';
    }

    private function mapearProcesoSiAplica($proceso, bool $filtrarProcesosPendientes): ?array
    {
        if (!$proceso || $this->debeExcluirProcesoPorEstado($proceso, $filtrarProcesosPendientes)) {
            return null;
        }

        $tipo = $this->resolverTipoProceso($proceso);

        return [
            'id' => $proceso->id ?? null,
            'tipo_proceso_id' => $proceso->tipo_proceso_id ?? ($proceso->tipoProceso->id ?? null),
            'tipo_proceso' => $tipo,
            'nombre_proceso' => $tipo,
            'estado' => $proceso->estado ?? null,
            'observaciones' => $proceso->observaciones ?? '',
            'ubicaciones' => $proceso->ubicaciones ?? [],
            'modo_tallas' => $proceso->modo_tallas ?? 'general',
            'tallas' => $this->obtenerTallasDelProceso($proceso),
            'imagenes' => $this->obtenerImagenesDelProceso($proceso),
            'fecha_aprobacion' => $proceso->fecha_aprobacion ?? null,
            'aprobado_por' => $proceso->aprobado_por ?? null,
        ];
    }

    private function debeExcluirProcesoPorEstado($proceso, bool $filtrarProcesosPendientes): bool
    {
        $estadoProc = strtolower(trim((string) ($proceso->estado ?? $proceso['estado'] ?? '')));
        return $filtrarProcesosPendientes && $estadoProc === 'pendiente';
    }

    private function resolverTipoProceso($proceso): ?string
    {
        $tipo = $proceso->tipoProceso->nombre ?? null;
        if ($tipo) {
            return $tipo;
        }

        return $proceso->tipo_proceso ?? $proceso->nombre_proceso ?? $proceso->nombre ?? null;
    }

    private function obtenerImagenesDelProceso($proceso): array
    {
        $imagenes = [];

        try {
            foreach (($proceso->imagenes ?? []) as $img) {
                $rutaWebp = $img->ruta_webp ?? $img->ruta_web ?? $img->url ?? null;
                $rutaOriginal = $img->ruta_original ?? $rutaWebp ?? null;

                $imagen = $this->crearRegistroImagen(
                    $img->id ?? null,
                    $rutaWebp,
                    $rutaOriginal,
                    (int) ($img->orden ?? 0),
                    (bool) ($img->es_principal ?? false)
                );

                if ($imagen !== null) {
                    $imagenes[] = $imagen;
                }
            }

            $this->ordenarImagenesPorOrden($imagenes);
        } catch (\Exception $e) {
            $imagenes = [];
        }

        return $imagenes;
    }

    private function obtenerTallasDelProceso($proceso): array
    {
        $tallas = [];

        if (!isset($proceso->tallas) || !$proceso->tallas) {
            return $tallas;
        }

        try {
            $tallas = $this->construirEstructuraTallasDelProceso($proceso->tallas);
        } catch (\Exception $e) {
            Log::warning('Error construyendo tallas del proceso', [
                'proceso_id' => $proceso->id ?? null,
                'error' => $e->getMessage(),
            ]);
            $tallas = [];
        }

        return $tallas;
    }

    private function obtenerImagenesPrenda($prenda): array
    {
        $imagenes = [];

        try {
            if ($prenda->fotos && $prenda->fotos->count() > 0) {
                foreach ($prenda->fotos as $foto) {
                    $rutaWebp = $foto->ruta_webp ?? $foto->url ?? null;
                    $rutaOriginal = $foto->ruta_original ?? $rutaWebp ?? null;

                    $imagenes[] = [
                        'id' => $foto->id ?? null,
                        'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                        'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                        'url' => $this->normalizarRutaImagen($rutaWebp ?: $rutaOriginal),
                        'orden' => (int) ($foto->orden ?? 0),
                        'es_principal' => (bool) ($foto->es_principal ?? false),
                    ];
                }
            }

            $this->ordenarImagenesPorOrden($imagenes);
        } catch (\Exception $e) {
            return [];
        }

        return $imagenes;
    }

    private function obtenerColoresTelasCompletos($prenda): array
    {
        $coloresTelas = [];

        try {
            $coleccionColoresTelas = $this->obtenerColeccionColoresTelas($prenda);

            foreach ($coleccionColoresTelas as $ct) {
                if (!$ct) {
                    continue;
                }
                $this->asegurarRelacionesColorTela($ct);
                $coloresTelas[] = $this->mapearColorTelaCompleto($ct, $prenda);
            }
        } catch (\Exception $e) {
            Log::warning('[obtenerColoresTelasCompletos] Error', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return $coloresTelas;
    }

    private function obtenerColeccionColoresTelas($prenda): Collection
    {
        $coleccion = $prenda->coloresTelas ?? null;
        if ($coleccion instanceof Collection) {
            return $coleccion;
        }

        return collect();
    }

    private function asegurarRelacionesColorTela($ct): void
    {
        if (!isset($ct->color) || !$ct->color) {
            $ct->load('color');
        }
        if (!isset($ct->tela) || !$ct->tela) {
            $ct->load('tela');
        }
        if (!isset($ct->fotos) || !$ct->fotos) {
            $ct->load('fotos');
        }
    }

    private function mapearColorTelaCompleto($ct, $prenda): array
    {
        return [
            'id' => $ct->id,
            'prenda_pedido_id' => $ct->prenda_pedido_id ?? ($prenda->id ?? null),
            'color_id' => $ct->color_id,
            'color_nombre' => $ct->color?->nombre ?? null,
            'color_codigo' => $ct->color?->codigo ?? null,
            'tela_id' => $ct->tela_id,
            'tela_nombre' => $ct->tela?->nombre ?? null,
            'tela_referencia' => $ct->tela?->referencia ?? ($ct->referencia ?? null),
            'referencia' => $ct->referencia ?? null,
            'fotos' => $this->mapearFotosColorTela($ct->fotos ?? null),
        ];
    }

    private function mapearFotosColorTela($fotosColeccion): array
    {
        $fotos = [];
        foreach (($fotosColeccion ?? []) as $foto) {
            $rutaWebp = $foto->ruta_webp ?? $foto->url ?? null;
            $rutaOriginal = $foto->ruta_original ?? $rutaWebp ?? null;
            $fotos[] = [
                'id' => $foto->id ?? null,
                'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                'url' => $this->normalizarRutaImagen($rutaWebp ?: $rutaOriginal),
                'orden' => (int) ($foto->orden ?? 0),
                'es_principal' => (bool) ($foto->es_principal ?? false),
            ];
        }

        $this->ordenarImagenesPorOrden($fotos);

        return $fotos;
    }

    private function obtenerTallaColoresDelaPrenda($prenda): array
    {
        try {
            return $this->pedidoDetalleReadService
                ->getTallaColoresDetallePrenda((int) $prenda->id)
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('[obtenerTallaColoresDelaPrenda] Error obteniendo colores por talla', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function construirEstructuraTallasDelProceso($tallasColeccion): array
    {
        $tallasPorGenero = [
            'DAMA' => [],
            'CABALLERO' => [],
            'UNISEX' => [],
        ];

        if (!$tallasColeccion) {
            return $tallasPorGenero;
        }

        try {
            $tallasArray = [];
            if ($tallasColeccion instanceof Collection) {
                $tallasArray = $tallasColeccion->toArray();
            } elseif (is_array($tallasColeccion)) {
                $tallasArray = $tallasColeccion;
            }

            foreach ($tallasArray as $talla) {
                ['genero' => $genero, 'talla' => $tallaNombre, 'cantidad' => $cantidad] =
                    $this->normalizarTallaProceso($talla);

                if (!$this->esGeneroValidoProceso($genero)) {
                    continue;
                }

                if ($cantidad > 0) {
                    $tallasPorGenero[$genero][$tallaNombre] = $cantidad;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error transformando tallas del proceso', [
                'error' => $e->getMessage(),
            ]);
        }

        return $tallasPorGenero;
    }

    private function normalizarTallaProceso($talla): array
    {
        if (is_array($talla)) {
            return [
                'genero' => strtoupper($talla['genero'] ?? ''),
                'talla' => $talla['talla'] ?? '',
                'cantidad' => (int) ($talla['cantidad'] ?? 0),
            ];
        }

        return [
            'genero' => strtoupper($talla->genero ?? ''),
            'talla' => $talla->talla ?? '',
            'cantidad' => (int) ($talla->cantidad ?? 0),
        ];
    }

    private function esGeneroValidoProceso(string $genero): bool
    {
        return in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true);
    }

    private function normalizarRutaImagen(?string $ruta): ?string
    {
        $rutaNormalizada = null;

        if (!$ruta) {
            return $rutaNormalizada;
        }

        $ruta = str_replace('\\', '/', $ruta);

        if (str_starts_with($ruta, 'http')) {
            $rutaNormalizada = $ruta;
        } elseif (str_starts_with($ruta, '/storage/')) {
            $rutaNormalizada = $ruta;
        } elseif (str_starts_with($ruta, 'storage/')) {
            $rutaNormalizada = '/' . $ruta;
        } else {
            $rutaNormalizada = '/storage/' . ltrim($ruta, '/');
        }

        return $rutaNormalizada;
    }

    /**
     * Obtiene las imágenes de diseños logo asociadas a los procesos de la prenda
     */
    private function obtenerImagenesDiseñosLogo($prenda): array
    {
        try {
            $imagenes = [];

            // Obtener todos los procesos de la prenda usando la relación correcta
            if (!isset($prenda->procesos) || (is_countable($prenda->procesos) && count($prenda->procesos) === 0)) {
                return [];
            }

            // Para cada proceso, obtener diseños logo asociados desde la relación cargada
            foreach ($prenda->procesos as $procesoPrendaDetalle) {
                // Usar la relación disenosLogo que ahora está eagerly loaded
                if (isset($procesoPrendaDetalle->disenosLogo) && count($procesoPrendaDetalle->disenosLogo) > 0) {
                    foreach ($procesoPrendaDetalle->disenosLogo as $diseño) {
                        if ($diseño->url) {
                            $imagenes[] = [
                                'id' => $diseño->id,
                                'url' => $this->normalizarRutaImagen($diseño->url),
                                'ruta_webp' => $this->normalizarRutaImagen($diseño->url),
                                'ruta_original' => $this->normalizarRutaImagen($diseño->url),
                                'tipo' => 'diseño-logo',
                                'orden' => 0,
                                'observacion' => $diseño->observacio_diseño ?? null,
                                'estado' => $diseño->estado,
                            ];
                        }
                    }
                }
            }

            return $imagenes;
        } catch (\Exception $e) {
            Log::warning('[obtenerImagenesDiseñosLogo] Error obteniendo diseños logo', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
