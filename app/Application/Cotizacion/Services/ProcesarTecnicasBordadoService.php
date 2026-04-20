<?php

namespace App\Application\Cotizacion\Services;

use App\Models\LogoCotizacionTecnicaPrenda;
use App\Models\LogoCotizacionTecnicaPrendaFoto;
use App\Models\PrendaCot;
use App\Services\TecnicaImagenService;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Aplicación: Procesar Técnicas y Prendas en Cotización de Bordado
 * Responsabilidades:
 * - Crear técnicas con prendas
 * - Sincronizar técnicas existentes
 * - Procesar archivos de imágenes
 * - Vincular logos compartidos
 * - Gestionar relaciones técnica-prenda-foto
 */
class ProcesarTecnicasBordadoService
{
    private TecnicaImagenService $imagenService;

    public function __construct()
    {
        $this->imagenService = new TecnicaImagenService();
    }

    /**
     * Ejecutar: Procesar técnicas nuevas
     */
    public function ejecutar(
        int $logoCotizacionId,
        int $cotizacionId,
        array $tecnicas,
        array $archivos = [],
        array $logosCompartidosMetadata = [],
    ): void {
        Log::info('Procesando técnicas nuevas', [
            'logo_cotizacion_id' => $logoCotizacionId,
            'cotizacion_id' => $cotizacionId,
            'tecnicas_count' => count($tecnicas),
        ]);

        $logosCompartidosGuardados = $this->procesarLogosCompartidos(
            $cotizacionId,
            $archivos,
            $logosCompartidosMetadata
        );

        foreach ($tecnicas as $tecnicaIdx => $tecnica) {
            $this->procesarTecnicaIndividual(
                $logoCotizacionId,
                $cotizacionId,
                $tecnicaIdx,
                $tecnica,
                $archivos,
                $logosCompartidosGuardados,
                $logosCompartidosMetadata
            );
        }
    }

    /**
     * Sincronizar: Actualizar técnicas existentes
     */
    public function sincronizar(
        int $logoCotizacionId,
        int $cotizacionId,
        array $tecnicas,
        array $archivos = [],
        array $logosCompartidosMetadata = [],
    ): void {
        Log::info('Sincronizando técnicas existentes', [
            'logo_cotizacion_id' => $logoCotizacionId,
            'tecnicas_count' => count($tecnicas),
        ]);

        $logosCompartidosGuardados = $this->procesarLogosCompartidos(
            $cotizacionId,
            $archivos,
            $logosCompartidosMetadata
        );

        // Obtener técnicas existentes
        $existentes = LogoCotizacionTecnicaPrenda::with('fotos')
            ->where('logo_cotizacion_id', $logoCotizacionId)
            ->get();

        // Extraer IDs de técnicas que vienen en la request
        $idsIncoming = $this->extraerIds($tecnicas);

        // Eliminar técnicas que ya no vienen
        $aEliminar = $existentes->filter(fn($m) => !in_array((int) $m->id, $idsIncoming, true));
        foreach ($aEliminar as $prendaTecnica) {
            $this->eliminarTecnicaPrendaYFotos($prendaTecnica);
        }

        // Actualizar técnicas existentes
        foreach ($tecnicas as $tecnicaIdx => $tecnica) {
            foreach (($tecnica['prendas'] ?? []) as $prendaIdx => $prenda) {
                $prendaTecnicaId = $prenda['id'] ?? null;

                if (!$prendaTecnicaId) {
                    $this->crearPrendaTecnica(
                        $logoCotizacionId,
                        $cotizacionId,
                        $tecnicaIdx,
                        $prendaIdx,
                        $tecnica,
                        $prenda,
                        $archivos,
                        $logosCompartidosGuardados,
                        $logosCompartidosMetadata,
                    );
                } else {
                    $model = $existentes->firstWhere('id', (int) $prendaTecnicaId);
                    if ($model) {
                        $model->update([
                            'tipo_logo_id' => $tecnica['tipo_logo']['id'] ?? $model->tipo_logo_id,
                            'observaciones' => $prenda['observaciones'] ?? $model->observaciones,
                            'ubicaciones' => $prenda['ubicaciones'] ?? $model->ubicaciones,
                            'talla_cantidad' => $prenda['talla_cantidad'] ?? $model->talla_cantidad,
                            'variaciones_prenda' => $prenda['variaciones_prenda'] ?? $model->variaciones_prenda,
                        ]);

                        $this->procesarFotosDelPrenda(
                            $model->id,
                            $logoCotizacionId,
                            $cotizacionId,
                            $tecnicaIdx,
                            $prendaIdx,
                            $archivos,
                        );
                        $this->vincularLogosCompartidos(
                            $model,
                            $tecnica,
                            $logosCompartidosGuardados,
                            $logosCompartidosMetadata
                        );
                    }
                }
            }
        }

        Log::info('✓ Técnicas sincronizadas', [
            'eliminadas' => $aEliminar->count(),
        ]);
    }

