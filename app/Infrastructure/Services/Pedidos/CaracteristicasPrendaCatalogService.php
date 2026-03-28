<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Services\CaracteristicasPrendaCatalogServiceContract;

use App\Models\TipoBrocheBoton;
use App\Models\TipoManga;
use Illuminate\Support\Facades\Log;

/**
 * Resuelve catalogos de caracteristicas de prenda para pedidos.
 */
class CaracteristicasPrendaCatalogService implements CaracteristicasPrendaCatalogServiceContract
{
    public function obtenerOCrearManga(?string $nombreManga): ?int
    {
        if (empty($nombreManga)) {
            return null;
        }

        try {
            $manga = TipoManga::where('nombre', $nombreManga)
                ->where('activo', true)
                ->first();

            if ($manga) {
                Log::info('[CaracteristicasPrendaCatalogService] Manga encontrada', [
                    'nombre' => $nombreManga,
                    'manga_id' => $manga->id,
                ]);
                return $manga->id;
            }

            $mangaNueva = TipoManga::create([
                'nombre' => $nombreManga,
                'activo' => true,
            ]);

            Log::info('[CaracteristicasPrendaCatalogService] Manga creada', [
                'nombre' => $nombreManga,
                'manga_id' => $mangaNueva->id,
            ]);

            return $mangaNueva->id;
        } catch (\Exception $e) {
            Log::error('[CaracteristicasPrendaCatalogService] Error obteniendo/creando manga', [
                'nombre' => $nombreManga,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function obtenerOCrearBroche(?string $nombreBroche): ?int
    {
        if (empty($nombreBroche)) {
            return null;
        }

        try {
            $broche = TipoBrocheBoton::where('nombre', $nombreBroche)
                ->where('activo', true)
                ->first();

            if ($broche) {
                Log::info('[CaracteristicasPrendaCatalogService] Broche encontrado', [
                    'nombre' => $nombreBroche,
                    'broche_id' => $broche->id,
                ]);
                return $broche->id;
            }

            $brocheNuevo = TipoBrocheBoton::create([
                'nombre' => $nombreBroche,
                'activo' => true,
            ]);

            Log::info('[CaracteristicasPrendaCatalogService] Broche creado', [
                'nombre' => $nombreBroche,
                'broche_id' => $brocheNuevo->id,
            ]);

            return $brocheNuevo->id;
        } catch (\Exception $e) {
            Log::error('[CaracteristicasPrendaCatalogService] Error obteniendo/creando broche', [
                'nombre' => $nombreBroche,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {CaracteristicasPrendaCatalogService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
