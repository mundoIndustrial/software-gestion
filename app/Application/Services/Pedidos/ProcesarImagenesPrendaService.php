<?php

namespace App\Application\Services\Pedidos;

use App\Domain\Pedidos\Services\PrendaFotoService;
use App\Domain\Pedidos\Services\TelaFotoService;
use App\Domain\Pedidos\Services\ProcesoFotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ProcesarImagenesPrendaService — Application Service
 *
 * Encapsula todo el procesamiento de archivos subidos (imagenes, telas, colores,
 * procesos) para las operaciones de prenda completa. El controlador delega aquí
 * y recibe un array de resultados listos para pasarlos a los DTOs.
 *
 * SRP: este servicio SOLO transforma archivos HTTP en rutas almacenadas.
 */
class ProcesarImagenesPrendaService
{
    public function __construct(
        private PrendaFotoService $prendaFotoService,
        private TelaFotoService $telaFotoService,
        private ProcesoFotoService $procesoFotoService,
    ) {}

    /**
     * Procesa todos los archivos subidos para CREAR una prenda.
     *
     * @param  mixed $asignacionesColoresInput  JSON string o array con asignaciones_colores
     * @return array{
     *   imagenes_guardadas: string[],
     *   imagenes_existentes: array,
     *   fotos_color_meta: array,
     *   asignaciones_colores: array|null,
     *   fotos_proceso_nuevo: array,
     *   fotos_tela_rutas: array,
     * }
     */
    public function procesarParaCrear(Request $request, int $pedidoId, mixed $asignacionesColoresInput = null): array
    {
        $fotosColorMeta = $this->procesarFotosColorListaPlana($request, $pedidoId);

        return [
            'imagenes_guardadas'   => $this->procesarImagenesPrenda($request, $pedidoId),
            'imagenes_existentes'  => $this->decodificarImagenesExistentes($request),
            'fotos_color_meta'     => $fotosColorMeta,
            'asignaciones_colores' => $this->inyectarRutasEnAsignaciones($asignacionesColoresInput, $fotosColorMeta),
            'fotos_proceso_nuevo'  => $this->procesarFotosProcesoNuevo($request, $pedidoId),
            'fotos_tela_rutas'     => $this->procesarFotosTelaSimple($request, $pedidoId),
        ];
    }

    /**
     * Procesa todos los archivos subidos para ACTUALIZAR una prenda.
     *
     * @return array{
     *   imagenes_guardadas: array[],
     *   imagenes_existentes: array,
     *   imagenes_a_eliminar: array,
     *   fotos_telas_procesadas: array,
     *   fotos_proceso_nuevo: array,
     *   fotos_proceso_tallas_nuevo: array,
     *   fotos_color_procesadas: array,
     * }
     */
    public function procesarParaActualizar(Request $request, int $pedidoId): array
    {
        return [
            'imagenes_guardadas'         => $this->procesarImagenesPrendaCompletas($request, $pedidoId),
            'imagenes_existentes'        => $this->decodificarImagenesExistentes($request),
            'imagenes_a_eliminar'        => $this->decodificarImagenesAEliminar($request),
            'fotos_telas_procesadas'     => $this->procesarFotosTelasCompletas($request, $pedidoId),
            'fotos_proceso_nuevo'        => $this->procesarFotosProcesoNuevo($request, $pedidoId),
            'fotos_proceso_tallas_nuevo' => $this->procesarFotosProcesoTallasNuevo($request, $pedidoId),
            'fotos_color_procesadas'     => $this->procesarFotosColorIndexadas($request, $pedidoId),
        ];
    }

    // ─── Private helpers ────────────────────────────────────────────────────────

