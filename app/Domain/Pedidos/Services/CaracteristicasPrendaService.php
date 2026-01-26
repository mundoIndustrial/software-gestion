<?php

namespace App\Domain\Pedidos\Services;

use App\Models\TipoManga;
use App\Models\TipoBrocheBoton;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para obtener o crear caracterÃ­sticas de prendas (manga, broche, bolsillos)
 * desde nombres
 */
class CaracteristicasPrendaService
{
    /**
     * Obtener o crear tipo de manga desde nombre
     */
    public function obtenerOCrearManga(?string $nombreManga): ?int
    {
        if (empty($nombreManga)) {
            return null;
        }

        try {
            // Buscar por nombre exacto
            $manga = TipoManga::where('nombre', $nombreManga)
                ->where('activo', true)
                ->first();

            if ($manga) {
                Log::info(' [CaracteristicasPrendaService] Manga encontrada', [
                    'nombre' => $nombreManga,
                    'manga_id' => $manga->id,
                ]);
                return $manga->id;
            }

            // Si no existe, crear
            $mangaNueva = TipoManga::create([
                'nombre' => $nombreManga,
                'activo' => true,
            ]);

            Log::info(' [CaracteristicasPrendaService] Manga creada', [
                'nombre' => $nombreManga,
                'manga_id' => $mangaNueva->id,
            ]);

            return $mangaNueva->id;

        } catch (\Exception $e) {
            Log::error(' [CaracteristicasPrendaService] Error obteniendo/creando manga', [
                'nombre' => $nombreManga,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener o crear tipo de broche/botón desde nombre
     */
    public function obtenerOCrearBroche(?string $nombreBroche): ?int
    {
        if (empty($nombreBroche)) {
            return null;
        }

        try {
            // Buscar por nombre exacto
            $broche = TipoBrocheBoton::where('nombre', $nombreBroche)
                ->where('activo', true)
                ->first();

            if ($broche) {
                Log::info(' [CaracteristicasPrendaService] Broche encontrado', [
                    'nombre' => $nombreBroche,
                    'broche_id' => $broche->id,
                ]);
                return $broche->id;
            }

            // Si no existe, crear
            $brocheNuevo = TipoBrocheBoton::create([
                'nombre' => $nombreBroche,
                'activo' => true,
            ]);

            Log::info(' [CaracteristicasPrendaService] Broche creado', [
                'nombre' => $nombreBroche,
                'broche_id' => $brocheNuevo->id,
            ]);

            return $brocheNuevo->id;

        } catch (\Exception $e) {
            Log::error(' [CaracteristicasPrendaService] Error obteniendo/creando broche', [
                'nombre' => $nombreBroche,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

