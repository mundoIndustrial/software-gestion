<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * FormatterService
 * 
 * Procesa y formatea inputs del formulario para cotizaciones
 * Mantiene la lógica de transformación separada del controlador
 */
class FormatterService
{
    /**
     * Procesar inputs del formulario de cotización
     * 
     * @param array $validado Datos validados del FormRequest
     * @return array Datos formateados y listos para usar
     * @throws \Exception
     */
    public function procesarInputsFormulario(array $validado): array
    {
        try {
            // Procesar observaciones generales con su tipo y valor
            $observacionesGenerales = [];
            $obsTextos = $validado['observaciones_generales'] ?? [];
            $obsChecks = $validado['observaciones_check'] ?? [];
            $obsValores = $validado['observaciones_valor'] ?? [];
            
            foreach ($obsTextos as $index => $texto) {
                if (!empty($texto)) {
                    $checkValue = $obsChecks[$index] ?? null;
                    $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
                    $valor = ($tipo === 'texto') ? ($obsValores[$index] ?? '') : '';
                    
                    $observacionesGenerales[] = [
                        'texto' => $texto,
                        'tipo' => $tipo,
                        'valor' => $valor
                    ];
                }
            }
            
            return [
                'cliente' => $validado['cliente'] ?? '',
                'productos' => $validado['productos'] ?? [],
                'tecnicas' => $validado['tecnicas'] ?? [],
                'ubicaciones' => $this->procesarUbicaciones($validado['ubicaciones'] ?? []),
                'imagenes' => $validado['imagenes'] ?? [],
                'especificaciones' => $this->procesarEspecificaciones($validado['especificaciones'] ?? []),
                'observaciones_generales' => $observacionesGenerales,
                'observaciones_tecnicas' => $validado['observaciones_tecnicas'] ?? null,
                'tipo_cotizacion' => $validado['tipo_cotizacion'] ?? $validado['tipo_venta'] ?? null,
                'tipo_venta' => $validado['tipo_venta'] ?? $validado['tipo_cotizacion'] ?? null,
                'tipo_cotizacion_codigo' => $validado['tipo_cotizacion'] ?? 'P', // Usar lo que venga, por defecto 'P'
                'numero_cotizacion' => $validado['numero_cotizacion'] ?? null,
            ];
        } catch (\Exception $e) {
            \Log::error('Error procesando inputs del formulario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error al procesar datos del formulario: ' . $e->getMessage());
        }
    }

    /**
     * Procesar ubicaciones para garantizar formato consistente
     * 
     * @param array $ubicacionesRaw
     * @return array
     */
    public function procesarUbicaciones(array $ubicacionesRaw): array
    {
        try {
            $ubicaciones = [];

            if (!is_array($ubicacionesRaw)) {
                return $ubicaciones;
            }

            foreach ($ubicacionesRaw as $item) {
                if (is_array($item) && isset($item['seccion'])) {
                    $ubicaciones[] = $item;
                } else {
                    $ubicaciones[] = [
                        'seccion' => 'GENERAL',
                        'ubicaciones_seleccionadas' => [$item]
                    ];
                }
            }

            return $ubicaciones;
        } catch (\Exception $e) {
            \Log::error('Error procesando ubicaciones', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Procesar especificaciones
     * 
     * @param array|mixed $especificacionesRaw
     * @return array
     */
    public function procesarEspecificaciones($especificacionesRaw): array
    {
        try {
            if (!is_array($especificacionesRaw)) {
                return (array) $especificacionesRaw;
            }

            return $especificacionesRaw;
        } catch (\Exception $e) {
            \Log::error('Error procesando especificaciones', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Procesar observaciones
     * 
     * @param Request $request
     * @return array
     */
    public function procesarObservaciones(Request $request): array
    {
        try {
            $observacionesTexto = $request->input('observaciones_generales', []);
            $observacionesCheck = $request->input('observaciones_check', []);
            $observacionesValor = $request->input('observaciones_valor', []);

            $observacionesGenerales = [];

            foreach ($observacionesTexto as $index => $obs) {
                if (!empty($obs)) {
                    $checkValue = $observacionesCheck[$index] ?? null;
                    $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
                    $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';

                    $observacionesGenerales[] = [
                        'texto' => $obs,
                        'tipo' => $tipo,
                        'valor' => $valor
                    ];
                }
            }

            return $observacionesGenerales;
        } catch (\Exception $e) {
            \Log::error('Error procesando observaciones', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
