<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Servicio de Dominio para procesar FormData
 * Extrae imágenes, procesos y telas desde FormData
 */
class FormDataProcessorService
{
    /**
     * Procesar imágenes de prenda desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @return UploadedFile[]
     */
    public function extraerImagenesPrenda(Request $request, int $itemIndex): array
    {
        $allFiles = $request->allFiles();
        $fotosFiltered = [];

        foreach ($allFiles as $key => $files) {
            if (preg_match("/^items\.{$itemIndex}\.imagenes/", $key)) {
                $fotosUploadedFiles = (array)$files;
                
                $fotosFiltered = array_merge($fotosFiltered, array_filter($fotosUploadedFiles, function($foto) {
                    return $foto instanceof UploadedFile;
                }));
            }
        }

        return $fotosFiltered;
    }

    /**
     * Procesar imágenes de telas desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @param array $telas - Array de telas con estructura básica
     * @return array - Telas con imágenes adjuntas
     */
    public function extraerImagenesTelas(Request $request, int $itemIndex, array $telas): array
    {
        $allFiles = $request->allFiles();
        $telasConImagenes = [];

        // Primero, copiar datos de telas existentes
        foreach ($telas as $telaIdx => $telaDatos) {
            $telasConImagenes[$telaIdx] = is_array($telaDatos) ? $telaDatos : [];
            if (!isset($telasConImagenes[$telaIdx]['fotos'])) {
                $telasConImagenes[$telaIdx]['fotos'] = [];
            }
        }

        // Buscar imágenes en FormData
        foreach ($allFiles as $key => $files) {
            if (preg_match("/^items\.{$itemIndex}\.telas\.(\d+)\.imagenes/", $key, $matches)) {
                $telaIdx = (int)$matches[1];

                if (!isset($telasConImagenes[$telaIdx])) {
                    $telasConImagenes[$telaIdx] = ['fotos' => []];
                }

                $imagenesFiltered = array_filter((array)$files, function($img) {
                    return $img instanceof UploadedFile;
                });

                if (!empty($imagenesFiltered)) {
                    $telasConImagenes[$telaIdx]['fotos'] = array_values($imagenesFiltered);
                }
            }
        }

        return $telasConImagenes;
    }

    /**
     * Procesar imágenes de procesos desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @param array $procesos - Array de procesos
     * @return array - Procesos con imágenes adjuntas
     */
    public function extraerImagenesProcesos(Request $request, int $itemIndex, array $procesos): array
    {
        $allFiles = $request->allFiles();
        $procesosConImagenes = [];

        foreach ($procesos as $tipoProceso => $procesoData) {
            $procesosConImagenes[$tipoProceso] = $procesoData;
            $procesosConImagenes[$tipoProceso]['imagenes'] = [];

            // Buscar imágenes en FormData
            foreach ($allFiles as $key => $files) {
                if (preg_match("/^items\.{$itemIndex}\.procesos\.{$tipoProceso}\.imagenes/", $key)) {
                    $imagenesFiltered = array_filter((array)$files, function($img) {
                        return $img instanceof UploadedFile;
                    });

                    if (!empty($imagenesFiltered)) {
                        $procesosConImagenes[$tipoProceso]['imagenes'] = array_values($imagenesFiltered);
                    }
                }
            }
        }

        return $procesosConImagenes;
    }

    /**
     * Procesar imágenes de EPP desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @return UploadedFile[]
     */
    public function extraerImagenesEpp(Request $request, int $itemIndex): array
    {
        $imagenKey = "items.{$itemIndex}.epp_imagenes";
        $imagenesDelEpp = $request->file($imagenKey) ?? [];

        if (!is_array($imagenesDelEpp)) {
            $imagenesDelEpp = [$imagenesDelEpp];
        }

        return array_filter($imagenesDelEpp, function($img) {
            return $img instanceof UploadedFile;
        });
    }

    /**
     * Reconstruir procesos desde input() con datos parseados
     * @param Request $request
     * @param int $itemIndex
     * @return array
     */
    public function reconstruirProcesos(Request $request, int $itemIndex): array
    {
        $procesosReconstruidos = [];

        // Obtener datos de procesos desde input()
        $items_input = $request->input('items');
        $prendas = $request->input('prendas');

        $procesosDatos = null;
        if ($items_input && isset($items_input[$itemIndex]) && isset($items_input[$itemIndex]['procesos'])) {
            $procesosDatos = $items_input[$itemIndex]['procesos'];
        } elseif ($prendas && isset($prendas[$itemIndex]) && isset($prendas[$itemIndex]['procesos'])) {
            $procesosDatos = $prendas[$itemIndex]['procesos'];
        }

        if (!$procesosDatos) {
            return [];
        }

        foreach ($procesosDatos as $tipoProceso => $procesoData) {
            $datosProceso = [];

            // Copiar campos básicos
            if (isset($procesoData['tipo'])) {
                $datosProceso['tipo'] = $procesoData['tipo'];
            }
            if (isset($procesoData['ubicaciones'])) {
                $datosProceso['ubicaciones'] = is_string($procesoData['ubicaciones']) 
                    ? json_decode($procesoData['ubicaciones'], true) 
                    : $procesoData['ubicaciones'];
            }
            if (isset($procesoData['observaciones'])) {
                $datosProceso['observaciones'] = $procesoData['observaciones'];
            }

            // Procesar tallas
            $datosProceso['tallas'] = [];
            if (isset($procesoData['tallas_dama'])) {
                $tallasDama = is_string($procesoData['tallas_dama']) 
                    ? json_decode($procesoData['tallas_dama'], true) 
                    : $procesoData['tallas_dama'];
                $datosProceso['tallas']['dama'] = $tallasDama ?? [];
            }
            if (isset($procesoData['tallas_caballero'])) {
                $tallasCapallero = is_string($procesoData['tallas_caballero']) 
                    ? json_decode($procesoData['tallas_caballero'], true) 
                    : $procesoData['tallas_caballero'];
                $datosProceso['tallas']['caballero'] = $tallasCapallero ?? [];
            }

            $procesosReconstruidos[$tipoProceso] = $datosProceso;
        }

        return $procesosReconstruidos;
    }
}
