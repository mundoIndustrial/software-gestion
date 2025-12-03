<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Producción Calculadora Service
 * 
 * Maneja todos los cálculos relacionados con producción, horas y operarios.
 * Responsable de:
 * - Calcular seguimiento de módulos
 * - Calcular producción por horas
 * - Calcular producción por operarios
 */
class ProduccionCalculadoraService extends BaseService
{
    /**
     * Calcular seguimiento de módulos
     * 
     * Extrae datos de módulos de registros y calcula
     * totales por módulo y por hora.
     */
    public function calcularSeguimientoModulos($registros)
    {
        $this->log('Iniciando cálculo de seguimiento de módulos', [
            'total_registros' => count($registros),
        ]);

        // Obtener módulos únicos de los registros y ordenarlos
        $modulosDisponibles = $registros->pluck('modulo')->unique()->values()->toArray();

        // Normalizar los nombres de módulos (trim espacios, uppercase consistente)
        $modulosDisponibles = array_map(function($mod) {
            return strtoupper(trim($mod));
        }, $modulosDisponibles);

        // Remover duplicados después de normalizar
        $modulosDisponibles = array_unique($modulosDisponibles);

        // Filtrar módulos vacíos
        $modulosDisponibles = array_filter($modulosDisponibles, function($mod) {
            return !empty(trim($mod));
        });
        $modulosDisponibles = array_values($modulosDisponibles); // reindex

        // Ordenar los módulos
        sort($modulosDisponibles);

        // Si no hay módulos dinámicos, usar los módulos por defecto
        if (empty($modulosDisponibles)) {
            $modulosDisponibles = ['MÓDULO 1', 'MÓDULO 2', 'MÓDULO 3'];
        }

        // Inicializar estructuras de datos
        $dataPorHora = [];
        $totales = ['modulos' => []];

        // INICIALIZAR todos los módulos en totales
        foreach ($modulosDisponibles as $modulo) {
            $totales['modulos'][$modulo] = [
                'prendas' => 0,
                'tiempo_ciclo_sum' => 0,
                'numero_operarios_sum' => 0,
                'porcion_tiempo_sum' => 0,
                'tiempo_parada_no_programada_sum' => 0,
                'tiempo_para_programada_sum' => 0,
                'tiempo_disponible_sum' => 0,
                'meta_sum' => 0,
                'count' => 0
            ];
        }

        // Acumular datos por hora y módulo
        foreach ($registros as $registro) {
            // Handle both relationship (object) and direct field (string)
            $hora = is_object($registro->hora) ? $registro->hora->hora : ($registro->hora ?? 'Sin hora');
            $hora = !empty(trim($hora)) ? trim($hora) : 'Sin hora';
            $modulo = !empty(trim($registro->modulo)) ? strtoupper(trim($registro->modulo)) : 'SIN MÓDULO';

            if (!isset($dataPorHora[$hora])) {
                $dataPorHora[$hora] = ['modulos' => []];
            }

            if (!isset($dataPorHora[$hora]['modulos'][$modulo])) {
                $dataPorHora[$hora]['modulos'][$modulo] = [
                    'prendas' => 0,
                    'tiempo_ciclo_sum' => 0,
                    'numero_operarios_sum' => 0,
                    'porcion_tiempo_sum' => 0,
                    'tiempo_parada_no_programada_sum' => 0,
                    'tiempo_para_programada_sum' => 0,
                    'tiempo_disponible_sum' => 0,
                    'meta_sum' => 0,
                    'count' => 0
                ];
            }

            $dataPorHora[$hora]['modulos'][$modulo]['prendas'] += floatval($registro->cantidad ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_ciclo_sum'] += floatval($registro->tiempo_ciclo ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['numero_operarios_sum'] += floatval($registro->numero_operarios ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['porcion_tiempo_sum'] += floatval($registro->porcion_tiempo ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += floatval($registro->tiempo_parada_no_programada ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_para_programada_sum'] += floatval($registro->tiempo_para_programada ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['count']++;

            // Inicializar módulo en totales si no existe
            if (!isset($totales['modulos'][$modulo])) {
                $totales['modulos'][$modulo] = [
                    'prendas' => 0,
                    'tiempo_ciclo_sum' => 0,
                    'numero_operarios_sum' => 0,
                    'porcion_tiempo_sum' => 0,
                    'tiempo_parada_no_programada_sum' => 0,
                    'tiempo_para_programada_sum' => 0,
                    'tiempo_disponible_sum' => 0,
                    'meta_sum' => 0,
                    'count' => 0
                ];
            }

            // Acumular totales generales
            $totales['modulos'][$modulo]['prendas'] += floatval($registro->cantidad ?? 0);
            $totales['modulos'][$modulo]['tiempo_ciclo_sum'] += floatval($registro->tiempo_ciclo ?? 0);
            $totales['modulos'][$modulo]['numero_operarios_sum'] += floatval($registro->numero_operarios ?? 0);
            $totales['modulos'][$modulo]['porcion_tiempo_sum'] += floatval($registro->porcion_tiempo ?? 0);
            $totales['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += floatval($registro->tiempo_parada_no_programada ?? 0);
            $totales['modulos'][$modulo]['tiempo_para_programada_sum'] += floatval($registro->tiempo_para_programada ?? 0);
            $totales['modulos'][$modulo]['count']++;

            // Usar la meta que ya está calculada en el registro
            $meta_registro = floatval($registro->meta ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['meta_sum'] += $meta_registro;
            $totales['modulos'][$modulo]['meta_sum'] += $meta_registro;
        }

        // Calcular meta y eficiencia por hora
        foreach ($dataPorHora as $hora => &$data) {
            foreach ($data['modulos'] as $modulo => &$modData) {
                if ($modData['count'] > 0) {
                    $meta = $modData['meta_sum'];
                    $modData['meta_promedio'] = $modData['count'] > 0 ? $meta / $modData['count'] : 0;

                    if ($meta > 0) {
                        $modData['eficiencia'] = round(($modData['prendas'] / $meta) * 100, 1);
                    } else {
                        $modData['eficiencia'] = 0;
                    }

                    // Calcular tiempo disponible
                    $modData['tiempo_disponible'] = $modData['porcion_tiempo_sum'] - $modData['tiempo_parada_no_programada_sum'];
                } else {
                    $modData['eficiencia'] = 0;
                    $modData['meta_promedio'] = 0;
                    $modData['tiempo_disponible'] = 0;
                }
            }
            unset($modData);
        }
        unset($data);

        // Calcular eficiencia y meta promedio general por módulo
        foreach ($totales['modulos'] as $modulo => &$modData) {
            if ($modData['count'] > 0) {
                $meta = $modData['meta_sum'];
                $modData['meta_promedio'] = $meta / $modData['count'];

                if ($meta > 0) {
                    $modData['eficiencia'] = round(($modData['prendas'] / $meta) * 100, 1);
                } else {
                    $modData['eficiencia'] = 0;
                }

                // Calcular tiempo disponible
                $modData['tiempo_disponible'] = $modData['porcion_tiempo_sum'] - $modData['tiempo_parada_no_programada_sum'];
            } else {
                $modData['eficiencia'] = 0;
                $modData['meta_promedio'] = 0;
                $modData['tiempo_disponible'] = 0;
            }
        }
        unset($modData);

        // Ordenar módulos
        ksort($dataPorHora);

        $this->log('Seguimiento de módulos calculado exitosamente', [
            'horas_unicas' => count($dataPorHora),
            'modulos_totales' => count($totales['modulos']),
        ]);

        return [
            'dataPorHora' => $dataPorHora,
            'totales' => $totales
        ];
    }

    /**
     * Calcular producción por horas
     */
    public function calcularProduccionPorHoras($registrosCorte)
    {
        $this->log('Iniciando cálculo de producción por horas', [
            'total_registros' => count($registrosCorte),
        ]);

        $horasData = [];

        foreach ($registrosCorte as $registro) {
            $horaOriginal = $registro->hora ? $registro->hora->hora : 'SIN HORA';
            
            // Formatear la hora como "HORA 1", "HORA 2", etc.
            if ($horaOriginal !== 'SIN HORA' && is_numeric($horaOriginal)) {
                $hora = 'HORA ' . $horaOriginal;
            } else {
                $hora = $horaOriginal;
            }
            
            if (!isset($horasData[$hora])) {
                $horasData[$hora] = [
                    'hora' => $hora,
                    'cantidad' => 0,
                    'meta' => 0,
                    'eficiencia' => 0,
                    'tiempo_disponible' => 0
                ];
            }
            $horasData[$hora]['cantidad'] += $registro->cantidad ?? 0;
            $horasData[$hora]['meta'] += $registro->meta ?? 0;
            $horasData[$hora]['tiempo_disponible'] += $registro->tiempo_disponible ?? 0;
        }

        // Calcular eficiencia para cada hora
        foreach ($horasData as &$horaData) {
            if ($horaData['meta'] > 0) {
                $horaData['eficiencia'] = round(($horaData['cantidad'] / $horaData['meta']) * 100, 1);
            } else {
                $horaData['eficiencia'] = 0;
            }
        }

        // Ordenar por hora (asumiendo formato HORA XX)
        uasort($horasData, function($a, $b) {
            $numA = (int) preg_replace('/\D/', '', $a['hora']);
            $numB = (int) preg_replace('/\D/', '', $b['hora']);
            return $numA <=> $numB;
        });

        $this->log('Producción por horas calculada', [
            'total_horas' => count($horasData),
        ]);

        return array_values($horasData);
    }

    /**
     * Calcular producción por operarios
     */
    public function calcularProduccionPorOperarios($registrosCorte)
    {
        $this->log('Iniciando cálculo de producción por operarios', [
            'total_registros' => count($registrosCorte),
        ]);

        $operariosData = [];

        foreach ($registrosCorte as $registro) {
            $operario = $registro->operario ? $registro->operario->name : 'SIN OPERARIO';
            if (!isset($operariosData[$operario])) {
                $operariosData[$operario] = [
                    'operario' => $operario,
                    'cantidad' => 0,
                    'meta' => 0,
                    'eficiencia' => 0
                ];
            }
            $operariosData[$operario]['cantidad'] += $registro->cantidad ?? 0;
            $operariosData[$operario]['meta'] += $registro->meta ?? 0;
        }

        // Calcular eficiencia para cada operario
        foreach ($operariosData as &$operarioData) {
            if ($operarioData['meta'] > 0) {
                $operarioData['eficiencia'] = round(($operarioData['cantidad'] / $operarioData['meta']) * 100, 1);
            } else {
                $operarioData['eficiencia'] = 0;
            }
        }

        // Ordenar alfabéticamente por operario
        ksort($operariosData);

        $this->log('Producción por operarios calculada', [
            'total_operarios' => count($operariosData),
        ]);

        return array_values($operariosData);
    }
}
