<?php

namespace App\Application\Bodega\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

class BodegaDatosService
{
    /**
     * Obtener datos enriquecidos desde bodega_detalles_talla
     * incluye información de prendas, procesos, variantes, etc.
     */
    public function obtenerDatosDesdeBodegaDetalles(string $numeroPedido): array
    {
        try {
            \Log::info('Obteniendo datos desde bodega_detalles_talla', ['numero_pedido' => $numeroPedido]);
            
            // Obtener el PedidoProduccion para acceder a las prendas
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            // Obtener todos los detalles para este pedido
            $detalles = DB::table('bodega_detalles_talla')
                ->where('numero_pedido', $numeroPedido)
                ->get();
            
            \Log::info('Detalles encontrados', ['count' => $detalles->count(), 'pedido_id' => $pedidoProduccion?->id]);
            
            // Formatear los datos como los espera la vista
            $items = [];
            foreach ($detalles as $detalle) {
                // Buscar la prenda compatible con el nombre para obtener datos enriquecidos
                $prendaEnriquecida = null;
                if ($pedidoProduccion) {
                    $prendaEnriquecida = $pedidoProduccion->prendas()
                        ->with([
                            'coloresTelas.color',
                            'coloresTelas.tela',
                            'procesos.tipoProceso',
                            'variantes'
                        ])
                        ->where('nombre_prenda', $detalle->prenda_nombre)
                        ->first();
                }
                
                // Si encontramos la prenda, enriquecer datos
                $descripcionData = [
                    'nombre_prenda' => $detalle->prenda_nombre,
                    'nombre' => $detalle->prenda_nombre,
                    'descripcion' => null,
                    'tela' => null,
                    'color' => null,
                    'variantes' => [],
                    'procesos' => [],
                    'de_bodega' => false,
                ];
                
                if ($prendaEnriquecida) {
                    // Obtener descripción de la prenda
                    $descripcionData['descripcion'] = $prendaEnriquecida->descripcion ?? null;
                    
                    // Obtener tela y color desde colores-telas
                    if ($prendaEnriquecida->coloresTelas && $prendaEnriquecida->coloresTelas->count() > 0) {
                        $primerColorTela = $prendaEnriquecida->coloresTelas->first();
                        $descripcionData['tela'] = $primerColorTela->tela?->nombre ?? null;
                        $descripcionData['color'] = $primerColorTela->color?->nombre ?? null;
                    }
                    
                    // Obtener procesos
                    if ($prendaEnriquecida->procesos && $prendaEnriquecida->procesos->count() > 0) {
                        $descripcionData['procesos'] = $prendaEnriquecida->procesos->map(function($proceso) {
                            return [
                                'tipo_proceso' => $proceso->tipoProceso?->nombre ?? $proceso->tipo_proceso ?? 'Proceso',
                                'ubicaciones' => isset($proceso->ubicaciones) && is_string($proceso->ubicaciones) 
                                    ? json_decode($proceso->ubicaciones, true) 
                                    : [],
                            ];
                        })->toArray();
                    }
                    
                    // Obtener variantes desde prenda_pedido_tallas (que tiene genero y talla)
                    // PERO usar solo la talla y género del detalle de bodega para evitar confusiones
                    $tallasPedido = \DB::table('prenda_pedido_tallas')
                        ->where('prenda_pedido_id', $prendaEnriquecida->id)
                        ->where('talla', $detalle->talla)
                        ->where('genero', $detalle->genero ?? 'DAMA')
                        ->get();
                    
                    if ($tallasPedido->isNotEmpty()) {
                        $descripcionData['variantes'] = $tallasPedido->map(function($talla) use ($detalle) {
                            return [
                                'talla' => $talla->talla ?? null,
                                'genero' => $talla->genero ?? null,
                                'cantidad' => $talla->cantidad ?? 0,
                                'manga' => null,
                                'manga_obs' => null,
                                'broche' => null,
                                'broche_obs' => null,
                                'bolsillos' => null,
                                'bolsillos_obs' => null,
                                'colores_detalle' => [
                                    [
                                        'color' => '',
                                        'cantidad' => $detalle->cantidad,
                                        'talla_color_id' => null,
                                    ]
                                ],
                            ];
                        })->toArray();
                    } else {
                        // Si no encuentra la talla exacta en prenda_pedido_tallas, usar el detalle de bodega directamente
                        $descripcionData['variantes'] = [
                            [
                                'talla' => $detalle->talla,
                                'genero' => $detalle->genero ?? null,
                                'cantidad' => $detalle->cantidad,
                                'manga' => null,
                                'manga_obs' => null,
                                'broche' => null,
                                'broche_obs' => null,
                                'bolsillos' => null,
                                'bolsillos_obs' => null,
                                'colores_detalle' => [
                                    [
                                        'color' => '',
                                        'cantidad' => $detalle->cantidad,
                                        'talla_color_id' => null,
                                    ]
                                ],
                            ]
                        ];
                    }
                    
                    $descripcionData['de_bodega'] = (bool)$prendaEnriquecida->de_bodega;
                }
                
                $items[] = [
                    'id' => $detalle->id,
                    'numero_pedido' => $detalle->numero_pedido,
                    'prenda_nombre' => $detalle->prenda_nombre,
                    'talla' => $detalle->talla,
                    'cantidad' => $detalle->cantidad,
                    'area' => $detalle->area,
                    'estado_bodega' => $detalle->estado_bodega,
                    'costura_estado' => $detalle->costura_estado ?? null,
                    'epp_estado' => $detalle->epp_estado ?? null,
                    'observaciones_bodega' => $detalle->observaciones_bodega,
                    'fecha_pedido' => $detalle->fecha_pedido,
                    'fecha_entrega' => $detalle->fecha_entrega,
                    'usuario_bodega_nombre' => $detalle->usuario_bodega_nombre,
                    'asesor' => $detalle->asesor,
                    'empresa' => $detalle->empresa,
                    // Estructura enriquecida que espera la vista pendiente-costura-show
                    'descripcion' => $descripcionData
                ];
            }
            
            // Obtener información del pedido principal
            $pedidoInfo = DB::table('pedidos_produccion')
                ->where('numero_pedido', $numeroPedido)
                ->first();
            
            // Si no encontramos el pedido principal, crear datos básicos desde bodega_detalles_talla
            if (!$pedidoInfo && !empty($detalles)) {
                $primerDetalle = $detalles->first();
                $pedidoInfo = (object) [
                    'id' => $primerDetalle->id,
                    'numero_pedido' => $primerDetalle->numero_pedido,
                    'cliente' => $primerDetalle->empresa ?? 'Cliente no especificado',
                    'estado' => 'Desconocido',
                    'area' => 'Múltiple',
                    'descripcion' => 'Pedido desde bodega',
                    'asesor' => $primerDetalle->asesor ?? 'No especificado',
                ];
            }
            
            $datos = [
                'pedido' => $pedidoInfo ? [
                    'id' => $pedidoInfo->id,
                    'numero_pedido' => $pedidoInfo->numero_pedido,
                    'cliente' => $pedidoInfo->cliente,
                    'estado' => $pedidoInfo->estado,
                    'area' => $pedidoInfo->area,
                    'descripcion' => $pedidoInfo->descripcion ?? $pedidoInfo->novedades ?? 'Sin descripción',
                    'asesor' => $pedidoInfo->asesor ?? null,
                ] : [
                    'id' => null,
                    'numero_pedido' => $numeroPedido,
                    'cliente' => 'No especificado',
                    'estado' => 'Desconocido',
                    'area' => 'Desconocida',
                    'descripcion' => 'Pedido no encontrado',
                    'asesor' => null,
                ],
                'items' => $items,
                'estadisticas' => [
                    'total_items' => count($items),
                    'total_epp_pendientes' => count(array_filter($items, fn($item) => $item['area'] === 'EPP' && $item['estado_bodega'] === 'Pendiente')),
                ]
            ];
            
            \Log::info('Datos formateados correctamente', ['items_count' => count($items)]);
            
            return $datos;
            
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosDesdeBodegaDetalles: ' . $e->getMessage());
            return [
                'pedido' => null,
                'items' => [],
                'estadisticas' => ['total_items' => 0, 'total_epp_pendientes' => 0]
            ];
        }
    }
}
