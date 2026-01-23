<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Support\Facades\Log;

/**
 * Servicio para mapear imÃ¡genes del JSON del frontend al formato esperado por el backend
 * 
 * El frontend envÃ­a imÃ¡genes como:
 * - imagenes: [{file: null, previewUrl: "blob:...", nombre: "...", tamaÃ±o: ...}]
 * - telas[].imagenes: [{file: null, nombre: "...", tamaÃ±o: ...}]
 * 
 * El backend espera:
 * - fotos: [UploadedFile] o [strings con rutas]
 * - telas[].fotos: [UploadedFile] o [strings con rutas]
 */
class ImagenMapperService
{
    public function __construct(
        private ColorTelaService $colorTelaService
    ) {}
    /**
     * Mapear imÃ¡genes de prenda desde JSON a formato esperado
     */
    public function mapearImagenesPrenda(array $item): array
    {
        $fotosFormateadas = [];
        
        $imagenes = $item['imagenes'] ?? [];
        if (!is_array($imagenes)) {
            return [];
        }
        
        foreach ($imagenes as $idx => $imagen) {
            // Si es un objeto con previewUrl (blob), usar como ruta
            if (isset($imagen['previewUrl']) && !empty($imagen['previewUrl'])) {
                $fotosFormateadas[] = [
                    'ruta_original' => $imagen['nombre'] ?? "imagen-{$idx}.png",
                    'ruta_webp' => $imagen['previewUrl'],
                    'orden' => $idx + 1
                ];
            }
            // Si es un objeto con file (UploadedFile), ya estÃ¡ procesado
            elseif (isset($imagen['file']) && $imagen['file'] !== null) {
                $fotosFormateadas[] = $imagen['file'];
            }
            // Si es un string (ruta), usarlo directamente
            elseif (is_string($imagen)) {
                $fotosFormateadas[] = [
                    'ruta_original' => $imagen,
                    'ruta_webp' => $imagen,
                    'orden' => $idx + 1
                ];
            }
        }
        
        Log::info(' [ImagenMapperService] ImÃ¡genes de prenda mapeadas', [
            'cantidad_original' => count($imagenes),
            'cantidad_mapeada' => count($fotosFormateadas),
        ]);
        
        return $fotosFormateadas;
    }
    
    /**
     * Mapear imÃ¡genes de telas desde JSON a formato esperado
     * TambiÃ©n obtiene/crea IDs de colores y telas
     */
    public function mapearImagenesTelas(array $telas): array
    {
        $telasFormateadas = [];
        
        foreach ($telas as $telaIdx => $tela) {
            // Obtener o crear color y tela desde nombres
            $colorTelaIds = $this->colorTelaService->procesarTela($tela);
            
            $telaFormateada = [
                'tela' => $tela['tela'] ?? '',
                'color' => $tela['color'] ?? '',
                'referencia' => $tela['referencia'] ?? '',
                'color_id' => $colorTelaIds['color_id'],
                'tela_id' => $colorTelaIds['tela_id'],
                'fotos' => []
            ];
            
            $imagenes = $tela['imagenes'] ?? [];
            if (!is_array($imagenes)) {
                $imagenes = [];
            }
            
            foreach ($imagenes as $imgIdx => $imagen) {
                // Si es un objeto con file (UploadedFile), usar directamente
                if (isset($imagen['file']) && $imagen['file'] !== null) {
                    $telaFormateada['fotos'][] = $imagen['file'];
                }
                // Si es un objeto con nombre (desde JSON), mapear a array
                elseif (is_array($imagen) && isset($imagen['nombre'])) {
                    $telaFormateada['fotos'][] = [
                        'ruta_original' => $imagen['nombre'],
                        'ruta_webp' => $imagen['nombre'],
                        'orden' => $imgIdx + 1,
                        'tamaÃ±o' => $imagen['tamaÃ±o'] ?? 0
                    ];
                }
                // Si es un string (ruta), usarlo directamente
                elseif (is_string($imagen)) {
                    $telaFormateada['fotos'][] = [
                        'ruta_original' => $imagen,
                        'ruta_webp' => $imagen,
                        'orden' => $imgIdx + 1
                    ];
                }
            }
            
            $telasFormateadas[] = $telaFormateada;
        }
        
        Log::info(' [ImagenMapperService] ImÃ¡genes de telas mapeadas', [
            'cantidad_telas' => count($telas),
            'telas_con_imagenes' => array_sum(array_map(function($t) {
                return count($t['fotos'] ?? []);
            }, $telasFormateadas))
        ]);
        
        return $telasFormateadas;
    }
}

