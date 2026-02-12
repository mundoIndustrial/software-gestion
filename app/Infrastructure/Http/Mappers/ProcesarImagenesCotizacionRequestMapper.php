<?php

namespace App\Infrastructure\Http\Mappers;

use App\Application\Cotizacion\DTOs\ProcesarImagenesCotizacionDTO;
use Illuminate\Http\Request;

final class ProcesarImagenesCotizacionRequestMapper
{
    public function map(Request $request, int $cotizacionId): ProcesarImagenesCotizacionDTO
    {
        $allData = $request->all();
        $prendas = $allData['prendas'] ?? $request->input('prendas', []);
        if (is_string($prendas)) {
            $prendas = json_decode($prendas, true) ?? [];
        }
        if (!is_array($prendas)) {
            $prendas = [];
        }

        $allFiles = $request->allFiles();

        $prendaFotosArchivosPorIndex = [];
        $prendaFotosGuardadasPorIndex = [];

        $telasArchivosPorPrendaIndex = [];
        $telasFotosExistentesPorPrendaIndex = [];

        foreach ($prendas as $index => $prenda) {
            // Fotos prenda
            $fotosArchivos = $request->file("prendas.{$index}.fotos") ?? [];
            if (empty($fotosArchivos)) {
                $fotosArchivos = $request->file("prendas.{$index}.fotos.0") ?? [];
            }
            if (empty($fotosArchivos)) {
                $fotosArchivos = $allFiles["prendas.{$index}.fotos"] ?? [];
            }
            if ($fotosArchivos instanceof \Illuminate\Http\UploadedFile) {
                $fotosArchivos = [$fotosArchivos];
            }
            if (!is_array($fotosArchivos)) {
                $fotosArchivos = [];
            }
            $prendaFotosArchivosPorIndex[(int) $index] = $fotosArchivos;

            $fotosGuardadas = $request->input("prendas.{$index}.fotos_guardadas") ?? [];
            if (is_string($fotosGuardadas)) {
                $fotosGuardadas = json_decode($fotosGuardadas, true) ?? [];
            }
            if (!is_array($fotosGuardadas)) {
                $fotosGuardadas = [];
            }
            $prendaFotosGuardadasPorIndex[(int) $index] = $fotosGuardadas;

            // Telas: estructura anidada desde allFiles['prendas'][index]['telas'][telaIndex]
            $telasArchivosPorPrendaIndex[(int) $index] = [];
            $telasFotosExistentesPorPrendaIndex[(int) $index] = [];

            if (isset($allFiles['prendas']) && is_array($allFiles['prendas']) && isset($allFiles['prendas'][$index])) {
                $prendaFiles = $allFiles['prendas'][$index];
                if (isset($prendaFiles['telas']) && is_array($prendaFiles['telas'])) {
                    foreach ($prendaFiles['telas'] as $telaIndex => $telaData) {
                        // archivos
                        $archivos = [];
                        if (isset($telaData['fotos'])) {
                            $archivos = $telaData['fotos'];
                        }
                        if ($archivos instanceof \Illuminate\Http\UploadedFile) {
                            $archivos = [$archivos];
                        }
                        if (!is_array($archivos)) {
                            $archivos = [];
                        }
                        $telasArchivosPorPrendaIndex[(int) $index][(int) $telaIndex] = $archivos;

                        // existentes
                        $existentes = $request->input("prendas.{$index}.telas.{$telaIndex}.fotos_existentes") ?? ($telaData['fotos_existentes'] ?? []);
                        if (is_string($existentes)) {
                            $existentes = json_decode($existentes, true) ?? [];
                        }
                        if (!is_array($existentes)) {
                            $existentes = [];
                        }
                        $telasFotosExistentesPorPrendaIndex[(int) $index][(int) $telaIndex] = $existentes;
                    }
                }
            }
        }

        return new ProcesarImagenesCotizacionDTO(
            cotizacionId: $cotizacionId,
            prendas: $prendas,
            prendaFotosArchivosPorIndex: $prendaFotosArchivosPorIndex,
            prendaFotosGuardadasPorIndex: $prendaFotosGuardadasPorIndex,
            telasArchivosPorPrendaIndex: $telasArchivosPorPrendaIndex,
            telasFotosExistentesPorPrendaIndex: $telasFotosExistentesPorPrendaIndex,
        );
    }
}
