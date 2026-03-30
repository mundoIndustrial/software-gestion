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
        if (is_null($fotosTelas) && !empty($fotosTelasProcesadas)) {
            $fotosTelasDesdeProcesadas = [];
            foreach ($fotosTelasProcesadas as $procesada) {
                $ultimaColorTela = $prenda->coloresTelas()->latest('id')->first();
                if ($ultimaColorTela) {
                    $fotosTelasDesdeProcesadas[] = [
                        'prenda_pedido_colores_telas_id' => $ultimaColorTela->id,
                        'ruta_original' => $procesada['ruta_original'] ?? null,
                        'ruta_webp' => $procesada['ruta_webp'] ?? null,
                    ];
                }
            }

            if (!empty($fotosTelasDesdeProcesadas)) {
                $this->procesarFotosTelasArray($prenda, $fotosTelasDesdeProcesadas);
                return;
            }
        }

        if (is_null($fotosTelas)) {
            return;
        }

        if (empty($fotosTelas)) {
            $prenda->fotosTelas()->delete();
            return;
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

    /**
     * @param array<int, array<string, mixed>> $fotosTelas
     */
    private function procesarFotosTelasArray(PrendaPedido $prenda, array $fotosTelas): void
    {
        foreach ($fotosTelas as $idx => $foto) {
            $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? null;
            $ruta = $foto['ruta_original'] ?? null;
            $rutaWebp = $foto['ruta_webp'] ?? null;

            if (!$colorTelaId || !$ruta) {
                continue;
            }

            if (!$rutaWebp) {
                $rutaWebp = $this->generarRutaWebp($ruta);
            }

            $datosFoto = [
                'prenda_pedido_colores_telas_id' => $colorTelaId,
                'ruta_original' => $ruta,
                'ruta_webp' => $rutaWebp,
                'orden' => $idx + 1,
            ];

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

