<?php

namespace App\Application\Services;

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrendaVarianteService
 * 
 * Responsabilidad: Crear variantes de prendas en la BD
 * Guarda características (manga, broche, bolsillos) en prenda_pedido_variantes
 */
class PrendaVarianteService
{
    /**
     * Crear variantes desde cantidad_talla
     * 
     * Crea UNA SOLA variante por prenda con sus características
     * Las tallas/cantidades ya están en prendas_pedido.cantidad_talla (JSON)
     */
    public function crearVariantesDesdeCantidadTalla(
        int $prendaId,
        mixed $cantidadTalla,
        ?int $colorId = null,
        ?int $telaId = null,
        ?int $tipoMangaId = null,
        ?int $tipoBrocheBotonId = null,
        string $mangaObs = '',
        string $brocheObs = '',
        bool $tieneBolsillos = false,
        string $bolsillosObs = ''
    ): void {
        try {
            // Verificar que hay tallas (para no crear variante vacía)
            $tieneTallas = false;
            if (is_array($cantidadTalla)) {
                $tieneTallas = !empty($cantidadTalla);
            } elseif (is_string($cantidadTalla)) {
                $decoded = json_decode($cantidadTalla, true);
                $tieneTallas = !empty($decoded);
            }

            if (!$tieneTallas) {
                Log::warning(' [PrendaVarianteService] cantidad_talla vacío, no se crea variante', [
                    'prenda_id' => $prendaId,
                ]);
                return;
            }

            Log::info(' [PrendaVarianteService] Creando variante de prenda', [
                'prenda_id' => $prendaId,
                'tipo_manga_id' => $tipoMangaId,
                'tipo_broche_boton_id' => $tipoBrocheBotonId,
            ]);

            // Obtener la prenda
            $prenda = PrendaPedido::find($prendaId);
            if (!$prenda) {
                throw new \Exception("Prenda {$prendaId} no encontrada");
            }

            // Crear UNA SOLA variante con todas las características
            $variante = $prenda->variantes()->create([
                'tipo_manga_id' => $tipoMangaId,
                'tipo_broche_boton_id' => $tipoBrocheBotonId,
                'manga_obs' => $mangaObs,
                'broche_boton_obs' => $brocheObs,
                'tiene_bolsillos' => $tieneBolsillos,
                'bolsillos_obs' => $bolsillosObs,
            ]);

            // Guardar color y tela en tabla separada si existen
            if ($colorId || $telaId) {
                \App\Models\PrendaPedidoColorTela::create([
                    'prenda_pedido_id' => $prendaId,
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                ]);
                
                Log::info(' [PrendaVarianteService] Color y tela guardados en tabla separada', [
                    'prenda_id' => $prendaId,
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                ]);
            }

            Log::info(' [PrendaVarianteService] Variante creada exitosamente', [
                'prenda_id' => $prendaId,
                'variante_id' => $variante->id,
            ]);
        } catch (\Exception $e) {
            Log::error(' [PrendaVarianteService] Error creando variante', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

}
