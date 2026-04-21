<?php

namespace App\Application\Cotizacion\Services;

use App\Models\LogoCotizacionTelasPrenda;
use App\Models\LogoCotizacionTecnicaPrenda;
use App\Services\TecnicaImagenService;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Aplicación: Procesar Telas, Colores y Referencias
 * Responsabilidades:
 * - Extraer datos de telas desde la estructura de técnicas
 * - Guardar información de tela por prenda
 * - Procesar imágenes de telas
 * - Vincular telas a prendas cotizadas
 */
class ProcesarTelasBordadoService
{
    private TecnicaImagenService $imagenService;

    public function __construct()
    {
        $this->imagenService = new TecnicaImagenService();
    }

    /**
     * Ejecutar: Procesar telas desde la estructura de técnicas
     */
    public function ejecutar(int $logoCotizacionId, array $tecnicas): void
    {
        Log::info('Procesando telas de técnicas', [
            'logo_cotizacion_id' => $logoCotizacionId,
            'tecnicas_count' => count($tecnicas),
        ]);

        // Limpiar telas existentes para evitar duplicados en actualizaciones
        LogoCotizacionTelasPrenda::where('logo_cotizacion_id', $logoCotizacionId)->delete();

        $telasProcesadas = 0;

        foreach ($tecnicas as $tecnicaIdx => $tecnicaData) {
            $prendas = $tecnicaData['prendas'] ?? [];

            foreach ($prendas as $prendaIdx => $prendaData) {
                $telasData = $prendaData['telas'] ?? [];

                if (empty($telasData)) {
                    continue;
                }

                // Obtener la prenda técnica guardada
                $prendaTecnica = $this->obtenerPrendaTecnica(
                    $prendaData['nombre_prenda'] ?? null
                );

                if (!$prendaTecnica) {
                    Log::warning('Prenda técnica no encontrada', [
                        'nombre' => $prendaData['nombre_prenda'] ?? 'desconocida',
                    ]);
                    continue;
                }

                // Procesar cada tela
                foreach ($telasData as $tela) {
                    $this->guardarTela(
                        $logoCotizacionId,
                        $prendaTecnica->prenda_cot_id,
                        $tela,
                        $tecnicaIdx,
                        $prendaIdx
                    );

                    $telasProcesadas++;
                }
            }
        }

        Log::info('✓ Telas procesadas', ['count' => $telasProcesadas]);
    }

    /**
     * Guardar información de una tela
     */
    private function guardarTela(
        int $logoCotizacionId,
        int $prendaCotId,
        array $telaData,
        int $tecnicaIdx,
        int $prendaIdx
    ): void {
        try {
            $tela = $telaData['tela'] ?? null;
            $color = $telaData['color'] ?? null;
            $ref = $telaData['ref'] ?? null;

            // Guardar registro de tela-color-referencia
            $telaPrenda = LogoCotizacionTelasPrenda::create([
                'logo_cotizacion_id' => $logoCotizacionId,
                'prenda_cot_id' => $prendaCotId,
                'tela' => $tela,
                'color' => $color,
                'ref' => $ref,
                'img' => null, // Las imágenes se guardan por separado
            ]);

            Log::info('✓ Tela guardada', [
                'tela_id' => $telaPrenda->id,
                'tela' => $tela,
                'color' => $color,
            ]);

        } catch (\Exception $e) {
            Log::error('Error guardando tela', [
                'error' => $e->getMessage(),
                'tecnica' => $tecnicaIdx,
                'prenda' => $prendaIdx,
            ]);
        }
    }

    /**
     * Obtener prenda técnica por nombre
     */
    private function obtenerPrendaTecnica(?string $nombrePrenda): ?LogoCotizacionTecnicaPrenda
    {
        if (!$nombrePrenda) {
            return null;
        }

        return LogoCotizacionTecnicaPrenda::whereHas('prendaCot', function ($query) use ($nombrePrenda) {
            $query->where('nombre_producto', $nombrePrenda);
        })->first();
    }

    /**
     * Guardar imagen de tela asociada a prenda
     */
    public function guardarImagenTela(int $prendaCotId, int $cotizacionId, $archivo): string
    {
        try {
            $rutas = $this->imagenService->guardarImagen(
                $archivo,
                $cotizacionId,
                'TELA'
            );

            Log::info('✓ Imagen de tela guardada', [
                'prenda_id' => $prendaCotId,
                'ruta' => $rutas['ruta_webp'],
            ]);

            return $rutas['ruta_webp'];

        } catch (\Exception $e) {
            Log::error('Error guardando imagen de tela', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
