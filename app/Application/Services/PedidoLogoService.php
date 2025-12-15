<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;
use App\Models\LogoPed;
use App\Models\LogoFotoPed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PedidoLogoService
 * 
 * Responsabilidad: Guardar logos de pedidos en tablas normalizadas
 * Equivalente a CotizacionLogoService pero para pedidos
 * 
 * Cumple:
 * - SRP: Solo guarda logos
 * - DIP: Inyecta dependencias
 * - OCP: Fácil de extender
 */
class PedidoLogoService
{
    /**
     * Guardar logo en pedido
     */
    public function guardarLogoEnPedido(PedidoProduccion $pedido, array $logoData): void
    {
        if (empty($logoData)) {
            Log::warning('PedidoLogoService: No hay datos de logo para guardar', [
                'pedido_id' => $pedido->id,
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Crear logo principal
            $logo = LogoPed::create([
                'pedido_produccion_id' => $pedido->id,
                'descripcion' => $logoData['descripcion'] ?? null,
                'ubicacion' => $logoData['ubicacion'] ?? null,
                'observaciones_generales' => $logoData['observaciones_generales'] ?? null,
            ]);

            // 2. Guardar fotos del logo (copiar URLs de cotización)
            if (!empty($logoData['fotos'])) {
                $this->guardarFotosLogo($logo, $logoData['fotos']);
            }

            DB::commit();
            Log::info('PedidoLogoService: Logo guardado correctamente', [
                'pedido_id' => $pedido->id,
                'logo_id' => $logo->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PedidoLogoService: Error guardando logo', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Guardar fotos del logo (copiar URLs de cotización)
     */
    private function guardarFotosLogo(LogoPed $logo, array $fotos): void
    {
        foreach ($fotos as $index => $foto) {
            LogoFotoPed::create([
                'logo_ped_id' => $logo->id,
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
