<?php

namespace App\Application\Cotizacion\Services;

use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GenerarNumeroCotizacionService
 *
 * Servicio para generar números de cotización únicos y consecutivos
 * Maneja la concurrencia usando database locking (FOR UPDATE)
 */
final class GenerarNumeroCotizacionService
{
    /**
     * Generar el próximo número de cotización para un asesor
     *
     * Usa transaction con lock para evitar race conditions
     * cuando dos solicitudes simultáneas crean cotizaciones
     *
     * @param UserId $usuarioId
     * @return int Número único y consecutivo para el asesor
     */
    public function generarProxNumeroCotizacion(UserId $usuarioId): int
    {
        $usuarioIdValue = $usuarioId->valor();
        $intentos = 5;
        $ultimoError = null;

        for ($intento = 1; $intento <= $intentos; $intento++) {
            try {
                return DB::transaction(function () use ($usuarioIdValue) {
                    // Usar SELECT FOR UPDATE para bloquear la fila
                    // y evitar que otro request obtenga el mismo número
                    $ultimaCotizacion = DB::table('cotizaciones')
                        ->where('asesor_id', $usuarioIdValue)
                        ->where('es_borrador', false)
                        ->orderBy('id', 'desc')
                        ->lockForUpdate()
                        ->first(['numero_cotizacion']);

                    // Si existe cotización, incrementar el número
                    if ($ultimaCotizacion && !is_null($ultimaCotizacion->numero_cotizacion)) {
                        // Extraer el número de la cadena (ej: "COT-00001" → 1)
                        $numeroCotizacion = $this->extraerNumero($ultimaCotizacion->numero_cotizacion) + 1;
                    } else {
                        // Primera cotización del asesor
                        $numeroCotizacion = 1;
                    }

                    Log::info('GenerarNumeroCotizacionService: Número generado', [
                        'usuario_id' => $usuarioIdValue,
                        'numero' => $numeroCotizacion,
                    ]);

                    return $numeroCotizacion;
                });
            } catch (\Throwable $e) {
                $ultimoError = $e;
                
                // Si es un deadlock, reintentar
                if (strpos($e->getMessage(), 'Deadlock') !== false) {
                    Log::warning('GenerarNumeroCotizacionService: Deadlock detectado, reintentando', [
                        'usuario_id' => $usuarioIdValue,
                        'intento' => $intento,
                        'error' => $e->getMessage(),
                    ]);
                    usleep(100000 * $intento); // Esperar con backoff exponencial
                    continue;
                }

                // Otros errores se lanzan inmediatamente
                Log::error('GenerarNumeroCotizacionService: Error al generar número', [
                    'usuario_id' => $usuarioIdValue,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        // Si agotó los reintentos
        Log::error('GenerarNumeroCotizacionService: Agotados los reintentos', [
            'usuario_id' => $usuarioIdValue,
            'ultmo_error' => $ultimoError?->getMessage(),
        ]);

        throw $ultimoError ?? new \Exception('Error al generar número de cotización después de ' . $intentos . ' intentos');
    }

    /**
     * Extraer el número entero de un número de cotización formateado
     *
     * Ejemplos:
     * - "COT-00001" → 1
     * - "1" → 1
     * - "COT-00123" → 123
     *
     * @param string $numeroCotizacion
     * @return int
     */
    private function extraerNumero(string $numeroCotizacion): int
    {
        // Buscar el último número en la cadena
        if (preg_match('/(\d+)$/', $numeroCotizacion, $matches)) {
            return (int) $matches[1];
        }

        // Si no encuentra números, retornar 0
        return 0;
    }

    /**
     * Formatear número de cotización con prefijo
     *
     * @param int $numero
     * @return string Número formateado (ej: "COT-00001")
     */
    public function formatearNumero(int $numero): string
    {
        return 'COT-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generar número de cotización completo (entero + formato)
     *
     * @param UserId $usuarioId
     * @return string Número formateado
     */
    public function generarNumeroCotizacionFormateado(UserId $usuarioId): string
    {
        $numero = $this->generarProxNumeroCotizacion($usuarioId);
        return $this->formatearNumero($numero);
    }
}