    /**
     * Guardar en disco cada logo compartido (un archivo por clave) y devolver rutas por nombreCompartido.
     *
     * @param  array<string, array<string, mixed>>  $metadataPorClave
     * @return array<string, string>
     */
    private function procesarLogosCompartidos(int $cotizacionId, array $archivos, array $metadataPorClave): array
    {
        $archivoPorClave = [];
        foreach ($archivos as $fieldName => $archivo) {
            if (!is_string($fieldName)) {
                continue;
            }
            if (!preg_match('/^tecnica_\d+_logo_compartido_(.+)$/', $fieldName, $m)) {
                continue;
            }
            if (is_array($archivo)) {
                $archivo = $archivo[0] ?? null;
            }
            if (!$archivo || !is_object($archivo) || !method_exists($archivo, 'isValid') || !$archivo->isValid()) {
                continue;
            }
            $clave = $m[1];
            if (!isset($archivoPorClave[$clave])) {
                $archivoPorClave[$clave] = $archivo;
            }
        }

        $logosGuardados = [];
        foreach ($metadataPorClave as $clave => $meta) {
            if (empty($archivoPorClave[$clave])) {
                Log::warning('Logo compartido: metadata sin archivo en request', ['clave' => $clave]);
                continue;
            }
            $tecnicas = $meta['tecnicasCompartidas'] ?? [];
            $tipoNombre = is_array($tecnicas) && $tecnicas !== [] ? (string) $tecnicas[0] : 'TÉCNICA';
            try {
                $rutas = $this->imagenService->guardarImagen(
                    $archivoPorClave[$clave],
                    $cotizacionId,
                    $tipoNombre,
                    null
                );
                if (!empty($rutas['ruta_webp'])) {
                    $logosGuardados[$clave] = $rutas['ruta_webp'];
                    Log::info('✓ Logo compartido guardado (bordado)', [
                        'clave' => $clave,
                        'ruta_webp' => $rutas['ruta_webp'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error guardando logo compartido (bordado)', [
                    'clave' => $clave,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $logosGuardados;
    }

    /**
     * Procesar una técnica individual
     */
    private function procesarTecnicaIndividual(
        int $logoCotizacionId,
        int $cotizacionId,
        int $tecnicaIdx,
        array $tecnica,
        array $archivos,
        array $logosRutasPorClave,
        array $logosMetadataPorClave,
    ): void {
        Log::info("Procesando técnica [$tecnicaIdx]", [
            'tipo' => $tecnica['tipo_logo']['nombre'] ?? 'desconocida',
        ]);

        if (!isset($tecnica['tipo_logo']['id'])) {
            Log::warning("Técnica [$tecnicaIdx] sin tipo_logo válido");
            return;
        }

        foreach (($tecnica['prendas'] ?? []) as $prendaIdx => $prenda) {
            $this->crearPrendaTecnica(
                $logoCotizacionId,
                $cotizacionId,
                $tecnicaIdx,
                $prendaIdx,
                $tecnica,
                $prenda,
                $archivos,
                $logosRutasPorClave,
                $logosMetadataPorClave
            );
        }
    }

    /**
     * Crear una prenda técnica con sus fotos
     */
    private function crearPrendaTecnica(
        int $logoCotizacionId,
        int $cotizacionId,
        int $tecnicaIdx,
        int $prendaIdx,
        array $tecnica,
        array $prenda,
        array $archivos,
        array $logosRutasPorClave = [],
        array $logosMetadataPorClave = [],
    ): void {
        // Obtener o crear prenda base
        $prendaCotId = $this->obtenerOCrearPrendaCot(
            $logoCotizacionId,
            $cotizacionId,
            $prenda['nombre_prenda'] ?? 'Prenda',
            $prenda['variaciones_prenda'] ?? null,
            $tecnica['grupo_combinado'] ?? null
        );

        // Crear prenda técnica
        $prendaTecnica = LogoCotizacionTecnicaPrenda::create([
            'logo_cotizacion_id' => $logoCotizacionId,
            'prenda_cot_id' => $prendaCotId,
            'tipo_logo_id' => $tecnica['tipo_logo']['id'],
            'observaciones' => $prenda['observaciones'] ?? null,
            'ubicaciones' => $prenda['ubicaciones'] ?? null,
            'talla_cantidad' => $prenda['talla_cantidad'] ?? null,
            'variaciones_prenda' => $prenda['variaciones_prenda'] ?? null,
            'grupo_combinado' => $tecnica['grupo_combinado'] ?? null,
        ]);

        Log::info('✓ Prenda técnica creada', [
            'prenda_tecnica_id' => $prendaTecnica->id,
        ]);

        // Procesar fotos de esta prenda técnica
        $this->procesarFotosDelPrenda(
            $prendaTecnica->id,
            $logoCotizacionId,
            $cotizacionId,
            $tecnicaIdx,
            $prendaIdx,
            $archivos
        );

        $this->vincularLogosCompartidos(
            $prendaTecnica,
            $tecnica,
            $logosRutasPorClave,
            $logosMetadataPorClave
        );
    }

    /**
     * Crear filas foto que apuntan a la misma ruta cuando esta técnica participa en un logo compartido.
     *
     * @param  array<string, string>  $rutasPorClave
     * @param  array<string, array<string, mixed>>  $metadataPorClave
     */
    private function vincularLogosCompartidos(
        LogoCotizacionTecnicaPrenda $prendaTecnica,
        array $tecnica,
        array $rutasPorClave,
        array $metadataPorClave,
    ): void {
        $nombreTipo = (string) ($tecnica['tipo_logo']['nombre'] ?? '');

        foreach ($metadataPorClave as $clave => $metadatos) {
            $tecnicasCompartidas = $metadatos['tecnicasCompartidas'] ?? [];
            if (!is_array($tecnicasCompartidas)) {
                continue;
            }
            $participa = false;
            foreach ($tecnicasCompartidas as $t) {
                if (strcasecmp((string) $t, $nombreTipo) === 0) {
                    $participa = true;
                    break;
                }
            }
            if (!$participa) {
                continue;
            }

            $rutaCompartida = $rutasPorClave[$clave] ?? null;
            if (!$rutaCompartida || !is_string($rutaCompartida)) {
                Log::warning('Logo compartido sin ruta al vincular (bordado)', [
                    'clave' => $clave,
                    'tecnica' => $nombreTipo,
                    'prenda_tecnica_id' => $prendaTecnica->id,
                ]);
                continue;
            }

            $rutaNormalizada = $rutaCompartida;
            if (str_starts_with($rutaNormalizada, '/storage/')) {
                $rutaNormalizada = substr($rutaNormalizada, strlen('/storage/'));
            }
            $rutaNormalizada = ltrim($rutaNormalizada, '/');

            $rutasAComparar = array_values(array_unique(array_filter([
                $rutaNormalizada,
                '/storage/' . ltrim($rutaNormalizada, '/'),
            ], fn ($v) => is_string($v) && $v !== '')));

            $yaExiste = LogoCotizacionTecnicaPrendaFoto::where('logo_cotizacion_tecnica_prenda_id', (int) $prendaTecnica->id)
                ->whereIn('ruta_webp', $rutasAComparar)
                ->exists();
            if ($yaExiste) {
                continue;
            }

            LogoCotizacionTecnicaPrendaFoto::create([
                'logo_cotizacion_tecnica_prenda_id' => $prendaTecnica->id,
                'ruta_original' => $rutaNormalizada,
                'ruta_webp' => $rutaNormalizada,
                'ruta_miniatura' => $rutaNormalizada,
                'orden' => 999,
            ]);

            Log::info('✓ Logo compartido vinculado a prenda técnica (bordado)', [
                'prenda_tecnica_id' => $prendaTecnica->id,
                'clave' => $clave,
                'ruta' => $rutaNormalizada,
            ]);
        }
    }

    /**
     * Procesar fotos de una prenda técnica
     */
    private function procesarFotosDelPrenda(
        int $prendaTecnicaId,
        int $logoCotizacionId,
        int $cotizacionId,
        int $tecnicaIdx,
        int $prendaIdx,
        array $archivos
    ): void {
        // Buscar archivos de esta prenda: tecnica_{tecnicaIdx}_prenda_{prendaIdx}_img_{imgIdx}
        $imgIdx = 0;

        foreach ($archivos as $fieldName => $archivo) {
            if (preg_match(
                "/^tecnica_{$tecnicaIdx}_prenda_{$prendaIdx}_img_(\d+)$/",
                $fieldName,
                $matches
            )) {
                $orden = (int) $matches[1];

                try {
                    $rutas = $this->imagenService->guardarImagen(
                        $archivo,
                        $cotizacionId,
                        'TÉCNICA'
                    );

                    LogoCotizacionTecnicaPrendaFoto::create([
                        'logo_cotizacion_tecnica_prenda_id' => $prendaTecnicaId,
                        'ruta_original' => $rutas['ruta_original'] ?? $rutas['ruta_webp'],
                        'ruta_webp' => $rutas['ruta_webp'],
                        'ruta_miniatura' => $rutas['ruta_miniatura'] ?? $rutas['ruta_webp'],
                        'orden' => $orden,
                    ]);

                    Log::info('✓ Foto procesada', [
                        'tecnica' => $tecnicaIdx,
                        'prenda' => $prendaIdx,
                        'orden' => $orden,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error procesando foto', [
                        'tecnica' => $tecnicaIdx,
                        'prenda' => $prendaIdx,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Obtener o crear prenda base (prenda_cot)
     */
    private function obtenerOCrearPrendaCot(
        int $logoCotizacionId,
        int $cotizacionId,
        string $nombre,
        $variaciones = null,
        $grupo = null
    ): int {
        // Buscar prenda existente con mismo nombre y cotización
        $prenda = PrendaCot::where('nombre_producto', $nombre)
            ->where('cotizacion_id', $cotizacionId)
            ->first();

        if ($prenda) {
            return (int) $prenda->id;
        }

        // Crear prenda nueva
        $prenda = PrendaCot::create([
            'nombre_producto' => $nombre,
            'cotizacion_id' => $cotizacionId,
        ]);

        Log::info('✓ Prenda base creada', ['prenda_id' => $prenda->id]);

        return (int) $prenda->id;
    }

    /**
     * Eliminar prenda técnica y todas sus fotos
     */
    private function eliminarTecnicaPrendaYFotos(LogoCotizacionTecnicaPrenda $prendaTecnica): void
    {
        foreach ($prendaTecnica->fotos as $foto) {
            $foto->forceDelete();
        }

        $prendaCotId = (int) $prendaTecnica->prenda_cot_id;
        $prendaTecnica->delete();

        // Intentar eliminar prenda base si no se usa en otra técnica
        if ($prendaCotId) {
            $sigueUsandose = LogoCotizacionTecnicaPrenda::where('prenda_cot_id', $prendaCotId)->exists();

            if (!$sigueUsandose) {
                PrendaCot::destroy($prendaCotId);
                Log::info('✓ Prenda base eliminada al no usarse más', [
                    'prenda_id' => $prendaCotId,
                ]);
            }
        }

        Log::info('✓ Prenda técnica y fotos eliminadas', [
            'prenda_tecnica_id' => $prendaTecnica->id,
        ]);
    }

    /**
     * Extraer IDs de técnicas del array
     */
    private function extraerIds(array $tecnicas): array
    {
        $ids = [];

        foreach ($tecnicas as $tecnica) {
            foreach (($tecnica['prendas'] ?? []) as $prenda) {
                if (!empty($prenda['id'])) {
                    $ids[] = (int) $prenda['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
