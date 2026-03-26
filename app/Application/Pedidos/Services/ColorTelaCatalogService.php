<?php

namespace App\Application\Pedidos\Services;

use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use Illuminate\Support\Facades\Log;

/**
 * Resuelve colores y telas de catalogo para flujos de pedidos.
 */
class ColorTelaCatalogService
{
    public function obtenerOCrearColor(?string $nombreColor): ?int
    {
        if (empty($nombreColor)) {
            return null;
        }

        try {
            $colorNuevo = ColorPrenda::create([
                'nombre' => $nombreColor,
                'codigo' => $this->generarCodigo($nombreColor),
                'activo' => true,
            ]);

            Log::info('[ColorTelaCatalogService] Color creado', [
                'nombre' => $nombreColor,
                'color_id' => $colorNuevo->id,
                'codigo' => $colorNuevo->codigo,
            ]);

            return $colorNuevo->id;
        } catch (\Exception $e) {
            Log::error('[ColorTelaCatalogService] Error obteniendo/creando color', [
                'nombre' => $nombreColor,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function obtenerOCrearTela(?string $nombreTela, ?string $referencia = null): ?int
    {
        if (empty($nombreTela)) {
            return null;
        }

        try {
            $telaNueva = TelaPrenda::create([
                'nombre' => $nombreTela,
                'referencia' => $referencia ?? '',
                'descripcion' => "Tela: {$nombreTela}",
                'activo' => true,
            ]);

            Log::info('[ColorTelaCatalogService] Tela creada', [
                'nombre' => $nombreTela,
                'tela_id' => $telaNueva->id,
                'referencia' => $telaNueva->referencia,
            ]);

            return $telaNueva->id;
        } catch (\Exception $e) {
            Log::error('[ColorTelaCatalogService] Error obteniendo/creando tela', [
                'nombre' => $nombreTela,
                'referencia' => $referencia,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function procesarTela(array $telaData): array
    {
        $nombreTela = $telaData['tela'] ?? null;
        $nombreColor = $telaData['color'] ?? null;
        $referencia = $telaData['referencia'] ?? null;

        $colorId = $this->obtenerOCrearColor($nombreColor);
        $telaId = $this->obtenerOCrearTela($nombreTela, $referencia);

        Log::info('[ColorTelaCatalogService] Tela procesada', [
            'nombre_tela' => $nombreTela,
            'nombre_color' => $nombreColor,
            'referencia' => $referencia,
            'color_id' => $colorId,
            'tela_id' => $telaId,
        ]);

        return [
            'color_id' => $colorId,
            'tela_id' => $telaId,
            'nombre_tela' => $nombreTela,
            'nombre_color' => $nombreColor,
        ];
    }

    public function procesarTelas(array $telas): array
    {
        $telasProcessadas = [];

        foreach ($telas as $tela) {
            $telasProcessadas[] = $this->procesarTela($tela);
        }

        return $telasProcessadas;
    }

    private function generarCodigo(string $nombre): string
    {
        $codigo = strtoupper(str_replace(' ', '-', trim($nombre)));
        return substr($codigo, 0, 50);
    }
}
