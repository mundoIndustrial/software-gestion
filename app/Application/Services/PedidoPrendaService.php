<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\TipoManga;
use App\Helpers\DescripcionPrendaHelper;
use App\Helpers\DescripcionPrendaLegacyFormatter;
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
            $index = 1;
            foreach ($prendas as $prendaData) {
                // CRÍTICO: Convertir DTO a array si es necesario
                if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
                    $prendaData = $prendaData->toArray();
                }
                
                $this->guardarPrenda($pedido, $prendaData, $index);
                $index++;
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
     * Genera descripción formateada usando DescripcionPrendaLegacyFormatter
     * (Formato compatible con pedidos legacy como 45452)
     */
    private function guardarPrenda(PedidoProduccion $pedido, mixed $prendaData, int $index = 1): void
    {
        // DEFENSA: Convertir DTO a array si llega un objeto
        if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
            $prendaData = $prendaData->toArray();
        } elseif (is_object($prendaData)) {
            // Conversión forzada de objeto a array como último recurso
            $prendaData = (array)$prendaData;
        }
        
        // Validar que sea array después de conversión
        if (!is_array($prendaData)) {
            throw new \InvalidArgumentException(
                'guardarPrenda: prendaData debe ser un array o DTO con toArray(). Recibido: ' . gettype($prendaData)
            );
        }

        // Construir array de datos para el formatter legacy
        $datosParaFormatter = $this->construirDatosParaFormatter($prendaData, $index);
        
        // Generar descripción en formato legacy
        $descripcionFormateada = DescripcionPrendaLegacyFormatter::generar($datosParaFormatter);
        
        // Crear prenda principal usando PrendaPedido (tabla correcta)
        $prenda = PrendaPedido::create([
            'numero_pedido' => $pedido->numero_pedido,
            'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'descripcion' => $descripcionFormateada, // ✅ DESCRIPCIÓN EN FORMATO LEGACY
            'cantidad' => $prendaData['cantidad'] ?? 1,
            'cantidad_talla' => is_array($prendaData['cantidades'] ?? null) 
                ? json_encode($prendaData['cantidades']) 
                : $prendaData['cantidades'],
            'descripcion_variaciones' => $this->armarDescripcionVariaciones($prendaData),
            // Campos de variaciones
            'color_id' => $prendaData['color_id'] ?? null,
            'tela_id' => $prendaData['tela_id'] ?? null,
            'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
            'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? null,
            'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
            'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
        ]);

        // 2. Guardar fotos de la prenda (copiar URLs de cotización)
        if (!empty($prendaData['fotos'])) {
            $this->guardarFotosPrenda($prenda, $prendaData['fotos']);
        }

        // 3. Guardar logos de la prenda (si existen)
        if (!empty($prendaData['logos'])) {
            $this->guardarLogosPrenda($prenda, $prendaData['logos']);
        }

        // 4. Guardar fotos de telas/colores (si existen)
        if (!empty($prendaData['telas'])) {
            $this->guardarFotosTelas($prenda, $prendaData['telas']);
        }
    }

    /**
     * Construir array de datos formateado para el DescripcionPrendaLegacyFormatter
     * Convierte los datos del frontend a la estructura esperada por el formatter
     */
    private function construirDatosParaFormatter(array $prendaData, int $index = 1): array
    {
        // Obtener relaciones si están disponibles, sino buscar en BD
        $color = '';
        if ($prendaData['color_id'] ?? null) {
            $colorObj = ColorPrenda::find($prendaData['color_id']);
            $color = $colorObj?->nombre ?? '';
        }
        
        $tela = '';
        $ref = '';
        if ($prendaData['tela_id'] ?? null) {
            $telaObj = TelaPrenda::find($prendaData['tela_id']);
            if ($telaObj) {
                $tela = $telaObj->nombre ?? '';
                $ref = $telaObj->referencia ? $telaObj->referencia : '';
            }
        }
        
        $manga = '';
        if ($prendaData['tipo_manga_id'] ?? null) {
            $mangaObj = TipoManga::find($prendaData['tipo_manga_id']);
            $manga = $mangaObj?->nombre ?? '';
        }
        
        // Parsear tallas desde cantidades
        $tallas = [];
        if (is_array($prendaData['cantidades'] ?? null)) {
            $tallas = $prendaData['cantidades'];
        } elseif (is_string($prendaData['cantidades'] ?? null)) {
            try {
                $tallas = json_decode($prendaData['cantidades'], true) ?? [];
            } catch (\Exception $e) {
                $tallas = [];
            }
        }
        
        return [
            'numero' => $index,
            'tipo' => $prendaData['nombre_producto'] ?? '',
            'descripcion' => $prendaData['descripcion'] ?? '', // La descripción es el logo/detalles
            'tela' => $tela,
            'ref' => $ref,
            'color' => $color,
            'manga' => $manga,
            'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
            'bolsillos_obs' => $prendaData['bolsillos_obs'] ?? '',
            'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
            'reflectivo_obs' => $prendaData['reflectivo_obs'] ?? '',
            'tallas' => $tallas,
        ];
    }

    /**
     * Armar descripción de variaciones a partir de los datos
     */
    private function armarDescripcionVariaciones(array $prendaData): ?string
    {
        $partes = [];
        
        if (!empty($prendaData['manga'])) {
            $partes[] = "Manga: " . $prendaData['manga'];
        }
        if (!empty($prendaData['manga_obs'])) {
            $partes[] = "Obs Manga: " . $prendaData['manga_obs'];
        }
        if (!empty($prendaData['bolsillos_obs'])) {
            $partes[] = "Bolsillos: " . $prendaData['bolsillos_obs'];
        }
        if (!empty($prendaData['broche'])) {
            $partes[] = "Broche: " . $prendaData['broche'];
        }
        if (!empty($prendaData['reflectivo_obs'])) {
            $partes[] = "Reflectivo: " . $prendaData['reflectivo_obs'];
        }
        
        return !empty($partes) ? implode(" | ", $partes) : null;
    }

    /**
     * Guardar fotos de la prenda 
     */
    private function guardarFotosPrenda(PrendaPedido $prenda, array $fotos): void
    {
        foreach ($fotos as $index => $foto) {
            DB::table('prenda_fotos_pedido')->insert([
                'prenda_pedido_id' => $prenda->id,
                'ruta_original' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                'ruta_webp' => $foto['ruta_webp'] ?? null,
                'ruta_miniatura' => $foto['ruta_miniatura'] ?? null,
                'orden' => $index + 1,
                'ancho' => $foto['ancho'] ?? null,
                'alto' => $foto['alto'] ?? null,
                'tamaño' => $foto['tamaño'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Guardar logos de la prenda
     */
    private function guardarLogosPrenda(PrendaPedido $prenda, array $logos): void
    {
        foreach ($logos as $index => $logo) {
            DB::table('prenda_fotos_logo_pedido')->insert([
                'prenda_pedido_id' => $prenda->id,
                'ruta_original' => $logo['ruta_original'] ?? $logo['url'] ?? null,
                'ruta_webp' => $logo['ruta_webp'] ?? null,
                'ruta_miniatura' => $logo['ruta_miniatura'] ?? null,
                'orden' => $index + 1,
                'ubicacion' => $logo['ubicacion'] ?? null,
                'ancho' => $logo['ancho'] ?? null,
                'alto' => $logo['alto'] ?? null,
                'tamaño' => $logo['tamaño'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Guardar fotos de telas seleccionadas
     */
    private function guardarFotosTelas(PrendaPedido $prenda, array $telas): void
    {
        foreach ($telas as $tela) {
            if (!empty($tela['fotos'])) {
                foreach ($tela['fotos'] as $index => $foto) {
                    DB::table('prenda_fotos_tela_pedido')->insert([
                        'prenda_pedido_id' => $prenda->id,
                        'tela_id' => $tela['tela_id'] ?? null,
                        'color_id' => $tela['color_id'] ?? null,
                        'ruta_original' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                        'ruta_webp' => $foto['ruta_webp'] ?? null,
                        'ruta_miniatura' => $foto['ruta_miniatura'] ?? null,
                        'orden' => $index + 1,
                        'ancho' => $foto['ancho'] ?? null,
                        'alto' => $foto['alto'] ?? null,
                        'tamaño' => $foto['tamaño'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
