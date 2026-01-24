<?php

namespace App\Domain\Pedidos\CommandHandlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariante;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaFotoPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcesosPrendaTalla;
use App\Models\PedidosProcesoImagen;

/**
 * Handler completo para crear pedido de producción con TODAS sus relaciones
 * Persiste en 10 tablas diferentes en una sola transacción
 */
class CrearPedidoProduccionCompletoHandler
{
    /**
     * Ejecutar creación completa del pedido
     * 
     * @param array $data Datos del pedido completo
     * @return PedidoProduccion
     */
    public function handle(array $data): PedidoProduccion
    {
        return DB::transaction(function () use ($data) {
            Log::info('🚀 [CrearPedidoCompletoHandler] Iniciando transacción', [
                'cliente' => $data['cliente'],
                'items_count' => count($data['items'] ?? []),
            ]);

            // 1️⃣ CREAR PEDIDO (Aggregate Root)
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $data['numero_pedido'],
                'cliente_id' => $data['cliente_id'] ?? null,
                'cliente' => is_string($data['cliente']) ? $data['cliente'] : null,
                'forma_de_pago' => $data['forma_pago'] ?? $data['forma_de_pago'] ?? 'contado',
                'asesor_id' => $data['asesor_id'],
                'cantidad_total' => 0, // Se actualiza después
                'estado' => 'Pendiente',
            ]);

            Log::info('✅ Pedido creado', ['pedido_id' => $pedido->id]);

            $cantidadTotalPedido = 0;

            // 2️⃣ PROCESAR CADA PRENDA DEL PEDIDO
            foreach ($data['items'] as $index => $item) {
                Log::info("🔹 Procesando prenda #{$index}", [
                    'nombre' => $item['nombre_prenda'] ?? 'Sin nombre',
                ]);

                // 2.1 CREAR PRENDA
                $prenda = PrendaPedido::create([
                    'pedido_produccion_id' => $pedido->id,
                    'nombre_prenda' => $item['nombre_prenda'] ?? $item['nombre_producto'] ?? 'Sin nombre',
                    'descripcion' => $item['descripcion'] ?? '',
                    'de_bodega' => (int)($item['de_bodega'] ?? $item['origen'] === 'bodega' ?? 0),
                ]);

                Log::info('  ✅ Prenda creada', ['prenda_id' => $prenda->id]);

                // 2.2 GUARDAR VARIANTES (manga, broche, bolsillos)
                if (!empty($item['variaciones']) || !empty($item['variantes'])) {
                    $variaciones = $item['variaciones'] ?? $item['variantes'] ?? [];
                    
                    PrendaVariante::create([
                        'prenda_pedido_id' => $prenda->id,
                        'tipo_manga_id' => $variaciones['tipo_manga_id'] ?? null,
                        'tipo_broche_boton_id' => $variaciones['tipo_broche_boton_id'] ?? $variaciones['tipo_broche_id'] ?? null,
                        'manga_obs' => $variaciones['manga_obs'] ?? $variaciones['obs_manga'] ?? '',
                        'broche_boton_obs' => $variaciones['broche_boton_obs'] ?? $variaciones['obs_broche'] ?? '',
                        'tiene_bolsillos' => (bool)($variaciones['tiene_bolsillos'] ?? false),
                        'bolsillos_obs' => $variaciones['bolsillos_obs'] ?? $variaciones['obs_bolsillos'] ?? '',
                    ]);

                    Log::info('  ✅ Variantes guardadas');
                }

                // 2.3 GUARDAR TALLAS (prenda_pedido_tallas)
                $cantidadPrenda = 0;
                if (!empty($item['cantidad_talla'])) {
                    foreach ($item['cantidad_talla'] as $genero => $tallas) {
                        if (is_array($tallas) && !empty($tallas)) {
                            foreach ($tallas as $talla => $cantidad) {
                                if ($cantidad > 0) {
                                    PrendaPedidoTalla::create([
                                        'prenda_pedido_id' => $prenda->id,
                                        'genero' => strtoupper($genero),
                                        'talla' => strtoupper($talla),
                                        'cantidad' => (int)$cantidad,
                                    ]);
                                    $cantidadPrenda += (int)$cantidad;
                                }
                            }
                        }
                    }
                    Log::info('  ✅ Tallas guardadas', ['cantidad_total' => $cantidadPrenda]);
                    $cantidadTotalPedido += $cantidadPrenda;
                }

                // 2.4 GUARDAR COLORES Y TELAS
                if (!empty($item['telas'])) {
                    foreach ($item['telas'] as $telaData) {
                        $colorTela = PrendaPedidoColorTela::create([
                            'prenda_pedido_id' => $prenda->id,
                            'color_id' => $telaData['color_id'] ?? null,
                            'tela_id' => $telaData['tela_id'] ?? null,
                        ]);

                        Log::info('  ✅ Color-Tela guardado', ['id' => $colorTela->id]);

                        // 2.5 GUARDAR FOTOS DE TELA
                        if (!empty($telaData['imagenes'])) {
                            $orden = 1;
                            foreach ($telaData['imagenes'] as $imagen) {
                                if (is_string($imagen) && !empty($imagen)) {
                                    PrendaFotoTelaPedido::create([
                                        'prenda_pedido_colores_telas_id' => $colorTela->id,
                                        'ruta_original' => $imagen,
                                        'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imagen),
                                        'orden' => $orden++,
                                    ]);
                                }
                            }
                            Log::info('    ✅ Fotos de tela guardadas', ['cantidad' => $orden - 1]);
                        }
                    }
                }

                // 2.6 GUARDAR FOTOS DE LA PRENDA
                if (!empty($item['imagenes'])) {
                    $orden = 1;
                    foreach ($item['imagenes'] as $imagen) {
                        // Manejar arrays anidados [[]]
                        if (is_array($imagen)) {
                            foreach ($imagen as $imgNested) {
                                if (is_string($imgNested) && !empty($imgNested)) {
                                    PrendaFotoPedido::create([
                                        'prenda_pedido_id' => $prenda->id,
                                        'ruta_original' => $imgNested,
                                        'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imgNested),
                                        'orden' => $orden++,
                                    ]);
                                }
                            }
                        } elseif (is_string($imagen) && !empty($imagen)) {
                            PrendaFotoPedido::create([
                                'prenda_pedido_id' => $prenda->id,
                                'ruta_original' => $imagen,
                                'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imagen),
                                'orden' => $orden++,
                            ]);
                        }
                    }
                    if ($orden > 1) {
                        Log::info('  ✅ Fotos de prenda guardadas', ['cantidad' => $orden - 1]);
                    }
                }

                // 3️⃣ GUARDAR PROCESOS PRODUCTIVOS
                if (!empty($item['procesos'])) {
                    $tipoProcesoMap = [
                        'reflectivo' => 1,
                        'bordado' => 2,
                        'estampado' => 3,
                        'dtf' => 4,
                        'sublimado' => 5,
                    ];

                    foreach ($item['procesos'] as $tipoProceso => $procesoData) {
                        if (empty($procesoData['datos'])) {
                            continue;
                        }

                        $datos = $procesoData['datos'];
                        $tipoProcesoId = $tipoProcesoMap[strtolower($tipoProceso)] ?? null;

                        if (!$tipoProcesoId) {
                            Log::warning('  ⚠️ Tipo de proceso desconocido', ['tipo' => $tipoProceso]);
                            continue;
                        }

                        // 3.1 CREAR REGISTRO DE PROCESO
                        $proceso = PedidosProcesosPrendaDetalle::create([
                            'prenda_pedido_id' => $prenda->id,
                            'tipo_proceso_id' => $tipoProcesoId,
                            'ubicaciones' => !empty($datos['ubicaciones']) ? json_encode($datos['ubicaciones']) : null,
                            'observaciones' => $datos['observaciones'] ?? null,
                            'tallas_dama' => !empty($datos['tallas']['dama']) ? json_encode($datos['tallas']['dama']) : null,
                            'tallas_caballero' => !empty($datos['tallas']['caballero']) ? json_encode($datos['tallas']['caballero']) : null,
                            'estado' => 'Pendiente',
                            'datos_adicionales' => !empty($datos['adicionales']) ? json_encode($datos['adicionales']) : null,
                        ]);

                        Log::info("  ✅ Proceso guardado: {$tipoProceso}", ['proceso_id' => $proceso->id]);

                        // 3.2 GUARDAR TALLAS POR PROCESO
                        if (!empty($datos['tallas'])) {
                            foreach ($datos['tallas'] as $genero => $tallas) {
                                if (is_array($tallas)) {
                                    foreach ($tallas as $talla => $cantidad) {
                                        if ($cantidad > 0) {
                                            PedidosProcesosPrendaTalla::create([
                                                'proceso_prenda_detalle_id' => $proceso->id,
                                                'genero' => strtoupper($genero),
                                                'talla' => strtoupper($talla),
                                                'cantidad' => (int)$cantidad,
                                            ]);
                                        }
                                    }
                                }
                            }
                            Log::info('    ✅ Tallas de proceso guardadas');
                        }

                        // 3.3 GUARDAR IMÁGENES DEL PROCESO
                        if (!empty($datos['imagenes'])) {
                            $ordenProceso = 1;
                            foreach ($datos['imagenes'] as $imagen) {
                                if (is_array($imagen)) {
                                    foreach ($imagen as $imgNested) {
                                        if (is_string($imgNested) && !empty($imgNested)) {
                                            PedidosProcesoImagen::create([
                                                'proceso_prenda_detalle_id' => $proceso->id,
                                                'ruta_original' => $imgNested,
                                                'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imgNested),
                                                'orden' => $ordenProceso,
                                                'es_principal' => $ordenProceso === 1,
                                            ]);
                                            $ordenProceso++;
                                        }
                                    }
                                } elseif (is_string($imagen) && !empty($imagen)) {
                                    PedidosProcesoImagen::create([
                                        'proceso_prenda_detalle_id' => $proceso->id,
                                        'ruta_original' => $imagen,
                                        'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imagen),
                                        'orden' => $ordenProceso,
                                        'es_principal' => $ordenProceso === 1,
                                    ]);
                                    $ordenProceso++;
                                }
                            }
                            if ($ordenProceso > 1) {
                                Log::info('    ✅ Imágenes de proceso guardadas', ['cantidad' => $ordenProceso - 1]);
                            }
                        }
                    }
                }
            }

            // 4️⃣ ACTUALIZAR CANTIDAD TOTAL DEL PEDIDO
            $pedido->update(['cantidad_total' => $cantidadTotalPedido]);

            Log::info('🎉 [CrearPedidoCompletoHandler] Pedido completo persistido', [
                'pedido_id' => $pedido->id,
                'cantidad_total' => $cantidadTotalPedido,
                'prendas' => count($data['items'] ?? []),
            ]);

            return $pedido;
        });
    }
}
