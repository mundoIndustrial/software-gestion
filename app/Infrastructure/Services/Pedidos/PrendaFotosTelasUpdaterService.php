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
            $prenda->fotosTelas()->delete();
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

                $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? $foto['color_tela_id'] ?? null;
                if (!$colorTelaId) {
                    continue;
                }

                if (!isset($incomingByColorTela[$colorTelaId])) {
                    $incomingByColorTela[$colorTelaId] = ['ids' => [], 'rutas' => []];
                }

                if (!empty($foto['id'])) {
                    $incomingByColorTela[$colorTelaId]['ids'][] = (int) $foto['id'];
                }

                $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
                if (is_string($ruta) && $ruta !== '') {
                    $incomingByColorTela[$colorTelaId]['rutas'][] = $ruta;
                }
            }

            foreach ($incomingByColorTela as $colorTelaId => $incoming) {
                $query = PrendaFotoTelaPedido::where('prenda_pedido_colores_telas_id', $colorTelaId);
                if (!empty($incoming['ids'])) {
                    $query->whereNotIn('id', array_values(array_unique($incoming['ids'])));
                } elseif (!empty($incoming['rutas'])) {
                    $query->whereNotIn('ruta_original', array_values(array_unique($incoming['rutas'])));
                } else {
                    continue;
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
            $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? $foto['color_tela_id'] ?? null;
            $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
            $rutaWebp = null;

            if (!$id && !$ruta) {
                if (is_array($fotosTelasProcesadas) && isset($fotosTelasProcesadas[$indiceFotoNueva])) {
                    $procesado = $fotosTelasProcesadas[$indiceFotoNueva];
                    $ruta = $procesado['ruta_original'] ?? null;
                    $rutaWebp = $procesado['ruta_webp'] ?? null;
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
                $rutaWebp = $foto['ruta_webp'] ?? $this->generarRutaWebp($ruta);
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

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
