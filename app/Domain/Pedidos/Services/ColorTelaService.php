<?php

namespace App\Domain\Pedidos\Services;

use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para obtener o crear colores y telas desde nombres
 * 
 * El frontend envÃ­a:
 * - color: "Rojo"
 * - tela: "Algodón 100%"
 * - referencia: "ALG-ROJO-001"
 * 
 * Este servicio busca en BD o crea si no existen
 */
class ColorTelaService
{
    /**
     * Obtener o crear color desde nombre
     */
    public function obtenerOCrearColor(?string $nombreColor): ?int
    {
        if (empty($nombreColor)) {
            return null;
        }

        try {
            //  CAMBIO: Siempre crear un NUEVO color para este pedido
            // No reutilizar colores existentes - Cada pedido tiene sus propios colores
            // Esto asegura independencia entre pedidos
            
            $colorNuevo = ColorPrenda::create([
                'nombre' => $nombreColor,
                'codigo' => $this->generarCodigo($nombreColor),
                'activo' => true,
            ]);

            Log::info(' [ColorTelaService] Color creado (NUEVA INSTANCIA)', [
                'nombre' => $nombreColor,
                'color_id' => $colorNuevo->id,
                'codigo' => $colorNuevo->codigo,
            ]);

            return $colorNuevo->id;

        } catch (\Exception $e) {
            Log::error(' [ColorTelaService] Error obteniendo/creando color', [
                'nombre' => $nombreColor,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener o crear tela desde nombre y referencia
     */
    public function obtenerOCrearTela(?string $nombreTela, ?string $referencia = null): ?int
    {
        if (empty($nombreTela)) {
            return null;
        }

        try {
            //  CAMBIO: Siempre crear una NUEVA tela para este pedido
            // No reutilizar telas existentes - Cada pedido tiene sus propias telas
            // Esto asegura independencia: si no guardaste referencia, no toma la de otro pedido
            
            $telaNueva = TelaPrenda::create([
                'nombre' => $nombreTela,
                'referencia' => $referencia ?? '',
                'descripcion' => "Tela: {$nombreTela}",
                'activo' => true,
            ]);

            Log::info(' [ColorTelaService] Tela creada (NUEVA INSTANCIA)', [
                'nombre' => $nombreTela,
                'tela_id' => $telaNueva->id,
                'referencia' => $telaNueva->referencia,
            ]);

            return $telaNueva->id;

        } catch (\Exception $e) {
            Log::error(' [ColorTelaService] Error obteniendo/creando tela', [
                'nombre' => $nombreTela,
                'referencia' => $referencia,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generar código desde nombre
     */
    private function generarCodigo(string $nombre): string
    {
        // Convertir a mayÃºsculas y reemplazar espacios con guiones
        $codigo = strtoupper(str_replace(' ', '-', trim($nombre)));
        // Limitar a 50 caracteres
        return substr($codigo, 0, 50);
    }

    /**
     * Procesar tela completa: obtener/crear color y tela, retornar IDs
     */
    public function procesarTela(array $telaData): array
    {
        $nombreTela = $telaData['tela'] ?? null;
        $nombreColor = $telaData['color'] ?? null;
        $referencia = $telaData['referencia'] ?? null;

        $colorId = $this->obtenerOCrearColor($nombreColor);
        $telaId = $this->obtenerOCrearTela($nombreTela, $referencia);

        Log::info(' [ColorTelaService] Tela procesada', [
            'nombre_tela' => $nombreTela,
            'nombre_color' => $nombreColor,
            'referencia' => $referencia,
            'color_id' => $colorId,
            'tela_id' => $telaId,
        ]);

        return [
            'color_id' => $colorId,
            'tela_id' => $telaId,
            'nombre_tela' => $nombreTela,
            'nombre_color' => $nombreColor,
        ];
    }

    /**
     * Procesar mÃºltiples telas
     */
    public function procesarTelas(array $telas): array
    {
        $telasProcessadas = [];

        foreach ($telas as $tela) {
            $telasProcessadas[] = $this->procesarTela($tela);
        }

        return $telasProcessadas;
    }
}

