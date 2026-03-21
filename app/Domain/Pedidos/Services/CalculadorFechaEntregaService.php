<?php

namespace App\Domain\Pedidos\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Domain Service: CalculadorFechaEntregaService
 * 
 * Responsabilidad: Calcular fecha estimada de entrega con días hábiles
 * Patrón: Domain Service
 * 
 * Encapsula la lógica de:
 * - Saltar fines de semana (sábado y domingo)
 * - Saltar festivos colombianos
 * - Contar solo días hábiles
 * - Fallback a suma simple si hay error
 */
class CalculadorFechaEntregaService
{
    /**
     * Calcular fecha estimada sumando días hábiles (excluyendo fin de semana y festivos)
     * 
     * @param Carbon $fechaInicio Fecha inicial
     * @param int $diasHabiles Cantidad de días hábiles a sumar
     * @return Carbon Fecha calculada
     */
    public function calcularConDiasHabiles(Carbon $fechaInicio, int $diasHabiles): Carbon
    {
        try {
            // Cargar festivos colombianos para este año y el próximo
            $festivos = $this->obtenerFestivosColombianosFormateados();

            $fechaActual = $fechaInicio->copy();
            $diasContados = 0;

            // Sumar días hábiles (saltando fines de semana y festivos)
            while ($diasContados < $diasHabiles) {
                $fechaActual->addDay();

                // Saltar si es fin de semana (0 = domingo, 6 = sábado)
                if ($fechaActual->dayOfWeek === 0 || $fechaActual->dayOfWeek === 6) {
                    continue;
                }

                // Saltar si es festivo
                $fechaFormato = $fechaActual->format('d-m');
                if (in_array($fechaFormato, $festivos)) {
                    continue;
                }

                $diasContados++;
            }

            return $fechaActual;
        } catch (\Exception $e) {
            // Fallback: suma simple de días si hay error en cálculo de festivos
            Log::warning('[CalculadorFechaEntregaService] Error calculando fecha con días hábiles, usando fallback', [
                'error' => $e->getMessage(),
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'dias_habiles' => $diasHabiles,
            ]);

            return $fechaInicio->copy()->addDays($diasHabiles);
        }
    }

    /**
     * Obtener festivos colombianos formateados como 'd-m'
     * 
     * @return array Array con formato ['01-01', '06-01', ...]
     */
    private function obtenerFestivosColombianosFormateados(): array
    {
        $festivos = [];

        try {
            // Usar servicio si existe
            if (class_exists('App\Services\FestivosColombiaService')) {
                $festivosList = \App\Services\FestivosColombiaService::obtenerFestivos();

                foreach ($festivosList as $festivo) {
                    if (is_array($festivo)) {
                        $festivos[] = $festivo['fecha'] ?? null;
                    } else if (is_object($festivo)) {
                        $festivos[] = $festivo->fecha ?? null;
                    } else {
                        $festivos[] = $festivo;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('[CalculadorFechaEntregaService] No se pudo cargar festivos colombianos', [
                'error' => $e->getMessage(),
            ]);
        }

        return array_filter($festivos);
    }
}
