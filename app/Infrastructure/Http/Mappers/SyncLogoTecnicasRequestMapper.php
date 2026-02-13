<?php

namespace App\Infrastructure\Http\Mappers;

use App\Application\Cotizacion\DTOs\SyncLogoTecnicasDTO;
use Illuminate\Http\Request;

final class SyncLogoTecnicasRequestMapper
{
    public function map(Request $request, int $cotizacionId): SyncLogoTecnicasDTO
    {
        $tipoCotizacion = $request->input('tipo_cotizacion');
        $tipoVenta = $request->input('tipo_venta_paso3') ?? $request->input('tipo_venta');

        $observacionesGenerales = $request->input('observaciones_generales', []);
        if (is_string($observacionesGenerales)) {
            $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
        }
        if (!is_array($observacionesGenerales)) {
            $observacionesGenerales = [];
        }

        $tecnicasAgregadasPresent = $request->has('logo.tecnicas_agregadas') || $request->has('logo[tecnicas_agregadas]');
        $tecnicasAgregadas = $request->input('logo.tecnicas_agregadas', $request->input('logo[tecnicas_agregadas]', '[]'));
        if (is_string($tecnicasAgregadas)) {
            $tecnicasAgregadas = json_decode($tecnicasAgregadas, true) ?? [];
        }
        if (!is_array($tecnicasAgregadas)) {
            $tecnicasAgregadas = [];
        }

        $allFiles = $request->allFiles();

        $logoArchivos = [];
        if (isset($allFiles['logo']) && is_array($allFiles['logo']) && isset($allFiles['logo']['imagenes'])) {
            $logoArchivos = $allFiles['logo']['imagenes'];
        } else {
            $logoArchivos = $request->file('logo.imagenes') ?? [];
        }
        if ($logoArchivos instanceof \Illuminate\Http\UploadedFile) {
            $logoArchivos = [$logoArchivos];
        }
        if (!is_array($logoArchivos)) {
            $logoArchivos = [];
        }

        $imagenesPaso3Files = $request->file('logo.imagenes_paso3');
        $imagenesPaso3Archivos = [];
        if ($imagenesPaso3Files && is_array($imagenesPaso3Files)) {
            $this->flattenFiles($imagenesPaso3Files, $imagenesPaso3Archivos, 'logo[imagenes_paso3]');
        }

        $logoFotosGuardadas = $request->input('logo_fotos_guardadas', []);
        if (!is_array($logoFotosGuardadas)) {
            $logoFotosGuardadas = $logoFotosGuardadas ? [$logoFotosGuardadas] : [];
        }

        $logoFotosExistentes = $request->input('logo_fotos_existentes', []);
        if (!is_array($logoFotosExistentes)) {
            $logoFotosExistentes = $logoFotosExistentes ? [$logoFotosExistentes] : [];
        }

        return new SyncLogoTecnicasDTO(
            cotizacionId: $cotizacionId,
            tipoCotizacion: is_string($tipoCotizacion) ? $tipoCotizacion : null,
            tipoVenta: is_string($tipoVenta) ? $tipoVenta : null,
            observacionesGenerales: $observacionesGenerales,
            tecnicasAgregadas: $tecnicasAgregadas,
            tecnicasAgregadasPresent: $tecnicasAgregadasPresent,
            logoArchivos: $logoArchivos,
            imagenesPaso3Archivos: $imagenesPaso3Archivos,
            logoFotosGuardadas: $logoFotosGuardadas,
            logoFotosExistentes: $logoFotosExistentes,
        );
    }

    private function flattenFiles(array $archivos, array &$resultado, string $prefijo = ''): void
    {
        foreach ($archivos as $key => $valor) {
            $nuevaLlave = $prefijo . '[' . $key . ']';

            if ($valor instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $resultado[$nuevaLlave] = $valor;
            } elseif (is_array($valor)) {
                $this->flattenFiles($valor, $resultado, $nuevaLlave);
            }
        }
    }
}
