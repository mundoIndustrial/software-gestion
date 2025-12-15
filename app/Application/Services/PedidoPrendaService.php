<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPed;
use App\Models\PrendaFotoPed;
use App\Models\PrendaTalaPed;
use App\Models\PrendaVariantePed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PedidoPrendaService
 * 
 * Responsabilidad: Guardar prendas de pedidos en tablas normalizadas
 * Equivalente a CotizacionPrendaService pero para pedidos
 * 
 * Cumple:
 * - SRP: Solo guarda prendas
 * - DIP: Inyecta dependencias
 * - OCP: Fácil de extender
 */
class PedidoPrendaService
{
    /**
     * Guardar prendas en pedido
     */
    public function guardarPrendasEnPedido(PedidoProduccion $pedido, array $prendas): void
    {
        if (empty($prendas)) {
            Log::warning('PedidoPrendaService: No hay prendas para guardar', [
                'pedido_id' => $pedido->id,
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($prendas as $prendaData) {
                $this->guardarPrenda($pedido, $prendaData);
            }
            DB::commit();
            Log::info('PedidoPrendaService: Prendas guardadas correctamente', [
                'pedido_id' => $pedido->id,
                'cantidad' => count($prendas),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PedidoPrendaService: Error guardando prendas', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Guardar una prenda con sus relaciones
     */
    private function guardarPrenda(PedidoProduccion $pedido, array $prendaData): void
    {
        // 1. Crear prenda principal
        $prenda = PrendaPed::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_producto' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'descripcion' => $prendaData['descripcion'] ?? null,
            'cantidad' => $prendaData['cantidad'] ?? 1,
        ]);

        // 2. Guardar fotos de la prenda (copiar URLs de cotización)
        if (!empty($prendaData['fotos'])) {
            $this->guardarFotosPrenda($prenda, $prendaData['fotos']);
        }

        // 3. Guardar telas/colores (copiar URLs de cotización)
        if (!empty($prendaData['telas'])) {
            $this->guardarTelasPrenda($prenda, $prendaData['telas']);
        }

        // 4. Guardar tallas
        if (!empty($prendaData['tallas'])) {
            $this->guardarTallasPrenda($prenda, $prendaData['tallas']);
        }

        // 5. Guardar variantes
        if (!empty($prendaData['variantes'])) {
            $this->guardarVariantesPrenda($prenda, $prendaData['variantes']);
        }
    }

    /**
     * Guardar fotos de la prenda (copiar URLs de cotización)
     */
    private function guardarFotosPrenda(PrendaPed $prenda, array $fotos): void
    {
        foreach ($fotos as $index => $foto) {
            PrendaFotoPed::create([
                'prenda_ped_id' => $prenda->id,
                'ruta_original' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                'ruta_webp' => $foto['ruta_webp'] ?? null,
                'ruta_miniatura' => $foto['ruta_miniatura'] ?? null,
                'orden' => $index + 1,
                'ancho' => $foto['ancho'] ?? null,
                'alto' => $foto['alto'] ?? null,
                'tamaño' => $foto['tamaño'] ?? null,
            ]);
        }
    }

    /**
     * Guardar telas de la prenda (copiar URLs de cotización)
     */
    private function guardarTelasPrenda(PrendaPed $prenda, array $telas): void
    {
        foreach ($telas as $tela) {
            $telaPed = \App\Models\PrendaTelaPed::create([
                'prenda_ped_id' => $prenda->id,
                'color_id' => $tela['color_id'] ?? null,
                'tela_id' => $tela['tela_id'] ?? null,
            ]);

            // Guardar fotos de telas (copiar URLs de cotización)
            if (!empty($tela['fotos'])) {
                foreach ($tela['fotos'] as $index => $foto) {
                    \App\Models\PrendaTalaFotoPed::create([
                        'prenda_tela_ped_id' => $telaPed->id,
                        'ruta_original' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                        'ruta_webp' => $foto['ruta_webp'] ?? null,
                        'ruta_miniatura' => $foto['ruta_miniatura'] ?? null,
                        'orden' => $index + 1,
                        'ancho' => $foto['ancho'] ?? null,
                        'alto' => $foto['alto'] ?? null,
                        'tamaño' => $foto['tamaño'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Guardar tallas de la prenda
     */
    private function guardarTallasPrenda(PrendaPed $prenda, array $tallas): void
    {
        foreach ($tallas as $talla) {
            PrendaTalaPed::create([
                'prenda_ped_id' => $prenda->id,
                'talla' => $talla['talla'] ?? null,
                'cantidad' => $talla['cantidad'] ?? 1,
            ]);
        }
    }

    /**
     * Guardar variantes de la prenda
     */
    private function guardarVariantesPrenda(PrendaPed $prenda, array $variantes): void
    {
        foreach ($variantes as $variante) {
            PrendaVariantePed::create([
                'prenda_ped_id' => $prenda->id,
                'tipo_prenda' => $variante['tipo_prenda'] ?? null,
                'es_jean_pantalon' => $variante['es_jean_pantalon'] ?? false,
                'tipo_jean_pantalon' => $variante['tipo_jean_pantalon'] ?? null,
                'genero_id' => $variante['genero_id'] ?? null,
                'color' => $variante['color'] ?? null,
                'tiene_bolsillos' => $variante['tiene_bolsillos'] ?? false,
                'obs_bolsillos' => $variante['obs_bolsillos'] ?? null,
                'aplica_manga' => $variante['aplica_manga'] ?? false,
                'tipo_manga_id' => $variante['tipo_manga_id'] ?? null,
                'obs_manga' => $variante['obs_manga'] ?? null,
                'aplica_broche' => $variante['aplica_broche'] ?? false,
                'tipo_broche_id' => $variante['tipo_broche_id'] ?? null,
                'obs_broche' => $variante['obs_broche'] ?? null,
                'tiene_reflectivo' => $variante['tiene_reflectivo'] ?? false,
                'obs_reflectivo' => $variante['obs_reflectivo'] ?? null,
                'descripcion_adicional' => $variante['descripcion_adicional'] ?? null,
                'telas_multiples' => $variante['telas_multiples'] ?? null,
            ]);
        }
    }
}
