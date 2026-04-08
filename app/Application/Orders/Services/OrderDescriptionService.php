<?php

namespace App\Application\Orders\Services;

use App\Application\Pedidos\Services\PrendaPedidoDescriptionFormatter;
use Illuminate\Support\Facades\Log;

class OrderDescriptionService
{
    public function __construct(
        private PrendaPedidoDescriptionFormatter $prendaDescriptionFormatter
    ) {
    }

    public function buildDescripcionConTallas($order)
    {
        $descripcionConTallas = '';
        $descripcionBase = $order->descripcion_prendas ?? '';

        Log::info(' [buildDescripcionConTallas] Descripción recibida:', [
            'pedido' => $order->numero_pedido,
            'longitud' => strlen($descripcionBase),
            'comienza_con' => substr($descripcionBase, 0, 100),
            'es_html' => strpos($descripcionBase, '<span') !== false,
            'contiene_important' => strpos($descripcionBase, '!important') !== false,
            'HTML_completo' => $descripcionBase,
        ]);

        if (strpos($descripcionBase, '<span') !== false) {
            Log::info(' [buildDescripcionConTallas] Descripción HTML detectada, devolviendo tal cual');
            return $descripcionBase;
        }

        $esReflectivo = false;
        if ($order->cotizacion && $order->cotizacion->tipoCotizacion) {
            $esReflectivo = ($order->cotizacion->tipoCotizacion->codigo === 'RF');
        }

        if (empty($descripcionBase) && $order->prendas && $order->prendas->count() > 0) {
            Log::info(' [buildDescripcionConTallas] Generando descripción dinámicamente', [
                'pedido' => $order->numero_pedido,
                'total_prendas' => $order->prendas->count(),
            ]);

            $descripciones = $order->prendas->map(function ($prenda, $index) {
                Log::info(' [Prenda ' . ($index + 1) . '] Datos disponibles:', [
                    'nombre' => $prenda->nombre_prenda,
                    'color_id' => $prenda->color_id ?? null,
                    'tela_id' => $prenda->tela_id ?? null,
                    'cantidad_talla' => is_array($prenda->cantidad_talla ?? null) ? count($prenda->cantidad_talla) . ' tallas' : 'NULL',
                ]);

                return $this->prendaDescriptionFormatter->formatDetailed($prenda, $index + 1);
            })->toArray();

            $descripcionBase = implode("\n\n", $descripciones);

            Log::info(' [buildDescripcionConTallas] Descripción generada', [
                'longitud' => strlen($descripcionBase),
                'primeras_lineas' => substr($descripcionBase, 0, 200),
            ]);
        }

        if (!empty($descripcionBase) || ($esReflectivo && $order->prendas && $order->prendas->count() > 0)) {
            if ($esReflectivo) {
                $descripcionConTallas = '';

                Log::info(' [REFLECTIVO] Construyendo descripción reflectivo', [
                    'pedido' => $order->numero_pedido,
                    'esReflectivo' => $esReflectivo,
                    'total_prendas' => $order->prendas ? $order->prendas->count() : 0,
                ]);

                if ($order->prendas && $order->prendas->count() > 0) {
                    foreach ($order->prendas as $index => $prenda) {
                        Log::info(' PRENDA ' . ($index + 1), [
                            'nombre' => $prenda->nombre_prenda,
                            'descripcion_length' => strlen($prenda->descripcion ?? ''),
                            'cantidad_talla' => $prenda->cantidad_talla ?? null,
                            'tiene_reflectivo' => $prenda->reflectivo ? 'SI' : 'NO',
                        ]);

                        if ($index > 0) {
                            $descripcionConTallas .= "\n\n";
                        }

                        if ($prenda->reflectivo) {
                            $reflectivo = $prenda->reflectivo;

                            if (!empty($reflectivo->nombre_producto)) {
                                $descripcionConTallas .= "PRENDA REFLECTIVO: " . strtoupper($reflectivo->nombre_producto);
                            } else {
                                $descripcionConTallas .= "PRENDA REFLECTIVO: " . $prenda->nombre_prenda;
                            }

                            if (!empty($reflectivo->descripcion)) {
                                $descripcionConTallas .= "\n" . $reflectivo->descripcion;
                            }

                            if ($reflectivo->generos && is_array($reflectivo->generos)) {
                                $generosStr = implode(', ', array_map('ucfirst', $reflectivo->generos));
                                $descripcionConTallas .= "\nGENEROS: " . $generosStr;
                            }

                            if ($reflectivo->cantidad_talla && is_array($reflectivo->cantidad_talla)) {
                                foreach ($reflectivo->cantidad_talla as $genero => $tallas) {
                                    if (is_array($tallas)) {
                                        $tallasTexto = [];
                                        foreach ($tallas as $talla => $cantidad) {
                                            if ($cantidad > 0) {
                                                $tallasTexto[] = "$talla: $cantidad";
                                            }
                                        }
                                        if (!empty($tallasTexto)) {
                                            $descripcionConTallas .= "\nTALLAS " . strtoupper($genero) . ": " . implode(', ', $tallasTexto);
                                        }
                                    }
                                }
                            }

                            if ($reflectivo->ubicaciones && is_array($reflectivo->ubicaciones)) {
                                foreach ($reflectivo->ubicaciones as $ubicacion) {
                                    $ubicDesc = "UBICACION: " . ($ubicacion['nombre'] ?? '');
                                    if (!empty($ubicacion['observaciones'])) {
                                        $ubicDesc .= " - " . $ubicacion['observaciones'];
                                    }
                                    $descripcionConTallas .= "\n" . $ubicDesc;
                                }
                            }

                            if (!empty($reflectivo->observaciones_generales)) {
                                $descripcionConTallas .= "\nOBSERVACIONES: " . $reflectivo->observaciones_generales;
                            }
                        } else {
                            if (!empty($prenda->descripcion)) {
                                $descripcionConTallas .= $prenda->descripcion;
                            }

                            if ($prenda->cantidad_talla) {
                                try {
                                    $tallas = is_string($prenda->cantidad_talla)
                                        ? json_decode($prenda->cantidad_talla, true)
                                        : $prenda->cantidad_talla;

                                    if (is_array($tallas) && !empty($tallas)) {
                                        $tallasTexto = [];
                                        foreach ($tallas as $talla => $cantidad) {
                                            if ($cantidad > 0) {
                                                $tallasTexto[] = "$talla: $cantidad";
                                            }
                                        }
                                        if (!empty($tallasTexto)) {
                                            $descripcionConTallas .= "\nTalla: " . implode(', ', $tallasTexto);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Error decodificando tallas: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            } else {
                if (strpos($descripcionBase, 'PRENDA ') !== false) {
                    $prendas = explode('PRENDA ', $descripcionBase);
                    $prendasCount = 0;

                    foreach ($prendas as $index => $prendaBlock) {
                        if ($index === 0 && empty(trim($prendaBlock))) {
                            continue;
                        }

                        $prendaBlock = trim($prendaBlock);
                        if (empty($prendaBlock)) {
                            continue;
                        }

                        preg_match('/^(\\d+):/', $prendaBlock, $matches);
                        $numPrenda = isset($matches[1]) ? intval($matches[1]) : ($prendasCount + 1);

                        $descripcionConTallas .= 'PRENDA ' . $prendaBlock;

                        if ($order->prendas && $order->prendas->count() > 0) {
                            $prendaActual = $order->prendas->where('numero_prenda', $numPrenda)->first();

                            if (!$prendaActual && $prendasCount < $order->prendas->count()) {
                                $prendaActual = $order->prendas[$prendasCount];
                            }

                            if ($prendaActual && $prendaActual->cantidad_talla) {
                                try {
                                    $tallas = is_string($prendaActual->cantidad_talla)
                                        ? json_decode($prendaActual->cantidad_talla, true)
                                        : $prendaActual->cantidad_talla;

                                    if (is_array($tallas) && !empty($tallas)) {
                                        $tallasTexto = [];
                                        foreach ($tallas as $talla => $cantidad) {
                                            if ($cantidad > 0) {
                                                $tallasTexto[] = "$talla: $cantidad";
                                            }
                                        }
                                        if (!empty($tallasTexto)) {
                                            $descripcionConTallas .= "\nTalla: " . implode(', ', $tallasTexto);
                                        }
                                    }
                                } catch (\Exception $e) {
                                }
                            }
                        }

                        $prendasCount++;
                        if ($prendasCount < count($prendas)) {
                            $descripcionConTallas .= "\n\n";
                        }
                    }
                } else {
                    $descripcionConTallas = $descripcionBase;

                    if ($order->prendas && $order->prendas->count() > 0) {
                        $prendaActual = $order->prendas->first();

                        if ($prendaActual && $prendaActual->cantidad_talla) {
                            try {
                                $tallas = is_string($prendaActual->cantidad_talla)
                                    ? json_decode($prendaActual->cantidad_talla, true)
                                    : $prendaActual->cantidad_talla;

                                if (is_array($tallas) && !empty($tallas)) {
                                    $tallasTexto = [];
                                    foreach ($tallas as $talla => $cantidad) {
                                        if ($cantidad > 0) {
                                            $tallasTexto[] = "$talla: $cantidad";
                                        }
                                    }
                                    if (!empty($tallasTexto)) {
                                        $descripcionConTallas .= "\n\nTallas: " . implode(', ', $tallasTexto);
                                    }
                                }
                            } catch (\Exception $e) {
                            }
                        }
                    }
                }
            }
        }

        if (empty($descripcionConTallas)) {
            $descripcionConTallas = $descripcionBase;
        }

        return $descripcionConTallas;
    }
}
