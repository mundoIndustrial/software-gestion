<?php

namespace App\Application\Services\Asesores;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarFotosTelasService
{
    /**
     * Procesar fotos de telas: guardar archivos en storage y retornar rutas
     * 
     * @param Request $request
     * @param array $productos
     * @return array Productos con fotos procesadas
     */
    public function procesar(Request $request, array $productos): array
    {
        Log::info('📁 [FOTOS-TELAS] Iniciando procesamiento de fotos');

        $productosProcessados = [];
        $allFiles = $request->allFiles();

        Log::info('📁 [FOTOS-TELAS] Archivos recibidos: ' . count($allFiles));

        foreach ($productos as $productoIndex => $producto) {
            $productosProcessados[$productoIndex] = $producto;

            // Procesar telas múltiples si existen
            if (!empty($producto['telas']) && is_array($producto['telas'])) {
                $telasProcessadas = [];

                foreach ($producto['telas'] as $telaIndex => $tela) {
                    $telasProcessadas[$telaIndex] = $tela;
                    $fotosProcessadas = [];

                    // Buscar archivos de fotos para esta tela específica
                    $fotosKey = "productos_friendly.{$productoIndex}.telas.{$telaIndex}.fotos";
                    
                    $archivos = $this->obtenerArchivos($request, $allFiles, $fotosKey);
                    
                    if (!empty($archivos)) {
                        Log::info('✅ [FOTOS-TELAS] Procesando fotos', [
                            'producto' => $productoIndex,
                            'tela' => $telaIndex,
                            'cantidad' => count($archivos)
                        ]);

                        $fotosProcessadas = $this->guardarFotos($archivos);
                    }

                    // Guardar fotos procesadas en la tela
                    if (!empty($fotosProcessadas)) {
                        $telasProcessadas[$telaIndex]['fotos'] = $fotosProcessadas;
                    }
                }

                // Guardar telas procesadas en el producto
                $productosProcessados[$productoIndex]['telas'] = $telasProcessadas;
            }
        }

        Log::info('✅ [FOTOS-TELAS] Procesamiento completado');

        return $productosProcessados;
    }

    /**
     * Obtener archivos de diferentes formas del request
     */
    private function obtenerArchivos(Request $request, array $allFiles, string $fotosKey): array
    {
        // Método 1: Usando allFiles()
        if (!empty($allFiles[$fotosKey])) {
            return $allFiles[$fotosKey];
        }
        
        // Método 2: Usando hasFile() y file()
        if ($request->hasFile($fotosKey)) {
            $archivos = $request->file($fotosKey);
            return is_array($archivos) ? $archivos : [$archivos];
        }

        return [];
    }

    /**
     * Guardar archivos de fotos en storage
     */
    private function guardarFotos(array $archivos): array
    {
        $fotosGuardadas = [];

        foreach ($archivos as $archivoFoto) {
            if ($archivoFoto && $archivoFoto->isValid()) {
                try {
                    $rutaGuardada = $archivoFoto->store('telas/pedidos', 'public');
                    
                    Log::info('✅ [FOTOS-TELAS] Foto guardada', [
                        'nombre' => $archivoFoto->getClientOriginalName(),
                        'ruta' => $rutaGuardada,
                        'tamaño' => $archivoFoto->getSize(),
                    ]);

                    $fotosGuardadas[] = [
                        'ruta_original' => Storage::url($rutaGuardada),
                        'ruta_webp' => null,
                        'ruta_miniatura' => null,
                        'ancho' => null,
                        'alto' => null,
                        'tamaño' => $archivoFoto->getSize(),
                    ];
                } catch (\Exception $e) {
                    Log::error('❌ [FOTOS-TELAS] Error al guardar', [
                        'error' => $e->getMessage(),
                        'archivo' => $archivoFoto->getClientOriginalName(),
                    ]);
                }
            }
        }

        return $fotosGuardadas;
    }

    /**
     * Procesar imágenes del logo
     */
    public function procesarImagenesLogo(Request $request): array
    {
        Log::info('🎨 [LOGO-IMAGENES] Procesando imágenes del logo');

        $imagenesProcesadas = [];
        
        if ($request->hasFile('logo.imagenes')) {
            foreach ($request->file('logo.imagenes') as $imagen) {
                if ($imagen->isValid()) {
                    try {
                        $rutaGuardada = $imagen->store('logos/pedidos', 'public');
                        
                        $imagenesProcesadas[] = [
                            'nombre_archivo' => $imagen->getClientOriginalName(),
                            'ruta_original' => Storage::url($rutaGuardada),
                            'ruta_webp' => null,
                            'url' => Storage::url($rutaGuardada),
                            'tamaño_archivo' => $imagen->getSize(),
                            'tipo_archivo' => $imagen->getMimeType(),
                            'orden' => 0
                        ];

                        Log::info('✅ [LOGO-IMAGENES] Imagen guardada', [
                            'nombre' => $imagen->getClientOriginalName(),
                            'ruta' => $rutaGuardada,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('❌ [LOGO-IMAGENES] Error al guardar', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        Log::info('✅ [LOGO-IMAGENES] Procesamiento completado', ['total' => count($imagenesProcesadas)]);

        return $imagenesProcesadas;
    }
}
