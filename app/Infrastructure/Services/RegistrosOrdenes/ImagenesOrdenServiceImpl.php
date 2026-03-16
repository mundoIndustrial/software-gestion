<?php

namespace App\Infrastructure\Services\RegistrosOrdenes;

use App\Domain\RegistrosOrdenes\Contracts\ImagenesOrdenService;
use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Models\LogoPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ImagenesOrdenServiceImpl
 * 
 * Implementación para obtención de imágenes
 * Extrae lógica de getOrderImages y getLogoImages
 */
class ImagenesOrdenServiceImpl implements ImagenesOrdenService
{
    public function obtenerImagenesOrden($numeroPedido): array
    {
        $imagenes = [];
        $prendasConImagenes = [];

        try {
            // Obtener pedido
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            if ($pedidoProduccion && $pedidoProduccion->cotizacion_id) {
                $cotizacion = Cotizacion::find($pedidoProduccion->cotizacion_id);
                if ($cotizacion && $cotizacion->imagenes) {
                    $imagenes = array_map(fn($img) => [
                        'ruta' => $this->normalizarRuta($img->ruta ?? ''),
                        'descripcion' => $img->descripcion ?? '',
                    ], $cotizacion->imagenes);
                }
            }

            // Obtener imágenes por prenda
            $prendas = DB::table('prendas_pedido')
                ->where('pedido_produccion_id', $pedidoProduccion?->id ?? 0)
                ->get();

            foreach ($prendas as $prenda) {
                $prendasConImagenes[] = $this->obtenerImagenesPrenda($prenda->id);
            }

            Log::info('[obtenerImagenesOrden] Imágenes obtenidas', [
                'numero_pedido' => $numeroPedido,
                'total_prendas' => count($prendasConImagenes),
            ]);

            return [
                'prendas' => $prendasConImagenes,
                'cotizacion' => $imagenes,
            ];

        } catch (\Exception $e) {
            Log::error('[obtenerImagenesOrden] Error: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerImagenesLogo($numeroPedido): array
    {
        try {
            $logos = [];

            // Buscar logo_pedido
            $logoPedido = DB::table('logo_pedidos')
                ->where('numero_pedido', $numeroPedido)
                ->orWhere('numero_pedido', '#' . $numeroPedido)
                ->first(['id', 'numero_pedido', 'pedido_id']);

            if ($logoPedido && $logoPedido->pedido_id) {
                // Obtener imágenes asociadas
                $imagenes = DB::table('logo_pedido_imagenes')
                    ->where('logo_pedido_id', $logoPedido->id)
                    ->get();

                $logos[] = [
                    'id' => $logoPedido->id,
                    'numero_pedido' => $logoPedido->numero_pedido,
                    'imagenes' => array_map(fn($img) => [
                        'ruta' => $this->normalizarRuta($img->ruta ?? ''),
                    ], $imagenes->toArray()),
                ];
            }

            Log::info('[obtenerImagenesLogo] Logos obtenidos', [
                'numero_pedido' => $numeroPedido,
                'total_logos' => count($logos),
            ]);

            return $logos;

        } catch (\Exception $e) {
            Log::error('[obtenerImagenesLogo] Error: ' . $e->getMessage());
            return [];
        }
    }

    public function normalizarRuta($ruta): ?string
    {
        if (empty($ruta)) {
            return null;
        }

        // Si es URL completa, devolverla
        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }

        // Si ya comienza con /storage/, devolverla
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }

        // Si comienza con storage/, agregar /
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }

        // Si es relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }

    public function obtenerImagenesPrenda($prendaId): array
    {
        try {
            $imagenes = DB::table('prenda_fotos')
                ->where('prenda_pedido_id', $prendaId)
                ->get();

            return [
                'prenda_id' => $prendaId,
                'imagenes' => array_map(fn($img) => [
                    'ruta' => $this->normalizarRuta($img->ruta ?? ''),
                ], $imagenes->toArray()),
            ];

        } catch (\Exception $e) {
            Log::error('[obtenerImagenesPrenda] Error: ' . $e->getMessage());
            return ['prenda_id' => $prendaId, 'imagenes' => []];
        }
    }
}
