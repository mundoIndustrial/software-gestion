<?php

namespace App\Application\Cotizacion\Services;

use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GenerarNumeroCotizacionService
 *
 * Servicio para generar números de cotización únicos y consecutivos
 * Maneja la concurrencia usando database locking (SELECT FOR UPDATE)
 * Usa una tabla de secuencias por asesor para evitar race conditions
 */
final class GenerarNumeroCotizacionService
{
    /**
     * Tabla de secuencias por asesor
     */
    private const TABLA_SECUENCIAS = 'cotizacion_secuencias';

    /**
     * Generar el próximo número de cotización para un asesor
     *
     * Usa una secuencia GLOBAL única con SELECT FOR UPDATE para garantizar
     * números únicos y consecutivos sin race conditions entre todos los asesores
     *
     * @param UserId $usuarioId
     * @return int Número único y consecutivo (global)
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
                    // Garantiza que solo un request obtenga el siguiente número
                    $secuencia = DB::table(self::TABLA_SECUENCIAS)
                        ->where('tipo', 'global')
                        ->lockForUpdate()
                        ->first();

                    if (!$secuencia) {
                        // Inicializar si no existe
                        DB::table(self::TABLA_SECUENCIAS)->insert([
                            'tipo' => 'global',
                            'siguiente_numero' => 2,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $proximoNumero = 1;
                    } else {
                        // Obtener el número actual y incrementar
                        $proximoNumero = $secuencia->siguiente_numero;
                        DB::table(self::TABLA_SECUENCIAS)
                            ->where('tipo', 'global')
                            ->update([
                                'siguiente_numero' => $proximoNumero + 1,
                                'updated_at' => now(),
                            ]);
                    }

                    Log::info('GenerarNumeroCotizacionService: Número global generado', [
                        'usuario_id' => $usuarioIdValue,
                        'numero' => $proximoNumero,
                    ]);

                    return $proximoNumero;
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

