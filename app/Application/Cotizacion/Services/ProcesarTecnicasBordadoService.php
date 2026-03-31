<?php

namespace App\Application\Cotizacion\Services;

use App\Models\LogoCotizacion;
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
    public function ejecutar(int $logoCotizacionId, array $tecnicas, array $archivos = []): void
    {
        Log::info('Procesando técnicas nuevas', [
            'logo_cotizacion_id' => $logoCotizacionId,
            'tecnicas_count' => count($tecnicas),
        ]);

        // Procesar logos compartidos globalmente
        $logosCompartidosGuardados = $this->procesarLogosCompartidos(
            $logoCotizacionId,
            $archivos
        );

        // Procesar cada técnica
        foreach ($tecnicas as $tecnicaIdx => $tecnica) {
            $this->procesarTecnicaIndividual(
                $logoCotizacionId,
                $tecnicaIdx,
                $tecnica,
                $archivos,
                $logosCompartidosGuardados
            );
        }
    }

    /**
     * Sincronizar: Actualizar técnicas existentes
     */
    public function sincronizar(int $logoCotizacionId, array $tecnicas, array $archivos = []): void
    {
        Log::info('Sincronizando técnicas existentes', [
            'logo_cotizacion_id' => $logoCotizacionId,
            'tecnicas_count' => count($tecnicas),
        ]);

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
                    // Nueva prenda técnica: crearla
                    $this->crearPrendaTecnica(
                        $logoCotizacionId,
                        $tecnicaIdx,
                        $prendaIdx,
                        $tecnica,
                        $prenda,
                        $archivos,
                    );
                } else {
                    // Actualizar existente
                    $model = $existentes->firstWhere('id', (int) $prendaTecnicaId);
                    if ($model) {
                        $model->update([
                            'tipo_logo_id' => $tecnica['tipo_logo']['id'] ?? $model->tipo_logo_id,
                            'observaciones' => $prenda['observaciones'] ?? $model->observaciones,
                            'ubicaciones' => $prenda['ubicaciones'] ?? $model->ubicaciones,
                            'talla_cantidad' => $prenda['talla_cantidad'] ?? $model->talla_cantidad,
                            'variaciones_prenda' => $prenda['variaciones_prenda'] ?? $model->variaciones_prenda,
                        ]);
                    }
                }
            }
        }

        Log::info('✓ Técnicas sincronizadas', [
            'eliminadas' => $aEliminar->count(),
        ]);
    }

    /**
     * Procesar logos compartidos (guardarse una sola vez)
     */
    private function procesarLogosCompartidos(int $logoCotizacionId, array $archivos): array
    {
        $logosGuardados = [];
        return $logosGuardados;
    }

    /**
     * Procesar una técnica individual
     */
    private function procesarTecnicaIndividual(
        int $logoCotizacionId,
        int $tecnicaIdx,
        array $tecnica,
        array $archivos,
        array $logosGuardados
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
                $tecnicaIdx,
                $prendaIdx,
                $tecnica,
                $prenda,
                $archivos,
                $logosGuardados
            );
        }
    }

    /**
     * Crear una prenda técnica con sus fotos
     */
    private function crearPrendaTecnica(
        int $logoCotizacionId,
        int $tecnicaIdx,
        int $prendaIdx,
        array $tecnica,
        array $prenda,
        array $archivos,
        array $logosGuardados = []
    ): void {
        // Obtener o crear prenda base
        $prendaCotId = $this->obtenerOCrearPrendaCot(
            $logoCotizacionId,
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
            $tecnicaIdx,
            $prendaIdx,
            $archivos
        );
    }

    /**
     * Procesar fotos de una prenda técnica
     */
    private function procesarFotosDelPrenda(
        int $prendaTecnicaId,
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
                        (int) LogoCotizacionTecnicaPrenda::find($prendaTecnicaId)->logo_cotizacion_id,
                        'TÉCNICA'
                    );

                    LogoCotizacionTecnicaPrendaFoto::create([
                        'logo_cotizacion_tecnica_prenda_id' => $prendaTecnicaId,
                        'ruta_original' => $rutas['ruta_original'] ?? $rutas['ruta_webp'],
                        'ruta_webp' => $rutas['ruta_webp'],
                        'ruta_miniatura' => $rutas['ruta_miniatura'],
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
        string $nombre,
        $variaciones = null,
        $grupo = null
    ): int {
        $variacionesKey = is_string($variaciones)
            ? $variaciones
            : json_encode($variaciones ?? null);

        // Buscar prenda existente con mismo nombre y variaciones
        $prenda = PrendaCot::where('nombre_producto', $nombre)
            ->where('variaciones', $variacionesKey)
            ->first();

        if ($prenda) {
            return (int) $prenda->id;
        }

        // Crear prenda nueva
        $prenda = PrendaCot::create([
            'nombre_producto' => $nombre,
            'variaciones' => $variacionesKey,
            'grupo' => $grupo,
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
