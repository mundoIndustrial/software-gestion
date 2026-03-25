<?php

namespace App\Infrastructure\Mappers\Imagenes;

use App\Domain\Pedidos\ValueObjects\ImagenTela;
use App\Infrastructure\Services\Pedidos\ColorTelaService;
use Illuminate\Support\Facades\Log;

/**
 * TelaImagenesMapper
 * 
 * Responsabilidad: Orquestar el mapeo de todas las imágenes de las telas
 * INCLUYE: Procesamiento de colores y telas (lógica de negocio)
 * 
 * Nota: Este mapper es UN POCO más complejo porque:
 * - Valida/procesa imágenes (responsabilidad Infrastructure)
 * - Delega a ColorTelaService para logica de negocio (Domain logic)
 * 
 * Flujo:
 * 1. Recibe array de telas del frontend
 * 2. Para cada tela:
 *    a. Procesa colores/telas via ColorTelaService
 *    b. Mapea imágenes de esa tela
 * 3. Retorna telas con imágenes mapeadas
 */
final class TelaImagenesMapper
{
    public function __construct(
        private ImagenDTOToTelaArrayMapper $dtoToArrayMapper,
        private ColorTelaService $colorTelaService,
    ) {}

    /**
     * Mapear array de telas con sus imágenes
     * 
     * @param array $telas - Array de telas del frontend
     * @return array - Array de telas con imágenes mapeadas
     * 
     * Ejemplo entrada:
     * [
     *   {
     *     tela: "Algodón",
     *     color: "Rojo",
     *     referencia: "ALG-001",
     *     imagenes: [{previewUrl: "...", nombre: "..."}]
     *   }
     * ]
     * 
     * Ejemplo salida:
     * [
     *   {
     *     tela: "Algodón",
     *     color: "Rojo",
     *     referencia: "ALG-001",
     *     color_id: 5,
     *     tela_id: 3,
     *     fotos: [{ruta_original: "...", ruta_webp: "...", orden: 1}]
     *   }
     * ]
     */
    public function mapear(array $telas): array
    {
        if (empty($telas)) {
            Log::info('[TelaImagenesMapper] No hay telas para mapear');
            return [];
        }

        Log::info('[TelaImagenesMapper] Iniciando mapeo', [
            'cantidad_telas' => count($telas),
        ]);

        $telasFormateadas = [];

        foreach ($telas as $telaIdx => $tela) {
            try {
                // 1. Procesar color y tela (LÓGICA DE NEGOCIO)
                $colorTelaIds = $this->colorTelaService->procesarTela($tela);

                // 2. Mapear imágenes de esta tela
                $imagenesMapeadas = $this->mapearImagenesTela($tela['imagenes'] ?? []);

                // 3. Construir tela formateada
                $telaFormateada = [
                    'tela' => $tela['tela'] ?? '',
                    'color' => $tela['color'] ?? '',
                    'referencia' => $tela['referencia'] ?? '',
                    'color_id' => $colorTelaIds['color_id'],
                    'tela_id' => $colorTelaIds['tela_id'],
                    'fotos' => $imagenesMapeadas,
                ];

                $telasFormateadas[] = $telaFormateada;

                Log::debug('[TelaImagenesMapper] Tela procesada', [
                    'indice' => $telaIdx,
                    'tela' => $tela['tela'] ?? 'N/A',
                    'color' => $tela['color'] ?? 'N/A',
                    'imagenes_mapeadas' => count($imagenesMapeadas),
                ]);

            } catch (\Exception $e) {
                Log::error('[TelaImagenesMapper] Error procesando tela', [
                    'indice' => $telaIdx,
                    'error' => $e->getMessage(),
                    'tela_data' => json_encode($tela),
                ]);
                // Continuar con próxima tela
                continue;
            }
        }

        Log::info('[TelaImagenesMapper] Mapeo completado', [
            'cantidad_original' => count($telas),
            'cantidad_procesada' => count($telasFormateadas),
            'descartadas' => count($telas) - count($telasFormateadas),
        ]);

        return $telasFormateadas;
    }

    /**
     * Mapear imágenes de UNA SOLA tela
     * 
     * Responsabilidad: Validar y transformar imágenes de tela
     * (Separado para poder reutilizar si es necesario)
     */
    private function mapearImagenesTela(array $imagenes): array
    {
        if (empty($imagenes)) {
            return [];
        }

        $imagenesFormateadas = [];

        foreach ($imagenes as $idx => $imagen) {
            try {
                // Validar y crear VO
                $imagenVO = ImagenTela::from($imagen, $idx + 1);

                // Transformar a array
                $imagenesFormateadas[] = $this->dtoToArrayMapper->mapear($imagenVO);

            } catch (\InvalidArgumentException $e) {
                Log::warning('[TelaImagenesMapper] Imagen de tela inválida, ignorando', [
                    'indice' => $idx,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $imagenesFormateadas;
    }
}