    /** Para crear: devuelve solo la ruta webp por imagen (string[]) */
    private function procesarImagenesPrenda(Request $request, int $pedidoId): array
    {
        $result = [];
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                $rutas    = $this->prendaFotoService->procesarFoto($imagen, $pedidoId);
                $result[] = $rutas['ruta_webp'] ?? $rutas['ruta_original'];
            }
        }
        return $result;
    }

    /** Para actualizar: devuelve array de rutas completas por imagen */
    private function procesarImagenesPrendaCompletas(Request $request, int $pedidoId): array
    {
        $result = [];
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                $result[] = $this->prendaFotoService->procesarFoto($imagen, $pedidoId);
            }
        }
        return $result;
    }

    private function decodificarImagenesExistentes(Request $request): array
    {
        if (!$request->input('imagenes_existentes')) {
            return [];
        }
        try {
            return json_decode($request->input('imagenes_existentes'), true) ?? [];
        } catch (\Exception $e) {
            Log::warning('[ProcesarImagenesPrendaService] Error decodificando imagenes_existentes', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function decodificarImagenesAEliminar(Request $request): array
    {
        if (!$request->input('imagenes_a_eliminar')) {
            return [];
        }
        try {
            $input  = $request->input('imagenes_a_eliminar');
            $result = is_array($input) ? $input : (json_decode($input, true) ?? []);
            $result = is_array($result) ? $result : [];
            Log::info('[ProcesarImagenesPrendaService] Imágenes a eliminar', [
                'cantidad' => count($result),
                'ids'      => $result,
            ]);
            return $result;
        } catch (\Exception $e) {
            Log::warning('[ProcesarImagenesPrendaService] Error procesando imagenes_a_eliminar', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** Para crear: retorna lista plana con ruta + meta de color */
    private function procesarFotosColorListaPlana(Request $request, int $pedidoId): array
    {
        $result       = [];
        $fotosFiles   = $request->file('fotos_color') ?? [];
        $fotosMetaAll = $request->input('fotos_color_meta') ?? [];

        foreach ($fotosFiles as $indice => $archivo) {
            if (!$archivo || !$archivo->isValid()) continue;
            try {
                $rutas   = $this->telaFotoService->procesarFoto($archivo, $pedidoId, true);
                $metaRaw = $fotosMetaAll[$indice] ?? null;
                $meta    = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                $result[] = [
                    'ruta_webp'    => $rutas['ruta_webp'] ?? $rutas['ruta_original'],
                    'clave'        => $meta['clave'] ?? '',
                    'color_nombre' => $meta['color_nombre'] ?? '',
                ];
                Log::info('[ProcesarImagenesPrendaService] Imagen de color (crear) procesada', [
                    'indice'      => $indice,
                    'ruta_webp'   => end($result)['ruta_webp'],
                    'clave'       => end($result)['clave'],
                    'color'       => end($result)['color_nombre'],
                ]);
            } catch (\Exception $e) {
                Log::warning('[ProcesarImagenesPrendaService] Error procesando imagen color (crear)', [
                    'indice' => $indice, 'error' => $e->getMessage(),
                ]);
            }
        }
        return $result;
    }

    /** Para actualizar: retorna array indexado por índice con ruta + meta de color */
    private function procesarFotosColorIndexadas(Request $request, int $pedidoId): array
    {
        $result       = [];
        $fotosFiles   = $request->file('fotos_color') ?? [];
        $fotosMetaAll = $request->input('fotos_color_meta') ?? [];

        foreach ($fotosFiles as $indice => $archivo) {
            if (!$archivo || !$archivo->isValid()) continue;
            try {
                $rutas   = $this->telaFotoService->procesarFoto($archivo, $pedidoId, true);
                $metaRaw = $fotosMetaAll[$indice] ?? null;
                $meta    = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                $result[$indice] = [
                    'ruta_webp'    => $rutas['ruta_webp'] ?? $rutas['ruta_original'],
                    'clave'        => $meta['clave'] ?? '',
                    'color_nombre' => $meta['color_nombre'] ?? '',
                ];
                Log::info('[ProcesarImagenesPrendaService] Imagen de color (actualizar) procesada', [
                    'indice'      => $indice,
                    'ruta_webp'   => $result[$indice]['ruta_webp'],
                    'clave'       => $result[$indice]['clave'],
                    'color_nombre'=> $result[$indice]['color_nombre'],
                ]);
            } catch (\Exception $e) {
                Log::warning('[ProcesarImagenesPrendaService] Error procesando imagen de color', [
                    'indice' => $indice, 'error' => $e->getMessage(),
                ]);
            }
        }
        if (!empty($result)) {
            Log::info('[ProcesarImagenesPrendaService] Total imágenes de color procesadas', ['cantidad' => count($result)]);
        }
        return $result;
    }

    /** Para crear: fotos_tela[] indexadas por índice */
    private function procesarFotosTelaSimple(Request $request, int $pedidoId): array
    {
        $result = [];
        foreach ($request->file('fotos_tela') ?? [] as $indice => $archivo) {
            if (!$archivo || !$archivo->isValid()) continue;
            try {
                $rutas           = $this->telaFotoService->procesarFoto($archivo, $pedidoId, true);
                $result[$indice] = $rutas;
                Log::info('[ProcesarImagenesPrendaService] Imagen de tela procesada (crear)', [
                    'indice'   => $indice,
                    'ruta_webp'=> $rutas['ruta_webp'],
                ]);
            } catch (\Exception $e) {
                Log::warning('[ProcesarImagenesPrendaService] Error procesando imagen de tela (crear)', [
                    'indice' => $indice, 'error' => $e->getMessage(),
                ]);
            }
        }
        return $result;
    }

    /**
     * Para actualizar: telas con múltiples patrones de upload.
     * Patrón 1: fotos_tela[N]  — clave numérica explícita
     * Patrón 2: fotos_tela[]   — fallback si patrón 1 no produjo resultados
     */
    private function procesarFotosTelasCompletas(Request $request, int $pedidoId): array
    {
        $result   = [];
        $allFiles = $request->files->all();

        // Patrón fotos_tela[N]
        foreach ($allFiles as $key => $value) {
            if (strpos($key, 'fotos_tela[') !== 0 || strpos($key, ']') === false) continue;
            if (!$value || !$value->isValid()) continue;
            try {
                $rutas = $this->telaFotoService->procesarFoto($value, $pedidoId);
                preg_match('/fotos_tela\[(\d+)\]/', $key, $matches);
                $indice          = isset($matches[1]) ? (int)$matches[1] : count($result);
                $result[$indice] = $rutas;
                Log::info('[ProcesarImagenesPrendaService] Imagen de tela procesada (fotos_tela[N])', [
                    'key' => $key, 'indice' => $indice, 'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A',
                ]);
            } catch (\Exception $e) {
                Log::warning('[ProcesarImagenesPrendaService] Error procesando imagen de tela', [
                    'key' => $key, 'error' => $e->getMessage(),
                ]);
            }
        }

        // Patrón fotos_tela[] — fallback
        if ($request->hasFile('fotos_tela') && empty($result)) {
            $archivos = $request->file('fotos_tela');
            if (!is_array($archivos)) $archivos = [$archivos];
            foreach ($archivos as $indice => $archivo) {
                if (!$archivo || !$archivo->isValid()) continue;
                try {
                    $rutas           = $this->telaFotoService->procesarFoto($archivo, $pedidoId);
                    $result[$indice] = $rutas;
                    Log::info('[ProcesarImagenesPrendaService] Imagen de tela procesada (fotos_tela[])', [
                        'indice' => $indice, 'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[ProcesarImagenesPrendaService] Error procesando imagen de tela (fotos_tela[])', [
                        'indice' => $indice, 'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    /** fotosProcesoNuevo_0[], fotosProcesoNuevo_1[], ... (compartido crear y actualizar) */
    private function procesarFotosProcesoNuevo(Request $request, int $pedidoId): array
    {
        $result = [];
        foreach ($request->allFiles() as $key => $files) {
            if (strpos($key, 'fotosProcesoNuevo_') !== 0) continue;
            preg_match('/fotosProcesoNuevo_(\d+)/', $key, $matches);
            if (!isset($matches[1])) continue;
            $indice   = (int)$matches[1];
            $archivos = is_array($files) ? $files : [$files];
            $result[$indice] ??= [];
            foreach ($archivos as $archivo) {
                if (!$archivo || !$archivo->isValid()) continue;
                try {
                    $rutas             = $this->procesoFotoService->procesarFoto($archivo, $pedidoId);
                    $result[$indice][] = $rutas;
                    Log::info('[ProcesarImagenesPrendaService] Imagen de proceso nuevo procesada', [
                        'key' => $key, 'indice' => $indice, 'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[ProcesarImagenesPrendaService] Error procesando imagen de proceso nuevo', [
                        'key' => $key, 'indice' => $indice, 'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        return $result;
    }

    /** fotosProcesoTallasNuevo_{idx}_{genero}_{talla}[] */
    private function procesarFotosProcesoTallasNuevo(Request $request, int $pedidoId): array
    {
        $result = [];
        foreach ($request->allFiles() as $key => $files) {
            if (strpos($key, 'fotosProcesoTallasNuevo_') !== 0) continue;
            preg_match('/fotosProcesoTallasNuevo_(\d+)_([a-zA-Z]+)_(.+)/', $key, $matches);
            if (!isset($matches[1], $matches[2], $matches[3])) continue;
            $procesoIdx = (int)$matches[1];
            $genero     = strtolower($matches[2]);
            $talla      = $matches[3];
            $keyTalla   = "{$procesoIdx}_{$genero}_{$talla}";
            $archivos   = is_array($files) ? $files : [$files];
            $result[$keyTalla] ??= [];
            foreach ($archivos as $archivo) {
                if (!$archivo || !$archivo->isValid()) continue;
                try {
                    $rutas = $this->procesoFotoService->procesarFoto($archivo, $pedidoId);
                    $result[$keyTalla][] = [
                        'ruta_original' => $rutas['ruta_original'] ?? null,
                        'ruta_webp'     => $rutas['ruta_webp'] ?? null,
                        'proceso_idx'   => $procesoIdx,
                        'genero'        => $genero,
                        'talla'         => $talla,
                    ];
                    Log::info('[ProcesarImagenesPrendaService] Imagen de proceso por talla procesada', [
                        'keyTalla' => $keyTalla, 'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[ProcesarImagenesPrendaService] Error procesando imagen de proceso por talla', [
                        'keyTalla' => $keyTalla, 'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        return $result;
    }

    /**
     * Inyecta las rutas de imagen de color en la estructura asignaciones_colores.
     * Retorna la estructura actualizada, o null/array vacio si no había nada.
     */
    private function inyectarRutasEnAsignaciones(mixed $asignacionesInput, array $fotosColorMeta): ?array
    {
        $asignaciones = is_string($asignacionesInput)
            ? (json_decode($asignacionesInput, true) ?? null)
            : $asignacionesInput;

        if (empty($fotosColorMeta) || !is_array($asignaciones)) {
            return $asignaciones;
        }

        foreach ($fotosColorMeta as $fotoMeta) {
            $clave       = $fotoMeta['clave'];
            $colorNombre = strtoupper($fotoMeta['color_nombre']);
            if (empty($asignaciones[$clave]['colores'])) continue;
            foreach ($asignaciones[$clave]['colores'] as &$colorItem) {
                if (strtoupper($colorItem['nombre'] ?? '') === $colorNombre) {
                    $colorItem['imagen_ruta'] = $fotoMeta['ruta_webp'];
                    break;
                }
            }
            unset($colorItem);
        }
        return $asignaciones;
    }
}
