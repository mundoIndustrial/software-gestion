<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaPedido;

final class PrendaFotosTelasUpdaterService
{
    public function __construct(
        private readonly ImagenService $imagenService,
    ) {
    }

    /**
     * @param array<int, mixed>|null $fotosTelas
     * @param array<int, array<string, mixed>>|null $fotosTelasProcesadas
     */
    public function actualizarFotosTelas(
        PrendaPedido $prenda,
        ?array $fotosTelas,
        ?array $fotosTelasProcesadas = null
    ): void {
        $colorTelaIdsValidos = $prenda->coloresTelas()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $colorTelaIdsValidosSet = array_fill_keys($colorTelaIdsValidos, true);

        if (is_null($fotosTelas)) {
            if (!empty($fotosTelasProcesadas)) {
                \Log::error('[PrendaFotosTelasUpdaterService] Fotos de tela recibidas sin metadatos fotos_telas', [
                    'prenda_id' => $prenda->id,
                    'cantidad_archivos' => count($fotosTelasProcesadas),
                ]);
                throw new \RuntimeException('Se recibieron archivos de fotos_tela sin metadatos fotos_telas.');
            }
            return;
        }

        if (empty($fotosTelas)) {
            // Guard rail defensivo:
            // En escenarios de desincronizacion del frontend puede llegar fotos_telas=[]
            // sin intencion real de borrar todas las fotos de tela de la prenda.
            // Para evitar perdida de informacion, se conserva lo existente.
            \Log::warning('[PrendaFotosTelasUpdaterService] fotos_telas vacio - se omite borrado defensivamente', [
                'prenda_id' => $prenda->id,
                'fotos_telas_actuales' => $prenda->fotosTelas()->count(),
                'fotos_telas_procesadas_recibidas' => is_array($fotosTelasProcesadas) ? count($fotosTelasProcesadas) : 0,
            ]);
            return;
        }

        if (!empty($fotosTelasProcesadas)) {
            $placeholdersNuevos = 0;
            foreach ($fotosTelas as $foto) {
                if (!is_array($foto)) {
                    continue;
                }

                $id = $foto['id'] ?? null;
                $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;

                if (empty($id) && empty($ruta)) {
                    $placeholdersNuevos++;
                }
            }

            if ($placeholdersNuevos !== count($fotosTelasProcesadas)) {
                \Log::error('[PrendaFotosTelasUpdaterService] Desfase entre fotos_telas y fotos_telas_procesadas', [
                    'prenda_id' => $prenda->id,
                    'placeholders_nuevos' => $placeholdersNuevos,
                    'archivos_procesados' => count($fotosTelasProcesadas),
                ]);
                throw new \RuntimeException('Desfase entre archivos fotos_tela y metadatos fotos_telas.');
            }
        }

        // Eliminación por diferencia
        try {
            $incomingByColorTela = [];
            foreach ($fotosTelas as $foto) {
                if (!is_array($foto)) {
                    continue;
                }

                $colorTelaId = $this->resolverColorTelaIdParaPrenda($prenda, $foto, $colorTelaIdsValidosSet);
                if (!$colorTelaId) {
                    continue;
                }

                if (!isset($incomingByColorTela[$colorTelaId])) {
                    $incomingByColorTela[$colorTelaId] = ['ids' => [], 'rutas' => []];
                }

                if (!empty($foto['id'])) {
                    $incomingByColorTela[$colorTelaId]['ids'][] = (int) $foto['id'];
                }

                $ruta = $this->normalizarRutaStorage($foto['ruta_original'] ?? $foto['path'] ?? null);
                if (is_string($ruta) && $ruta !== '') {
                    $incomingByColorTela[$colorTelaId]['rutas'][] = $ruta;
                }
            }

            foreach ($incomingByColorTela as $colorTelaId => $incoming) {
                $query = PrendaFotoTelaPedido::where('prenda_pedido_colores_telas_id', $colorTelaId)
                    ->whereHas('colorTela', function ($q) use ($prenda) {
                        $q->where('prenda_pedido_id', $prenda->id);
                    });
                if (!empty($incoming['ids'])) {
                    $query->whereNotIn('id', array_values(array_unique($incoming['ids'])));
                } elseif (!empty($incoming['rutas'])) {
                    $query->whereNotIn('ruta_original', array_values(array_unique($incoming['rutas'])));
                }

                foreach ($query->get() as $foto) {
                    $rutaOriginal = $foto->ruta_original;
                    $rutaWebp = $foto->ruta_webp;
                    $foto->delete();
                    if ($rutaOriginal) {
                        $this->imagenService->eliminarImagen($rutaOriginal);
                    }
                    if ($rutaWebp && $rutaWebp !== $rutaOriginal) {
                        $this->imagenService->eliminarImagen($rutaWebp);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('[PrendaFotosTelasUpdaterService] Error en eliminación por diferencia', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
            ]);
        }

        $indiceFotoNueva = 0;
        foreach ($fotosTelas as $idx => $foto) {
            if (is_string($foto)) {
                continue;
            }
            if (!is_array($foto)) {
                continue;
            }

            $id = $foto['id'] ?? null;
            $colorTelaId = $this->resolverColorTelaIdParaPrenda($prenda, $foto, $colorTelaIdsValidosSet);
            $ruta = $this->normalizarRutaStorage($foto['ruta_original'] ?? $foto['path'] ?? null);
            $rutaWebp = null;

            if (!$id && !$ruta) {
                if (is_array($fotosTelasProcesadas) && isset($fotosTelasProcesadas[$indiceFotoNueva])) {
                    $procesado = $fotosTelasProcesadas[$indiceFotoNueva];
                    $ruta = $this->normalizarRutaStorage($procesado['ruta_original'] ?? null);
                    $rutaWebp = $this->normalizarRutaStorage($procesado['ruta_webp'] ?? null);
                    if ($ruta) {
                        $indiceFotoNueva++;
                    }
                }
            }

            if (!$colorTelaId && isset($foto['color_id']) && isset($foto['tela_id'])) {
                $colorTelaId = $this->obtenerOCrearColorTela($prenda, $foto['color_id'], $foto['tela_id']);
            }

            if (!$colorTelaId || !$ruta) {
                \Log::warning('[PrendaFotosTelasUpdaterService] Foto de tela omitida por falta de vínculo color/tela o ruta', [
                    'prenda_id' => $prenda->id,
                    'foto_id' => $id,
                    'color_tela_id' => $colorTelaId,
                    'color_id' => $foto['color_id'] ?? null,
                    'tela_id' => $foto['tela_id'] ?? null,
                    'color_nombre' => $foto['color_nombre'] ?? null,
                    'tela_nombre' => $foto['tela_nombre'] ?? null,
                    'ruta' => $ruta,
                ]);
                continue;
            }

            if (!$rutaWebp) {
                $rutaWebp = $this->normalizarRutaStorage($foto['ruta_webp'] ?? null) ?? $this->generarRutaWebp($ruta);
            }

            $datosFoto = [
                'prenda_pedido_colores_telas_id' => $colorTelaId,
                'ruta_original' => $ruta,
                'ruta_webp' => $rutaWebp,
                'orden' => $idx + 1,
            ];

            if ($id) {
                $fotoExistente = $prenda->fotosTelas()->where('prenda_fotos_tela_pedido.id', $id)->first();
                if ($fotoExistente) {
                    $fotoExistente->update($datosFoto);
                }
                continue;
            }

            $existente = PrendaFotoTelaPedido::where('prenda_pedido_colores_telas_id', $colorTelaId)
                ->where('ruta_original', $ruta)
                ->first();

            if (!$existente) {
                PrendaFotoTelaPedido::create($datosFoto);
            }
        }
    }

    private function obtenerOCrearColorTela(PrendaPedido $prenda, $colorId, $telaId): ?int
    {
        $colorTela = $prenda->coloresTelas()
            ->where('color_id', $colorId)
            ->where('tela_id', $telaId)
            ->first();

        if ($colorTela) {
            return $colorTela->id;
        }

        $colorTela = $prenda->coloresTelas()->create([
            'color_id' => $colorId,
            'tela_id' => $telaId,
        ]);

        return $colorTela->id;
    }

    /**
     * @param array<string, mixed> $foto
     * @param array<int, bool> $colorTelaIdsValidosSet
     */
    private function resolverColorTelaIdParaPrenda(PrendaPedido $prenda, array $foto, array &$colorTelaIdsValidosSet): ?int
    {
        $colorTelaId = (int) ($foto['prenda_pedido_colores_telas_id'] ?? $foto['color_tela_id'] ?? 0);

        if ($colorTelaId > 0 && isset($colorTelaIdsValidosSet[$colorTelaId])) {
            return $colorTelaId;
        }

        if (
            isset($foto['color_id'], $foto['tela_id']) &&
            (int) $foto['color_id'] > 0 &&
            (int) $foto['tela_id'] > 0
        ) {
            $colorTelaCreadoId = $this->obtenerOCrearColorTela($prenda, $foto['color_id'], $foto['tela_id']);
            if ($colorTelaCreadoId) {
                $colorTelaIdsValidosSet[(int) $colorTelaCreadoId] = true;
                return (int) $colorTelaCreadoId;
            }
        }

        if ($colorTelaId > 0) {
            \Log::warning('[PrendaFotosTelasUpdaterService] color_tela_id omitido por no pertenecer a la prenda actual', [
                'prenda_id' => $prenda->id,
                'color_tela_id_recibido' => $colorTelaId,
                'color_id' => $foto['color_id'] ?? null,
                'tela_id' => $foto['tela_id'] ?? null,
            ]);
        }

        return null;
    }

    private function normalizarRutaStorage(mixed $ruta): ?string
    {
        if (!is_string($ruta)) {
            return null;
        }

        $ruta = trim(str_replace('\\', '/', $ruta));
        if ($ruta === '') {
            return null;
        }

        if (preg_match('#^https?://[^/]+/storage/(.+)$#i', $ruta, $matches)) {
            return $matches[1];
        }

        if (str_starts_with($ruta, '/storage/')) {
            return substr($ruta, 9);
        }

        if (str_starts_with($ruta, 'storage/')) {
            return substr($ruta, 8);
        }

        return ltrim($ruta, '/');
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
