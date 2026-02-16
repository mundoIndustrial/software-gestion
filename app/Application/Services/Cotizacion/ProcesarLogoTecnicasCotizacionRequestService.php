<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use App\Models\LogoCotizacionTecnicaPrenda;
use App\Models\LogoFotoCot;
use App\Models\PrendaCot;
use App\Application\Cotizacion\DTOs\SyncLogoTecnicasDTO;
use Illuminate\Support\Facades\Log;

final class ProcesarLogoTecnicasCotizacionRequestService
{
    public function __construct(
        private readonly \App\Application\Services\ProcesarImagenesCotizacionService $procesarImagenesService,
        private readonly EliminarFotoInmediatamenteService $eliminarFotoInmediatamenteService,
    ) {
    }

    public function ejecutar(SyncLogoTecnicasDTO $dto): void
    {
        try {
            $cotizacionId = $dto->cotizacionId;
            $cotizacionExistente = Cotizacion::find($cotizacionId);
            $esUpdate = !!$cotizacionExistente;

            $logoArchivos = $dto->logoArchivos;
            $logoObservacionesGenerales = $dto->observacionesGenerales;
            $tipoCotizacionRequest = $dto->tipoCotizacion;
            $logoTecnicasAgregadas = $dto->tecnicasAgregadas;
            $tecnicasAgregadasPresent = $dto->tecnicasAgregadasPresent;

            $logoTieneInformacionValida = false;

            // IMPORTANTE: en update, puede venir sin imágenes (cuando el usuario las eliminó),
            // pero igual necesitamos procesar/sincronizar para borrar fotos anteriores.
            if (!empty($logoTecnicasAgregadas) && is_array($logoTecnicasAgregadas)) {
                foreach ($logoTecnicasAgregadas as $tecnica) {
                    if (!empty($tecnica['prendas']) && is_array($tecnica['prendas'])) {
                        foreach ($tecnica['prendas'] as $prenda) {
                            $tieneUbicaciones = !empty($prenda['ubicaciones']);
                            $tieneImagenes = !empty($prenda['imagenes']);

                            if ($tieneUbicaciones) {
                                $logoTieneInformacionValida = true;
                                break 2;
                            }
                        }
                    }
                }
            }

            if (!$logoTieneInformacionValida) {
                if (!empty($dto->imagenesPaso3Archivos)) {
                    $logoTieneInformacionValida = true;
                }
            }

            if ($tipoCotizacionRequest === 'P') {
                $logoTieneInformacionValida = false;
            }

            $logoCotizacion = null;
            $logoFueCreadoNuevo = false;

            if ($logoTieneInformacionValida) {
                $logoExistente = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();

                if ($logoExistente) {
                    $datosActualizar = [
                        'observaciones_generales' => is_array($logoObservacionesGenerales) && !empty($logoObservacionesGenerales)
                            ? json_encode($logoObservacionesGenerales)
                            : $logoExistente->observaciones_generales,
                        'tipo_venta' => $dto->tipoVenta ?? $logoExistente->tipo_venta,
                    ];

                    $logoExistente->update($datosActualizar);
                    $logoCotizacion = $logoExistente;
                } else {
                    $logoCotizacion = LogoCotizacion::create([
                        'cotizacion_id' => $cotizacionId,
                        'observaciones_generales' => is_array($logoObservacionesGenerales) ? json_encode($logoObservacionesGenerales) : $logoObservacionesGenerales,
                        'tipo_venta' => $dto->tipoVenta,
                    ]);
                    $logoFueCreadoNuevo = true;
                }
            } elseif ($esUpdate) {
                // En UPDATE, aunque no haya información válida (por ejemplo porque el usuario eliminó
                // todas las técnicas/prendas del Paso 3), debemos cargar el LogoCotizacion existente
                // para poder ejecutar el sync de limpieza y borrar registros huérfanos.
                $logoCotizacion = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
            }

            if ($logoCotizacion) {
                if (!empty($logoArchivos)) {
                    $orden = 1;
                    foreach ($logoArchivos as $foto) {
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            $ruta = $this->procesarImagenesService->procesarImagenLogo($foto, $cotizacionId);

                            try {
                                $logoCotizacion->fotos()->create([
                                    'ruta_original' => $ruta,
                                    'ruta_webp' => $ruta,
                                    'orden' => $orden,
                                ]);
                            } catch (\Exception $e) {
                                Log::error('ERROR al crear foto de logo', [
                                    'cotizacion_id' => $cotizacionId,
                                    'logo_cotizacion_id' => $logoCotizacion->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }

                            $orden++;
                        }
                    }
                }

                $fotoLogosExistentes = $dto->logoFotosExistentes;

                if (!empty($fotoLogosExistentes) && $logoFueCreadoNuevo) {
                    $fotoLogosExistentes = array_unique($fotoLogosExistentes);
                    $orden = 1;

                    foreach ($fotoLogosExistentes as $fotoIdExistente) {
                        if ($fotoIdExistente && is_string($fotoIdExistente)) {
                            $fotoExistente = LogoFotoCot::find($fotoIdExistente);
                            if (!$fotoExistente) {
                                continue;
                            }

                            $rutaOriginal = $fotoExistente->ruta_original;
                            if (strpos($rutaOriginal, '/storage/') === 0) {
                                $rutaOriginal = substr($rutaOriginal, 9);
                            }

                            $rutaWebp = $fotoExistente->ruta_webp;
                            if (strpos($rutaWebp, '/storage/') === 0) {
                                $rutaWebp = substr($rutaWebp, 9);
                            }

                            try {
                                $logoCotizacion->fotos()->create([
                                    'ruta_original' => $rutaOriginal,
                                    'ruta_webp' => $rutaWebp,
                                    'orden' => $orden,
                                ]);
                                $orden++;
                            } catch (\Exception $e) {
                                Log::warning('Error al reutilizar foto de logo', [
                                    'foto_id' => $fotoIdExistente,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                }
            }

            if (!$logoCotizacion) {
                return;
            }

            $tecnicasAgregadas = $logoTecnicasAgregadas;

            $normalizarArrayRecursivo = function ($v) use (&$normalizarArrayRecursivo) {
                if (!is_array($v)) {
                    return $v;
                }
                foreach ($v as $k => $val) {
                    $v[$k] = $normalizarArrayRecursivo($val);
                }
                $keys = array_keys($v);
                $esIndexado = ($keys === range(0, count($v) - 1));
                if ($esIndexado) {
                    $allScalar = true;
                    foreach ($v as $val) {
                        if (is_array($val) || is_object($val)) {
                            $allScalar = false;
                            break;
                        }
                    }
                    if ($allScalar) {
                        sort($v);
                    }
                } else {
                    ksort($v);
                }
                return $v;
            };

            $jsonEstable = function ($v) use ($normalizarArrayRecursivo) {
                if (is_string($v)) {
                    $decoded = json_decode($v, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $v = $decoded;
                    }
                }
                $v = is_array($v) ? $normalizarArrayRecursivo($v) : $v;
                return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
            };

            $firmaTecnicaPrenda = function (int $tipoLogoId, int $prendaCotId, string $ubicacionesJson, string $tallaCantidadJson): string {
                return $tipoLogoId . '|' . $prendaCotId . '|' . $ubicacionesJson . '|' . $tallaCantidadJson;
            };

            $firmasEsperadas = [];
            $prendaCotIdsEsperadas = [];

            // Para limpieza al eliminar una prenda completa del Paso 3:
            // guardamos el set de prenda_cot_id que existían asociadas a técnicas ANTES de sincronizar.
            $prendaCotIdsAntes = [];
            if ($esUpdate) {
                $logoExistente = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
                if ($logoExistente) {
                    $prendaCotIdsAntes = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoExistente->id)
                        ->pluck('prenda_cot_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                }
            }

            // IMPORTANTE: al reutilizar una prenda del Paso 2 en el Paso 3, esa prenda YA puede tener técnicas/logo.
            // Por lo tanto NO se puede filtrar por whereDoesntHave('logoCotizacionesTecnicas'), porque eso haría
            // que no se encuentre por prenda_paso2_index y termine creándose una nueva PrendaCot.
            //
            // Antes se intentaba identificar "prendas del paso 2" con prenda_bodega=false, pero en el flujo real
            // (especialmente en combinadas) una prenda del paso 2 puede guardarse con prenda_bodega=true.
            // Eso rompe el mapeo por índice y causa duplicados en prendas_cot.
            //
            // Solución: usar un orden estable de TODAS las prendas de la cotización para resolver prenda_paso2_index.
            $prendasPaso2Ordenadas = PrendaCot::where('cotizacion_id', $cotizacionId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->values();

            $prendasCotPorNombre = [];

            if (!empty($tecnicasAgregadas)) {
                foreach ($tecnicasAgregadas as $tecnicaIndex => $tecnicaData) {
                    $tipoLogoId = $tecnicaData['tipo_logo']['id'] ?? null;
                    if (!$tipoLogoId) {
                        continue;
                    }

                    if (empty($tecnicaData['prendas']) || !is_array($tecnicaData['prendas'])) {
                        continue;
                    }

                    $prendasKeys = [];

                    foreach ($tecnicaData['prendas'] as $prendaIndex => $prendaData) {
                        $nombrePrendaCompleto = $prendaData['nombre_prenda'] ?? '';
                        if (empty($nombrePrendaCompleto) || trim($nombrePrendaCompleto) === '') {
                            continue;
                        }

                        $prendaPaso2Index = $prendaData['prenda_paso2_index'] ?? null;
                        if (is_string($prendaPaso2Index)) {
                            $prendaPaso2Index = is_numeric($prendaPaso2Index) ? (int) $prendaPaso2Index : null;
                        }
                        if (!is_int($prendaPaso2Index)) {
                            $prendaPaso2Index = null;
                        }

                        $prendaKey = md5(
                            json_encode([
                                'nombre' => $nombrePrendaCompleto,
                                'prenda_paso2_index' => $prendaPaso2Index,
                                'ubicaciones' => $prendaData['ubicaciones'] ?? [],
                                'talla_cantidad' => $prendaData['talla_cantidad'] ?? [],
                            ])
                        );

                        if (in_array($prendaKey, $prendasKeys, true)) {
                            continue;
                        }

                        $prendasKeys[] = $prendaKey;

                        $prendaCot = null;
                        if ($prendaPaso2Index !== null) {
                            $prendaCot = $prendasPaso2Ordenadas->get($prendaPaso2Index);
                        }

                        if ($prendaCot) {
                            $nombreKey = 'paso2|' . ((string) $prendaCot->id);
                            $prendasCotPorNombre[$nombreKey] = $prendaCot;
                        } else {
                            $nombreKey = trim(mb_strtoupper($nombrePrendaCompleto));
                            $nombreProductoNormalizado = $nombreKey;

                            if (!isset($prendasCotPorNombre[$nombreKey])) {
                                $prendaCotExistentePaso3 = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                                    ->whereHas('prendaCot', function ($q) use ($nombreProductoNormalizado) {
                                        $q->whereRaw('LOWER(nombre_producto) = ?', [strtolower($nombreProductoNormalizado)]);
                                    })
                                    ->orderByDesc('id')
                                    ->first();

                                if ($prendaCotExistentePaso3 && $prendaCotExistentePaso3->prendaCot) {
                                    $prendasCotPorNombre[$nombreKey] = $prendaCotExistentePaso3->prendaCot;
                                } else {
                                    $prendasCotPorNombre[$nombreKey] = PrendaCot::create([
                                        'cotizacion_id' => $cotizacionId,
                                        'nombre_producto' => $nombreProductoNormalizado,
                                        'descripcion' => $prendaData['descripcion'] ?? ($prendaData['observaciones'] ?? ''),
                                        'texto_personalizado_tallas' => $prendaData['texto_personalizado_tallas'] ?? null,
                                        'cantidad' => $prendaData['cantidad'] ?? 1,
                                        'prenda_bodega' => true,
                                    ]);
                                }
                            }

                            $prendaCot = $prendasCotPorNombre[$nombreKey];
                        }

                        $ubicacionesJson = $jsonEstable($prendaData['ubicaciones'] ?? []);
                        $tallaCantidadJson = $jsonEstable($prendaData['talla_cantidad'] ?? []);

                        $firmasEsperadas[$firmaTecnicaPrenda((int) $tipoLogoId, (int) $prendaCot->id, (string) $ubicacionesJson, (string) $tallaCantidadJson)] = true;
                        $prendaCotIdsEsperadas[(int) $prendaCot->id] = true;

                        $logoCotizacionTecnicaPrendaExistente = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                            ->where('tipo_logo_id', $tipoLogoId)
                            ->where('prenda_cot_id', $prendaCot->id)
                            ->where('ubicaciones', $ubicacionesJson)
                            ->where('talla_cantidad', $tallaCantidadJson)
                            ->first();

                        if ($logoCotizacionTecnicaPrendaExistente) {
                            $logoCotizacionTecnicaPrenda = $logoCotizacionTecnicaPrendaExistente;
                        } else {
                            $logoCotizacionTecnicaPrenda = LogoCotizacionTecnicaPrenda::create([
                                'logo_cotizacion_id' => $logoCotizacion->id,
                                'tipo_logo_id' => $tipoLogoId,
                                'prenda_cot_id' => $prendaCot->id,
                                'observaciones' => $prendaData['observaciones'] ?? '',
                                'ubicaciones' => $ubicacionesJson,
                                'talla_cantidad' => $tallaCantidadJson,
                                'variaciones_prenda' => json_encode($prendaData['variaciones_prenda'] ?? []),
                                'grupo_combinado' => $prendaIndex,
                            ]);
                        }

                        if (isset($prendaData['imagenes']) && is_array($prendaData['imagenes']) && !empty($prendaData['imagenes'])) {
                            $ordenFoto = 1;
                            foreach ($prendaData['imagenes'] as $imagen) {
                                if ($ordenFoto > 5) {
                                    break;
                                }

                                $rutaGuardar = null;
                                if (isset($imagen['tipo']) && $imagen['tipo'] === 'paso2' && isset($imagen['ruta'])) {
                                    $rutaGuardar = $imagen['ruta'];
                                }

                                if ($rutaGuardar) {
                                    try {
                                        $rutaNormalizada = is_string($rutaGuardar) ? trim($rutaGuardar) : $rutaGuardar;
                                        if (is_string($rutaNormalizada)) {
                                            if (strpos($rutaNormalizada, 'http') === 0 && preg_match('#/storage/(.+)$#', $rutaNormalizada, $m)) {
                                                $rutaNormalizada = $m[1];
                                            }
                                            if (strpos($rutaNormalizada, '/storage/') === 0) {
                                                $rutaNormalizada = substr($rutaNormalizada, 9);
                                            }
                                            if (strpos($rutaNormalizada, 'storage/') === 0) {
                                                $rutaNormalizada = substr($rutaNormalizada, 8);
                                            }
                                            $rutaNormalizada = ltrim($rutaNormalizada, '/');
                                        }

                                        $rutasAComparar = array_values(array_unique(array_filter([
                                            $rutaNormalizada,
                                            is_string($rutaNormalizada) ? ('/' . ltrim($rutaNormalizada, '/')) : null,
                                            is_string($rutaNormalizada) ? ('/storage/' . ltrim($rutaNormalizada, '/')) : null,
                                        ], fn($v) => is_string($v) && trim($v) !== '')));

                                        $yaExisteFoto = $logoCotizacionTecnicaPrenda->fotos()
                                            ->where(function ($q) use ($rutasAComparar) {
                                                $q->whereIn('ruta_webp', $rutasAComparar)
                                                    ->orWhereIn('ruta_original', $rutasAComparar)
                                                    ->orWhereIn('ruta_miniatura', $rutasAComparar);
                                            })
                                            ->exists();

                                        if ($yaExisteFoto) {
                                            continue;
                                        }

                                        $logoCotizacionTecnicaPrenda->fotos()->create([
                                            'ruta_original' => $rutaNormalizada,
                                            'ruta_webp' => $rutaNormalizada,
                                            'ruta_miniatura' => $rutaNormalizada,
                                            'orden' => $ordenFoto,
                                        ]);

                                        $ordenFoto++;
                                    } catch (\Exception $e) {
                                        Log::error('Error al guardar foto de técnica', [
                                            'tecnica_prenda_id' => $logoCotizacionTecnicaPrenda->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }
                        }

                        // En update, sincronizar fotos existentes: eliminar las que ya no vienen en el request.
                        if ($esUpdate) {
                            $rutasConservar = [];
                            $imagenesReq = $prendaData['imagenes'] ?? [];

                            if (is_string($imagenesReq)) {
                                $imagenesReq = json_decode($imagenesReq, true) ?? [];
                            }

                            if (is_array($imagenesReq)) {
                                foreach ($imagenesReq as $imgReq) {
                                    if (!is_array($imgReq)) {
                                        continue;
                                    }
                                    if (($imgReq['tipo'] ?? null) !== 'paso2') {
                                        continue;
                                    }
                                    $ruta = $imgReq['ruta'] ?? null;
                                    if (!is_string($ruta) || trim($ruta) === '') {
                                        continue;
                                    }

                                    $ruta = trim($ruta);
                                    $rutasConservar[] = $ruta;

                                    // Normalizaciones adicionales para comparar contra DB
                                    if (strpos($ruta, 'http') === 0 && preg_match('#/storage/(.+)$#', $ruta, $m)) {
                                        $rutasConservar[] = '/storage/' . $m[1];
                                        $rutasConservar[] = $m[1];
                                    }
                                    if (strpos($ruta, '/storage/') === 0) {
                                        $rutasConservar[] = substr($ruta, 9);
                                    }
                                    if (strpos($ruta, 'storage/') === 0) {
                                        $rutasConservar[] = substr($ruta, 8);
                                    }
                                }
                            }

                            $rutasConservar = array_values(array_unique($rutasConservar));

                            $fotosExistentes = $logoCotizacionTecnicaPrenda->fotos()->get();
                            foreach ($fotosExistentes as $fotoExistente) {
                                $rutasFoto = array_filter([
                                    $fotoExistente->ruta_original,
                                    $fotoExistente->ruta_webp,
                                    $fotoExistente->ruta_miniatura,
                                ], fn($v) => is_string($v) && trim($v) !== '');

                                $estaEnLista = false;
                                foreach ($rutasFoto as $r) {
                                    if (in_array($r, $rutasConservar, true)) {
                                        $estaEnLista = true;
                                        break;
                                    }
                                    if (strpos($r, '/storage/') === 0 && in_array(substr($r, 9), $rutasConservar, true)) {
                                        $estaEnLista = true;
                                        break;
                                    }
                                }

                                if ($estaEnLista) {
                                    continue;
                                }

                                try {
                                    $rutaABorrar = $fotoExistente->ruta_webp ?: ($fotoExistente->ruta_original ?: $fotoExistente->ruta_miniatura);
                                    if (is_string($rutaABorrar) && trim($rutaABorrar) !== '') {
                                        $this->eliminarFotoInmediatamenteService->ejecutar($rutaABorrar, null);
                                    }
                                } catch (\Throwable $e) {
                                    Log::warning('Error borrando archivo de foto de técnica', [
                                        'foto_id' => $fotoExistente->id,
                                        'tecnica_prenda_id' => $logoCotizacionTecnicaPrenda->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }

                                try {
                                    $fotoExistente->delete();
                                } catch (\Throwable $e) {
                                    Log::warning('Error borrando registro de foto de técnica', [
                                        'foto_id' => $fotoExistente->id,
                                        'tecnica_prenda_id' => $logoCotizacionTecnicaPrenda->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            // Sync (update): eliminar registros antiguos que ya no vienen en el request
            // Ejemplo típico: el usuario elimina una ubicación => cambia el JSON de ubicaciones,
            // entonces el registro viejo queda huérfano y debe eliminarse.
            // También cubre el caso donde tecnicas_agregadas viene vacío: se debe eliminar TODO lo existente.
            if ($esUpdate) {
                $existentes = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)->get();

                foreach ($existentes as $row) {
                    $tipoId = (int) $row->tipo_logo_id;
                    $prendaId = (int) $row->prenda_cot_id;

                    $ubicJsonRow = $jsonEstable($row->ubicaciones ?? []);
                    $tallaJsonRow = $jsonEstable($row->talla_cantidad ?? []);

                    $firma = $firmaTecnicaPrenda($tipoId, $prendaId, (string) $ubicJsonRow, (string) $tallaJsonRow);
                    if (isset($firmasEsperadas[$firma])) {
                        continue;
                    }

                    $prendaFueEliminadaDelPaso3 = !isset($prendaCotIdsEsperadas[$prendaId]);

                    try {
                        $fotos = $row->fotos()->get();
                        foreach ($fotos as $foto) {
                            // Si se eliminó una prenda completa del Paso 3, sí borrar físicamente las imágenes.
                            // Si solo cambió ubicaciones/tallas (la prenda sigue existiendo), NO borrar archivos.
                            if ($prendaFueEliminadaDelPaso3) {
                                $rutaABorrar = $foto->ruta_webp ?: ($foto->ruta_original ?: $foto->ruta_miniatura);
                                if (is_string($rutaABorrar) && trim($rutaABorrar) !== '') {
                                    try {
                                        $this->eliminarFotoInmediatamenteService->ejecutar($rutaABorrar, null);
                                    } catch (\Throwable $e) {
                                        Log::warning('Sync técnica: error borrando archivo de foto', [
                                            'foto_id' => $foto->id,
                                            'tecnica_prenda_id' => $row->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }
                            try {
                                $foto->delete();
                            } catch (\Throwable $e) {
                                Log::warning('Sync técnica: error borrando registro de foto', [
                                    'foto_id' => $foto->id,
                                    'tecnica_prenda_id' => $row->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Sync técnica: error listando/borrando fotos', [
                            'tecnica_prenda_id' => $row->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    try {
                        $row->delete();
                    } catch (\Throwable $e) {
                        Log::warning('Sync técnica: error borrando tecnica_prenda', [
                            'tecnica_prenda_id' => $row->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Si una prenda completa del Paso 3 fue eliminada (ya no está referenciada),
                // borrar también el registro en prendas_cot si quedó sin técnicas asociadas.
                if (!empty($prendaCotIdsAntes)) {
                    $prendaCotIdsDespues = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                        ->pluck('prenda_cot_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $idsEliminados = array_values(array_diff($prendaCotIdsAntes, $prendaCotIdsDespues));
                    foreach ($idsEliminados as $prendaCotId) {
                        $prendaCotId = (int) $prendaCotId;
                        if ($prendaCotId <= 0) {
                            continue;
                        }

                        $prenda = PrendaCot::find($prendaCotId);
                        if (!$prenda) {
                            continue;
                        }

                        // Si la prenda viene del Paso 2, NO se debe eliminar de prendas_cot.
                        // Solo se eliminaron sus técnicas/fotos del Paso 3 (arriba) y eso es suficiente.
                        // Heurística:
                        // - Paso 2 normalmente crea prendas con relaciones (tallas/telas/variantes/fotos) y prenda_bodega != true.
                        // - Paso 3 (logo) crea una prenda mínima (prenda_bodega=true) sin esas relaciones.
                        if (!$prenda->prenda_bodega) {
                            continue;
                        }

                        $tieneRelacionesDePaso2 = $prenda->tallas()->exists()
                            || $prenda->variantes()->exists()
                            || $prenda->telas()->exists()
                            || $prenda->fotos()->exists();

                        if ($tieneRelacionesDePaso2) {
                            continue;
                        }

                        // Seguridad: solo borrar si ya no tiene técnicas asociadas
                        if ($prenda->logoCotizacionesTecnicas()->exists()) {
                            continue;
                        }

                        try {
                            // Borrar relaciones típicas (por consistencia), aunque normalmente Paso 3 no guarda aquí.
                            $prenda->fotos()->delete();
                            $prenda->telaFotos()->delete();
                            foreach ($prenda->telas as $tela) {
                                $tela->fotos()->delete();
                            }
                            $prenda->telas()->delete();
                            $prenda->tallas()->delete();
                            $prenda->variantes()->delete();
                        } catch (\Throwable $e) {
                            Log::warning('Sync técnica: error borrando relaciones de prenda Paso 3', [
                                'prenda_cot_id' => $prendaCotId,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        try {
                            $prenda->delete();
                        } catch (\Throwable $e) {
                            Log::warning('Sync técnica: error borrando prenda_cot Paso 3', [
                                'prenda_cot_id' => $prendaCotId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                // Purga adicional:
                // si quedó una prenda creada por Paso 3 (mínima) sin ninguna técnica asociada,
                // debe eliminarse incluso si no apareció en $prendaCotIdsAntes (ej: prenda huérfana por flujos previos).
                $prendaCotIdsEsperadasKeys = array_keys($prendaCotIdsEsperadas);
                $prendasCandidatasP3 = PrendaCot::where('cotizacion_id', $cotizacionId)
                    ->where('prenda_bodega', true)
                    ->whereNotIn('id', $prendaCotIdsEsperadasKeys)
                    ->get();

                foreach ($prendasCandidatasP3 as $prendaCandidata) {
                    try {
                        if ($prendaCandidata->logoCotizacionesTecnicas()->exists()) {
                            continue;
                        }

                        $tieneRelacionesDePaso2 = $prendaCandidata->tallas()->exists()
                            || $prendaCandidata->variantes()->exists()
                            || $prendaCandidata->telas()->exists()
                            || $prendaCandidata->fotos()->exists();

                        if ($tieneRelacionesDePaso2) {
                            continue;
                        }

                        $prendaCandidata->delete();
                    } catch (\Throwable $e) {
                        Log::warning('Sync técnica: error purgando prenda_cot huérfana de Paso 3', [
                            'prenda_cot_id' => $prendaCandidata->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $imagenesP3Archivos = [];

            $imagenesP3Archivos = $dto->imagenesPaso3Archivos;

            if (count($imagenesP3Archivos) <= 0) {
                return;
            }

            $logoPaso3PathCache = [];

            foreach ($imagenesP3Archivos as $fieldName => $archivo) {
                if (!($archivo instanceof \Illuminate\Http\UploadedFile)) {
                    continue;
                }

                if (preg_match('/^logo\[imagenes_paso3\]\[(\d+)\]\[(\d+)\]\[(\d+)\]$/', $fieldName, $matches)) {
                    $tecnicaIndex = (int) $matches[1];
                    $prendaIndex = (int) $matches[2];

                    if (!isset($tecnicasAgregadas[$tecnicaIndex]) || !isset($tecnicasAgregadas[$tecnicaIndex]['prendas'][$prendaIndex])) {
                        continue;
                    }

                    $prendaData = $tecnicasAgregadas[$tecnicaIndex]['prendas'][$prendaIndex];

                    $nombrePrendaCompleto = $prendaData['nombre_prenda'] ?? '';
                    $nombreKeyImg = trim(mb_strtoupper($nombrePrendaCompleto));

                    $prendaPaso2Index = $prendaData['prenda_paso2_index'] ?? null;
                    if (is_string($prendaPaso2Index)) {
                        $prendaPaso2Index = is_numeric($prendaPaso2Index) ? (int) $prendaPaso2Index : null;
                    }
                    if (!is_int($prendaPaso2Index)) {
                        $prendaPaso2Index = null;
                    }

                    $prendaCot = null;

                    if ($prendaPaso2Index !== null) {
                        $prendaCot = $prendasPaso2Ordenadas->get($prendaPaso2Index);
                    }

                    if (!$prendaCot && isset($prendasCotPorNombre[$nombreKeyImg])) {
                        $prendaCot = $prendasCotPorNombre[$nombreKeyImg];
                    }

                    if (!$prendaCot && $nombrePrendaCompleto !== '') {
                        $prendaCot = PrendaCot::where('cotizacion_id', $cotizacionId)
                            ->whereRaw('LOWER(nombre_producto) = ?', [strtolower($nombrePrendaCompleto)])
                            ->orderByDesc('id')
                            ->first();
                    }

                    if (!$prendaCot) {
                        continue;
                    }

                    $tipoLogoId = $tecnicasAgregadas[$tecnicaIndex]['tipo_logo']['id'];
                    $ubicacionesJsonImg = $jsonEstable($prendaData['ubicaciones'] ?? []);
                    $tallaCantidadJsonImg = $jsonEstable($prendaData['talla_cantidad'] ?? []);

                    $logoCotizacionTecnicaPrenda = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                        ->where('tipo_logo_id', $tipoLogoId)
                        ->where('prenda_cot_id', $prendaCot->id)
                        ->where('ubicaciones', $ubicacionesJsonImg)
                        ->where('talla_cantidad', $tallaCantidadJsonImg)
                        ->first();

                    if (!$logoCotizacionTecnicaPrenda) {
                        continue;
                    }

                    $ordenFoto = $logoCotizacionTecnicaPrenda->fotos()->count() + 1;
                    if ($ordenFoto > 5) {
                        continue;
                    }

                    $cacheKey = null;
                    try {
                        $realPath = $archivo->getRealPath();
                        if ($realPath && is_string($realPath) && file_exists($realPath)) {
                            $cacheKey = hash_file('sha256', $realPath);
                        }
                    } catch (\Throwable $e) {
                        $cacheKey = null;
                    }

                    if ($cacheKey && isset($logoPaso3PathCache[$cacheKey])) {
                        $path = $logoPaso3PathCache[$cacheKey];
                    } else {
                        $path = $this->procesarImagenesService->procesarImagenLogo($archivo, $cotizacionId);
                        if ($cacheKey) {
                            $logoPaso3PathCache[$cacheKey] = $path;
                        }
                    }

                    $logoCotizacionTecnicaPrenda->fotos()->create([
                        'ruta_original' => $path,
                        'ruta_webp' => $path,
                        'ruta_miniatura' => $path,
                        'orden' => $ordenFoto,
                    ]);
                } elseif (preg_match('/^logo\[imagenes_paso3\]\[(\d+)\]$/', $fieldName)) {
                    $cacheKey = null;
                    try {
                        $realPath = $archivo->getRealPath();
                        if ($realPath && is_string($realPath) && file_exists($realPath)) {
                            $cacheKey = hash_file('sha256', $realPath);
                        }
                    } catch (\Throwable $e) {
                        $cacheKey = null;
                    }

                    if ($cacheKey && isset($logoPaso3PathCache[$cacheKey])) {
                        $path = $logoPaso3PathCache[$cacheKey];
                    } else {
                        $path = $this->procesarImagenesService->procesarImagenLogo($archivo, $cotizacionId);
                        if ($cacheKey) {
                            $logoPaso3PathCache[$cacheKey] = $path;
                        }
                    }

                    $logoCotizacionTecnicaPrendaTarget = null;

                    $queryTecnicaPrenda = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                        ->orderByDesc('id');

                    $countTecnicaPrenda = (clone $queryTecnicaPrenda)->count();

                    if ($countTecnicaPrenda === 1) {
                        $logoCotizacionTecnicaPrendaTarget = $queryTecnicaPrenda->first();
                    }

                    if (!$logoCotizacionTecnicaPrendaTarget && isset($tecnicasAgregadas[0]) && isset($tecnicasAgregadas[0]['prendas'][0])) {
                        $prendaData0 = $tecnicasAgregadas[0]['prendas'][0];
                        $tipoLogoId0 = $tecnicasAgregadas[0]['tipo_logo']['id'] ?? null;

                        if ($tipoLogoId0) {
                            $nombrePrendaBase0 = explode(' - ', $prendaData0['nombre_prenda'])[0];
                            $nombreKeyImg0 = trim(mb_strtoupper($nombrePrendaBase0));

                            $prendaCot0 = null;
                            if (isset($prendasCotPorNombre[$nombreKeyImg0])) {
                                $prendaCot0 = $prendasCotPorNombre[$nombreKeyImg0];
                            } else {
                                $prendaCot0 = PrendaCot::where('cotizacion_id', $cotizacionId)
                                    ->whereRaw('LOWER(nombre_producto) = ?', [strtolower($nombrePrendaBase0)])
                                    ->where('prenda_bodega', true)
                                    ->orderByDesc('id')
                                    ->first();
                            }

                            if ($prendaCot0) {
                                $logoCotizacionTecnicaPrendaTarget = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                                    ->where('tipo_logo_id', $tipoLogoId0)
                                    ->where('prenda_cot_id', $prendaCot0->id)
                                    ->first();
                            }
                        }
                    }

                    if (!$logoCotizacionTecnicaPrendaTarget) {
                        continue;
                    }

                    $ordenFoto = $logoCotizacionTecnicaPrendaTarget->fotos()->count() + 1;

                    $logoCotizacionTecnicaPrendaTarget->fotos()->create([
                        'ruta_original' => $path,
                        'ruta_webp' => $path,
                        'ruta_miniatura' => $path,
                        'orden' => $ordenFoto,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('ProcesarLogoTecnicasCotizacionRequestService: Error', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function flatearArchivos(array $archivos, array &$resultado, string $prefijo = ''): void
    {
        foreach ($archivos as $key => $valor) {
            $nuevaLlave = $prefijo . '[' . $key . ']';

            if ($valor instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $resultado[$nuevaLlave] = $valor;
            } elseif (is_array($valor)) {
                $this->flatearArchivos($valor, $resultado, $nuevaLlave);
            }
        }
    }
}
