<?php

namespace App\Services;

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

/**
 * RegistroOrdenPrendaService
 * 
 * Responsabilidad: Gestión de prendas (creación, actualización, parseo de descripción)
 * Cumple con SRP: Solo maneja lógica de prendas
 * Cumple con DIP: Inyecta dependencias necesarias
 */
class RegistroOrdenPrendaService
{
    /**
     * Crear prendas para una orden
     */
    public function createPrendas(int $numeroPedido, array $prendas): int
    {
        $totalCantidad = 0;

        foreach ($prendas as $prendaData) {
            $totalCantidad += $this->createSinglePrenda($numeroPedido, $prendaData);
        }

        return $totalCantidad;
    }

    /**
     * Crear una prenda individual
     */
    private function createSinglePrenda(int $numeroPedido, array $prendaData): int
    {
        // Calcular cantidad total de la prenda
        $cantidadPrenda = 0;
        $cantidadesPorTalla = [];
        
        foreach ($prendaData['tallas'] as $talla) {
            $cantidadPrenda += $talla['cantidad'];
            $cantidadesPorTalla[$talla['talla']] = $talla['cantidad'];
        }

        // Crear prenda
        PrendaPedido::create([
            'numero_pedido' => $numeroPedido,
            'nombre_prenda' => $prendaData['prenda'],
            'cantidad' => $cantidadPrenda,
            'descripcion' => $prendaData['descripcion'] ?? '',
            'cantidad_talla' => json_encode($cantidadesPorTalla),
        ]);

        return $cantidadPrenda;
    }

    /**
     * Reemplazar todas las prendas de una orden
     */
    public function replacePrendas(int $numeroPedido, array $newPrendas): int
    {
        DB::beginTransaction();

        try {
            // Eliminar prendas existentes
            PrendaPedido::where('numero_pedido', $numeroPedido)->delete();

            // Crear nuevas prendas
            $totalCantidad = $this->createPrendas($numeroPedido, $newPrendas);

            DB::commit();

            return $totalCantidad;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Parsear descripción para extraer información de prendas y tallas
     * Formato esperado:
     * Prenda 1: NOMBRE
     * Descripción: detalles
     * Tallas: M:5, L:3, XL:2
     */
    public function parseDescripcionToPrendas(string $descripcion): array
    {
        $prendas = [];
        $lineas = explode("\n", $descripcion);
        $prendaActual = null;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Detectar inicio de nueva prenda (formato: "Prenda X: NOMBRE")
            if (preg_match('/^Prenda\s+\d+:\s*(.+)$/i', $linea, $matches)) {
                // Guardar prenda anterior si existe
                if ($prendaActual !== null) {
                    $prendas[] = $prendaActual;
                }
                
                // Iniciar nueva prenda
                $prendaActual = [
                    'nombre' => trim($matches[1]),
                    'descripcion' => '',
                    'tallas' => []
                ];
            }
            // Detectar descripción (formato: "Descripción: TEXTO")
            elseif (preg_match('/^Descripción:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $prendaActual['descripcion'] = trim($matches[1]);
                }
            }
            // Detectar tallas (formato: "Tallas: M:5, L:3, XL:2")
            elseif (preg_match('/^Tallas:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $tallasStr = trim($matches[1]);
                    $tallasPares = explode(',', $tallasStr);
                    
                    foreach ($tallasPares as $par) {
                        $parLimpio = trim($par);
                        if (preg_match('/^(.+):(\d+)$/', $parLimpio, $tallaMatches)) {
                            $talla = trim($tallaMatches[1]);
                            $cantidad = (int)$tallaMatches[2];
                            
                            if ($cantidad > 0) {
                                $prendaActual['tallas'][$talla] = $cantidad;
                            }
                        }
                    }
                }
            }
        }

        // Agregar la última prenda si existe
        if ($prendaActual !== null) {
            $prendas[] = $prendaActual;
        }

        return $prendas;
    }

    /**
     * Validar si el parsing de descripción generó prendas válidas
     */
    public function isValidParsedPrendas(array $prendas): bool
    {
        if (empty($prendas)) {
            return false;
        }

        $totalTallas = 0;
        foreach ($prendas as $prenda) {
            if (empty($prenda['nombre'])) {
                return false;
            }
            $totalTallas += count($prenda['tallas'] ?? []);
        }

        return $totalTallas > 0;
    }

    /**
     * Obtener mensaje de resultado del parsing
     */
    public function getParsedPrendasMessage(array $prendas, array $oldPrendas = []): string
    {
        if (empty($prendas)) {
            return " Descripción actualizada como texto libre. Para regenerar registros automáticamente, use el formato:\n\nPrenda 1: NOMBRE\nDescripción: detalles\nTallas: M:5, L:3";
        }

        $totalTallasEncontradas = 0;
        foreach ($prendas as $prenda) {
            $totalTallasEncontradas += count($prenda['tallas'] ?? []);
        }

        if ($totalTallasEncontradas > 0) {
            return " Descripción actualizada y registros regenerados automáticamente. Se procesaron " . count($prendas) . " prenda(s) con " . $totalTallasEncontradas . " talla(s).";
        }

        return " Descripción actualizada, pero no se encontraron tallas válidas. Los registros existentes se mantuvieron intactos.";
    }

    /**
     * Convertir prendas a array de tallas
     */
    public function getPrendasArray(int $numeroPedido): array
    {
        return PrendaPedido::where('numero_pedido', $numeroPedido)
            ->get()
            ->map(function ($prenda) {
                $cantidadTalla = is_string($prenda->cantidad_talla) 
                    ? json_decode($prenda->cantidad_talla, true) 
                    : $prenda->cantidad_talla;

                $registros = [];
                if (is_array($cantidadTalla)) {
                    foreach ($cantidadTalla as $talla => $cantidad) {
                        $registros[] = [
                            'prenda' => $prenda->nombre_prenda,
                            'talla' => $talla,
                            'cantidad' => $cantidad,
                            'descripcion' => $prenda->descripcion
                        ];
                    }
                }

                return $registros;
            })
            ->flatten(1)
            ->values()
            ->toArray();
    }
}
