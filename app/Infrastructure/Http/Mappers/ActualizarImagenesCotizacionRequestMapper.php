<?php

namespace App\Infrastructure\Http\Mappers;

use App\Application\Cotizacion\DTOs\ActualizarImagenesCotizacionDTO;
use Illuminate\Http\Request;

final class ActualizarImagenesCotizacionRequestMapper
{
    public function map(Request $request, array $prendasRecibidas): ActualizarImagenesCotizacionDTO
    {
        $fotosAEliminar = $request->input('fotos_a_eliminar', []);
        if (is_string($fotosAEliminar)) {
            $fotosAEliminar = json_decode($fotosAEliminar, true) ?? [];
        }
        if (!is_array($fotosAEliminar)) {
            $fotosAEliminar = [];
        }

        // Flags de archivos nuevos
        $hayFotosPrendaNuevasPorIndex = [];
        $hayFotosTelaNuevasPorIndex = [];

        $allFiles = $request->allFiles();

        foreach ($prendasRecibidas as $index => $prendaData) {
            $index = (int) $index;

            $fotosArchivos = $request->file("prendas.{$index}.fotos") ?? [];
            if (empty($fotosArchivos)) {
                $fotosArchivos = $allFiles["prendas.{$index}.fotos"] ?? [];
            }
            $hayFotosPrendaNuevasPorIndex[$index] = !empty($fotosArchivos);

            $telasArchivos = $request->file("prendas.{$index}.telas") ?? [];
            if (empty($telasArchivos)) {
                $telasArchivos = $allFiles["prendas.{$index}.telas"] ?? [];
            }
            $hayFotosTelaNuevasPorIndex[$index] = !empty($telasArchivos);
        }

        $logoFotosGuardadas = $request->input('logo_fotos_guardadas', []);
        if (!is_array($logoFotosGuardadas)) {
            $logoFotosGuardadas = $logoFotosGuardadas ? [$logoFotosGuardadas] : [];
        }

        $archivosNuevos = $request->file('logo.imagenes') ?? [];
        if (empty($archivosNuevos) && isset($allFiles['logo']['imagenes'])) {
            $archivosNuevos = $allFiles['logo']['imagenes'];
        }
        if ($archivosNuevos instanceof \Illuminate\Http\UploadedFile) {
            $archivosNuevos = [$archivosNuevos];
        }
        $logoArchivosNuevosCount = is_array($archivosNuevos) ? count($archivosNuevos) : 0;

        return new ActualizarImagenesCotizacionDTO(
            fotosAEliminar: $fotosAEliminar,
            prendasRecibidas: $prendasRecibidas,
            hayFotosPrendaNuevasPorIndex: $hayFotosPrendaNuevasPorIndex,
            hayFotosTelaNuevasPorIndex: $hayFotosTelaNuevasPorIndex,
            logoFotosGuardadas: $logoFotosGuardadas,
            logoArchivosNuevosCount: $logoArchivosNuevosCount,
        );
    }
}
