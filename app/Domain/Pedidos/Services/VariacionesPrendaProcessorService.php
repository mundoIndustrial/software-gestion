<?php

namespace App\Domain\Pedidos\Services;

use App\Application\Services\ColorGeneroMangaBrocheService;
use Illuminate\Support\Facades\Log;

/**
 * VariacionesPrendaProcessorService
 * 
 * Responsabilidad: Procesar variaciones de prenda (color, tela, manga, broche)
 * - Extraer variaciones anidadas
 * - Procesar color (crear/obtener ID si viene como nombre)
 * - Procesar tela (crear/obtener ID si viene como nombre)
 * - Procesar manga (crear/obtener ID si viene como nombre)
 * - Procesar broche (crear/obtener ID si viene como nombre)
 * 
 * Mantiene EXACTAMENTE los mismos logs que el código original
 */
class VariacionesPrendaProcessorService
{
    public function __construct(
        private ColorGeneroMangaBrocheService $colorGeneroService
    ) {}

    /**
     * Procesar todas las variaciones (color, tela, manga, broche)
     * Modifica $prendaData por referencia agregando IDs
     */
    public function procesarVariaciones(array &$prendaData): void
    {
        // Extraer variaciones anidadas si existen
        $this->extraerVariacionesAnidadas($prendaData);

        // Procesar color
        $this->procesarColor($prendaData);

        // Procesar tela
        $this->procesarTela($prendaData);

        // Procesar manga
        $this->procesarManga($prendaData);

        // Procesar broche
        $this->procesarBroche($prendaData);

        \Log::info(' [PedidoPrendaService::guardarPrenda] DESPUÃ‰S - Variaciones procesadas', [
            'color_id_final' => $prendaData['color_id'] ?? 'NULL',
            'tela_id_final' => $prendaData['tela_id'] ?? 'NULL',
            'tipo_manga_id_final' => $prendaData['tipo_manga_id'] ?? 'NULL',
            'tipo_broche_boton_id_final' => $prendaData['tipo_broche_boton_id'] ?? 'NULL',
        ]);
    }

    /**
     * Extraer variaciones anidadas si existen
     * NO TOCAR - Mantener logs exactamente igual
     */
    private function extraerVariacionesAnidadas(array &$prendaData): void
    {
        if (isset($prendaData['variaciones']) && is_array($prendaData['variaciones'])) {
            foreach ($prendaData['variaciones'] as $key => $value) {
                if (!isset($prendaData[$key])) {
                    $prendaData[$key] = $value;
                }
            }
            
            Log::info(' [PedidoPrendaService] Datos extraÃ­dos de variaciones anidadas', [
                'claves_extraidas' => array_keys($prendaData['variaciones']),
            ]);
        }
    }

    /**
     * Procesar color
     * NO TOCAR - Mantener logs exactamente igual
     */
    private function procesarColor(array &$prendaData): void
    {
        if (!empty($prendaData['color']) && empty($prendaData['color_id'])) {
            \Log::info(' [PedidoPrendaService::guardarPrenda] Procesando COLOR', [
                'color_nombre' => $prendaData['color'],
                'color_id_actual' => $prendaData['color_id'] ?? 'NULL',
            ]);
            
            $color = $this->colorGeneroService->obtenerOCrearColor($prendaData['color']);
            
            if ($color) {
                $prendaData['color_id'] = $color->id;
                \Log::info(' [PedidoPrendaService] Color creado/obtenido', [
                    'nombre' => $prendaData['color'],
                    'id' => $color->id,
                    'color_object' => $color,
                ]);
            } else {
                \Log::error(' [PedidoPrendaService] Error: color es NULL', [
                    'color_nombre' => $prendaData['color'],
                ]);
            }
        } else {
            \Log::info('[PedidoPrendaService] Color SALTADO', [
                'color_nombre_vacio' => empty($prendaData['color']),
                'color_id_existe' => !empty($prendaData['color_id']),
                'color_nombre' => $prendaData['color'] ?? 'NULL',
                'color_id' => $prendaData['color_id'] ?? 'NULL',
            ]);
        }
    }

    /**
     * Procesar tela
     * NO TOCAR - Mantener logs exactamente igual
     */
    private function procesarTela(array &$prendaData): void
    {
        if (!empty($prendaData['tela']) && empty($prendaData['tela_id'])) {
            \Log::info(' [PedidoPrendaService::guardarPrenda] Procesando TELA', [
                'tela_nombre' => $prendaData['tela'],
                'tela_id_actual' => $prendaData['tela_id'] ?? 'NULL',
            ]);
            
            $tela = $this->colorGeneroService->obtenerOCrearTela($prendaData['tela']);
            
            if ($tela) {
                $prendaData['tela_id'] = $tela->id;
                \Log::info(' [PedidoPrendaService] Tela creada/obtenida', [
                    'nombre' => $prendaData['tela'],
                    'id' => $tela->id,
                    'tela_object' => $tela,
                ]);
            } else {
                \Log::error(' [PedidoPrendaService] Error: tela es NULL', [
                    'tela_nombre' => $prendaData['tela'],
                ]);
            }
        } else {
            \Log::info('[PedidoPrendaService] Tela SALTADA', [
                'tela_nombre_vacia' => empty($prendaData['tela']),
                'tela_id_existe' => !empty($prendaData['tela_id']),
                'tela_nombre' => $prendaData['tela'] ?? 'NULL',
                'tela_id' => $prendaData['tela_id'] ?? 'NULL',
            ]);
        }
    }

    /**
     * Procesar manga
     * NO TOCAR - Mantener logs exactamente igual
     */
    private function procesarManga(array &$prendaData): void
    {
        if (!empty($prendaData['manga']) && empty($prendaData['tipo_manga_id'])) {
            $manga = $this->colorGeneroService->obtenerOCrearManga($prendaData['manga']);
            if ($manga) {
                $prendaData['tipo_manga_id'] = $manga->id;
                Log::info(' [PedidoPrendaService] Manga creada/obtenida', [
                    'nombre' => $prendaData['manga'],
                    'id' => $manga->id,
                ]);
            }
        }
    }

    /**
     * Procesar broche
     * NO TOCAR - Mantener logs exactamente igual
     */
    private function procesarBroche(array &$prendaData): void
    {
        if (!empty($prendaData['broche']) && empty($prendaData['tipo_broche_boton_id'])) {
            $broche = $this->colorGeneroService->obtenerOCrearBroche($prendaData['broche']);
            if ($broche) {
                $prendaData['tipo_broche_boton_id'] = $broche->id;
                Log::info(' [PedidoPrendaService] Broche/Botón creado/obtenido', [
                    'nombre' => $prendaData['broche'],
                    'id' => $broche->id,
                ]);
            }
        }
    }
}

