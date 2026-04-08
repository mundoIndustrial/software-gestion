<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * CarteraSugerenciasService
 * 
 * Servicio centralizado para obtener sugerencias de filtros en cartera
 * Elimina duplicación de código entre pendientes, rechazados, aprobados y anulados
 */
class CarteraSugerenciasService
{
    /**
     * Obtener sugerencias de clientes
     */
    public function obtenerClientesSugerencias(string $estado, string $busqueda = ''): array
    {
        try {
            $query = DB::table('pedidos_produccion')
                ->select('cliente')
                ->where('estado', '=', $estado)
                ->whereNotNull('cliente')
                ->where('cliente', '!=', '');

            if (!empty($busqueda)) {
                $query->whereRaw('LOWER(cliente) LIKE ?', ['%' . strtolower($busqueda) . '%']);
            }

            $clientes = $query->distinct()
                ->limit(10)
                ->pluck('cliente')
                ->toArray();

            return $this->ordenarPorRelevancia($clientes, $busqueda);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerClientesSugerencias', [
                'estado' => $estado,
                'busqueda' => $busqueda,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener sugerencias de números de pedido
     */
    public function obtenerNumerosSugerencias(string $estado, string $busqueda = ''): array
    {
        try {
            $query = DB::table('pedidos_produccion')
                ->select('numero_pedido')
                ->where('estado', '=', $estado)
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '!=', '');

            if (!empty($busqueda)) {
                $query->whereRaw('CAST(numero_pedido AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
            }

            $numeros = $query->distinct()
                ->limit(10)
                ->pluck('numero_pedido')
                ->toArray();

            return $this->ordenarPorRelevanciaNumeros($numeros, $busqueda);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNumerosSugerencias', [
                'estado' => $estado,
                'busqueda' => $busqueda,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener sugerencias de fechas según el campo de fecha del estado
     */
    public function obtenerFechasSugerencias(string $estado, string $busqueda = ''): array
    {
        try {
            // Determinar el campo de fecha según el estado
            $campoFecha = $this->obtenerCampoFecha($estado);

            $query = DB::table('pedidos_produccion')
                ->select($campoFecha)
                ->where('estado', '=', $estado)
                ->whereNotNull($campoFecha);

            if (!empty($busqueda)) {
                $query->whereRaw('DATE_FORMAT(' . $campoFecha . ', "%d/%m/%Y") LIKE ?', 
                    ['%' . strtolower($busqueda) . '%']);
            }

            $fechas = $query->distinct()
                ->limit(10)
                ->pluck($campoFecha)
                ->toArray();

            return $this->formatearYOrdenarFechas($fechas, $busqueda);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerFechasSugerencias', [
                'estado' => $estado,
                'busqueda' => $busqueda,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Determinar el campo de fecha correcto según el estado
     */
    private function obtenerCampoFecha(string $estado): string
    {
        return match($estado) {
            'pendiente_cartera' => 'created_at',
            'RECHAZADO_CARTERA' => 'rechazado_por_cartera_en',
            'PENDIENTE_SUPERVISOR', 'aprobado' => 'aprobado_por_cartera_en',
            'Anulada' => 'updated_at',
            default => 'created_at',
        };
    }

    /**
     * Ordenar por relevancia (coincidencias al principio primero)
     */
    private function ordenarPorRelevancia(array $items, string $busqueda): array
    {
        usort($items, function ($a, $b) use ($busqueda) {
            $aLower = strtolower($a);
            $bLower = strtolower($b);
            $busquedaLower = strtolower($busqueda);

            // Coincidencia exacta al principio
            if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                return -1;
            }
            if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                return 1;
            }

            // Coincidencia exacta
            if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                return -1;
            }
            if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                return 1;
            }

            // Orden alfabético
            return strcasecmp($a, $b);
        });

        return $items;
    }

    /**
     * Ordenar números por relevancia
     */
    private function ordenarPorRelevanciaNumeros(array $numeros, string $busqueda): array
    {
        usort($numeros, function ($a, $b) use ($busqueda) {
            $aLower = strtolower((string)$a);
            $bLower = strtolower((string)$b);
            $busquedaLower = strtolower($busqueda);

            // Coincidencia exacta al principio
            if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                return -1;
            }
            if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                return 1;
            }

            // Coincidencia exacta
            if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                return -1;
            }
            if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                return 1;
            }

            // Orden numérico
            return (int)$a - (int)$b;
        });

        return $numeros;
    }

    /**
     * Formatear y ordenar fechas
     */
    private function formatearYOrdenarFechas(array $fechas, string $busqueda): array
    {
        $fechasFormateadas = [];

        foreach ($fechas as $fecha) {
            try {
                $date = new \DateTime($fecha);
                $fechasFormateadas[] = $date->format('d/m/Y');
            } catch (\Exception $e) {
                \Log::warning('Error al formatear fecha', ['fecha' => $fecha]);
                // Usar valor original si hay error
                $fechasFormateadas[] = (string)$fecha;
            }
        }

        // Ordenar por relevancia y luego por fecha (más reciente primero)
        usort($fechasFormateadas, function ($a, $b) use ($busqueda) {
            $aLower = strtolower($a);
            $bLower = strtolower($b);
            $busquedaLower = strtolower($busqueda);

            // Coincidencia exacta al principio
            if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                return -1;
            }
            if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                return 1;
            }

            // Coincidencia exacta
            if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                return -1;
            }
            if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                return 1;
            }

            // Ordenar por fecha (más reciente primero)
            try {
                $dateA = \DateTime::createFromFormat('d/m/Y', $a);
                $dateB = \DateTime::createFromFormat('d/m/Y', $b);
                if ($dateA && $dateB) {
                    return $dateB <=> $dateA;
                }
            } catch (\Exception $e) {
                \Log::warning('Error al comparar fechas', ['fechaA' => $a, 'fechaB' => $b]);
            }

            return strcmp($b, $a);
        });

        return $fechasFormateadas;
    }
}
