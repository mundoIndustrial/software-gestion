<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Pedidos\DTOs\PrendaBeforeStateSnapshot;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Auth;

class PrendaComparisonService
{
    public function generarResumenCambios(PrendaBeforeStateSnapshot $beforeSnapshot, PrendaPedido $prendaDespues): ?string
    {
        \Log::info('[PrendaComparisonService] Iniciando comparación', [
            'prenda_id' => $prendaDespues->id,
            'prenda_nombre' => $prendaDespues->nombre_prenda,
        ]);

        $cambios = [];

        $cambiosCamposBasicos = $this->compararCamposBasicos($beforeSnapshot, $prendaDespues);
        \Log::info('[PrendaComparisonService] Comparación de campos básicos', [
            'cantidad_cambios' => count($cambiosCamposBasicos),
        ]);
        if (!empty($cambiosCamposBasicos)) {
            $cambios['campos_basicos'] = $cambiosCamposBasicos;
        }

        $cambiosOrigen = $this->compararOrigen($beforeSnapshot, $prendaDespues);
        \Log::info('[PrendaComparisonService] Comparación de origen', [
            'cantidad_cambios' => count($cambiosOrigen),
        ]);
        if (!empty($cambiosOrigen)) {
            $cambios['origen'] = $cambiosOrigen;
        }

        $cambiosTallas = $this->compararTallas($beforeSnapshot->tallasArray, $prendaDespues);
        \Log::info('[PrendaComparisonService] Comparación de tallas', [
            'cantidad_cambios' => count($cambiosTallas),
            'cambios' => $cambiosTallas,
        ]);
        if (!empty($cambiosTallas)) {
            $cambios['tallas'] = $cambiosTallas;
        }

        $cambiosFotos = $this->compararFotos($beforeSnapshot->fotosArray, $prendaDespues);
        \Log::info('[PrendaComparisonService] Comparación de fotos', [
            'cantidad_cambios' => count($cambiosFotos),
        ]);
        if (!empty($cambiosFotos)) {
            $cambios['fotos'] = $cambiosFotos;
        }

        $cambiosColoresTelas = $this->compararColoresTelas($beforeSnapshot->coloresTelasArray, $prendaDespues);
        \Log::info('[PrendaComparisonService] Comparación de colores/telas', [
            'cantidad_cambios' => count($cambiosColoresTelas),
        ]);
        if (!empty($cambiosColoresTelas)) {
            $cambios['colores_telas'] = $cambiosColoresTelas;
        }

        $cambiosProcesos = $this->compararProcesos($beforeSnapshot->procesosArray, $prendaDespues);
        \Log::info('[PrendaComparisonService] Comparación de procesos', [
            'cantidad_cambios' => count($cambiosProcesos),
        ]);
        if (!empty($cambiosProcesos)) {
            $cambios['procesos'] = $cambiosProcesos;
        }

        \Log::info('[PrendaComparisonService] Resumen total de cambios', [
            'total_cambios' => count($cambios),
            'tipos_cambios' => array_keys($cambios),
        ]);

        if (empty($cambios)) {
            \Log::info('[PrendaComparisonService] Sin cambios detectados');
            return null;
        }

        $mensaje = $this->formatearMensajeCambios($prendaDespues, $cambios);
        \Log::info('[PrendaComparisonService] Mensaje generado', [
            'prenda_id' => $prendaDespues->id,
            'mensaje_longitud' => strlen($mensaje),
            'mensaje_preview' => substr($mensaje, 0, 100),
        ]);

        return $mensaje;
    }

    private function compararTallas(array $tallasAntesArray, PrendaPedido $prendaDespues): array
    {
        $tallasAnteriores = collect($tallasAntesArray)
            ->mapWithKeys(function ($talla) {
                return ["{$talla['genero']}_{$talla['talla']}" => $talla];
            });

        $tallasNuevas = $prendaDespues->tallas()
            ->get()
            ->mapWithKeys(function ($talla) {
                return ["{$talla->genero}_{$talla->talla}" => $talla];
            });

        $cambios = [];

        foreach ($tallasNuevas as $key => $tallaNueva) {
            if (!$tallasAnteriores->has($key)) {
                $cambios[] = [
                    'tipo' => 'agregada',
                    'genero' => $tallaNueva->genero,
                    'talla' => $tallaNueva->talla,
                    'cantidad' => $tallaNueva->cantidad,
                ];
            } else {
                $tallaAnterior = $tallasAnteriores->get($key);
                if ($tallaAnterior['cantidad'] != $tallaNueva->cantidad) {
                    $cambios[] = [
                        'tipo' => 'modificada',
                        'genero' => $tallaNueva->genero,
                        'talla' => $tallaNueva->talla,
                        'cantidad_anterior' => $tallaAnterior['cantidad'],
                        'cantidad_nueva' => $tallaNueva->cantidad,
                    ];
                }
            }
        }

        foreach ($tallasAnteriores as $key => $tallaAnterior) {
            if (!$tallasNuevas->has($key)) {
                $cambios[] = [
                    'tipo' => 'removida',
                    'genero' => $tallaAnterior['genero'],
                    'talla' => $tallaAnterior['talla'],
                    'cantidad' => $tallaAnterior['cantidad'],
                ];
            }
        }

        return $cambios;
    }

    private function compararCamposBasicos(PrendaBeforeStateSnapshot $beforeSnapshot, PrendaPedido $prendaDespues): array
    {
        $cambios = [];

        // Comparar nombre_prenda
        if ($beforeSnapshot->nombrePrenda !== $prendaDespues->nombre_prenda) {
            $cambios[] = [
                'tipo' => 'nombre_modificado',
                'nombre_anterior' => $beforeSnapshot->nombrePrenda,
                'nombre_nuevo' => $prendaDespues->nombre_prenda,
            ];
        }

        // Comparar descripción
        if ($beforeSnapshot->descripcion !== $prendaDespues->descripcion) {
            $cambios[] = [
                'tipo' => 'descripcion_modificada',
                'descripcion_anterior' => $beforeSnapshot->descripcion,
                'descripcion_nueva' => $prendaDespues->descripcion,
            ];
        }

        return $cambios;
    }

    private function compararOrigen(PrendaBeforeStateSnapshot $beforeSnapshot, PrendaPedido $prendaDespues): array
    {
        $cambios = [];

        $origenAnterior = (bool) $beforeSnapshot->deBodega ? 'Bodega' : 'Confección';
        $origenNuevo = (bool) ($prendaDespues->de_bodega ?? false) ? 'Bodega' : 'Confección';

        if ($origenAnterior !== $origenNuevo) {
            $cambios[] = [
                'tipo' => 'origen_modificado',
                'origen_anterior' => $origenAnterior,
                'origen_nuevo' => $origenNuevo,
            ];
        }

        return $cambios;
    }

    private function compararFotos(array $fotosAntesArray, PrendaPedido $prendaDespues): array
    {
        $fotosAnteriores = collect($fotosAntesArray)
            ->mapWithKeys(function ($foto) {
                return [$foto['id'] => $foto];
            });

        $fotosNuevas = $prendaDespues->fotos()
            ->get()
            ->mapWithKeys(function ($foto) {
                return [$foto->id => $foto];
            });

        $cambios = [];

        foreach ($fotosNuevas as $id => $fotoNueva) {
            if (!$fotosAnteriores->has($id)) {
                $cambios[] = [
                    'tipo' => 'agregada',
                    'ruta' => $fotoNueva->ruta_original ?? $fotoNueva->ruta_webp,
                ];
            }
        }

        foreach ($fotosAnteriores as $id => $fotoAnterior) {
            if (!$fotosNuevas->has($id)) {
                $cambios[] = [
                    'tipo' => 'removida',
                    'ruta' => $fotoAnterior['ruta_original'] ?? $fotoAnterior['ruta_webp'],
                ];
            }
        }

        return $cambios;
    }

    private function compararColoresTelas(array $coloresAntesArray, PrendaPedido $prendaDespues): array
    {
        $coloresAnteriores = collect($coloresAntesArray)
            ->mapWithKeys(function ($colorTela) {
                return ["{$colorTela['color_id']}_{$colorTela['tela_id']}" => $colorTela];
            });

        $coloresNuevos = $prendaDespues->coloresTelas()
            ->get()
            ->mapWithKeys(function ($colorTela) {
                return ["{$colorTela->color_id}_{$colorTela->tela_id}" => $colorTela];
            });

        $cambios = [];

        foreach ($coloresNuevos as $key => $colorTelaNuevo) {
            if (!$coloresAnteriores->has($key)) {
                $color = $colorTelaNuevo->color()->first();
                $tela = $colorTelaNuevo->tela()->first();
                $colorNombre = $color?->nombre ?? "Color #{$colorTelaNuevo->color_id}";
                $telaNombre = $tela?->nombre ?? "Tela #{$colorTelaNuevo->tela_id}";

                $cambios[] = [
                    'tipo' => 'agregada',
                    'color' => $colorNombre,
                    'tela' => $telaNombre,
                ];
            }
        }

        foreach ($coloresAnteriores as $key => $colorTelaAnterior) {
            if (!$coloresNuevos->has($key)) {
                $color = $colorTelaAnterior['color_id'];
                $tela = $colorTelaAnterior['tela_id'];
                $colorNombre = "Color #{$color}";
                $telaNombre = "Tela #{$tela}";

                $cambios[] = [
                    'tipo' => 'removida',
                    'color' => $colorNombre,
                    'tela' => $telaNombre,
                ];
            }
        }

        return $cambios;
    }

    private function compararProcesos(array $procesosAntesArray, PrendaPedido $prendaDespues): array
    {
        $procesosAnteriores = collect($procesosAntesArray)
            ->mapWithKeys(function ($proceso) {
                return [$proceso['id'] => $proceso];
            });

        $procesosNuevos = $prendaDespues->procesos()
            ->get()
            ->mapWithKeys(function ($proceso) {
                return [$proceso->id => $proceso];
            });

        $cambios = [];

        foreach ($procesosNuevos as $id => $procesoNuevo) {
            if (!$procesosAnteriores->has($id)) {
                $tipoProceso = $procesoNuevo->tipoProces?->nombre ?? "Proceso #{$procesoNuevo->tipo_proceso_id}";
                $cambios[] = [
                    'tipo' => 'agregado',
                    'nombre_proceso' => $tipoProceso,
                    'estado' => $procesoNuevo->estado ?? 'Sin estado',
                ];
            } else {
                $procesoAnterior = $procesosAnteriores->get($id);
                if ($procesoAnterior['estado'] !== $procesoNuevo->estado) {
                    $tipoProceso = $procesoNuevo->tipoProces?->nombre ?? "Proceso #{$procesoNuevo->tipo_proceso_id}";
                    $cambios[] = [
                        'tipo' => 'modificado',
                        'nombre_proceso' => $tipoProceso,
                        'estado_anterior' => $procesoAnterior['estado'] ?? 'Sin estado',
                        'estado_nuevo' => $procesoNuevo->estado ?? 'Sin estado',
                    ];
                }

                if ($procesoAnterior['observaciones'] !== $procesoNuevo->observaciones) {
                    if (!empty($procesoNuevo->observaciones)) {
                        $tipoProceso = $procesoNuevo->tipoProces?->nombre ?? "Proceso #{$procesoNuevo->tipo_proceso_id}";
                        $cambios[] = [
                            'tipo' => 'observaciones_modificadas',
                            'nombre_proceso' => $tipoProceso,
                            'observacion' => $procesoNuevo->observaciones,
                        ];
                    }
                }
            }
        }

        foreach ($procesosAnteriores as $id => $procesoAnterior) {
            if (!$procesosNuevos->has($id)) {
                $tipoProceso = "Proceso #{$procesoAnterior['tipo_proceso_id']}";
                $cambios[] = [
                    'tipo' => 'removido',
                    'nombre_proceso' => $tipoProceso,
                    'estado' => $procesoAnterior['estado'] ?? 'Sin estado',
                ];
            }
        }

        return $cambios;
    }

    private function formatearMensajeCambios(PrendaPedido $prenda, array $cambios): string
    {
        $nombrePrenda = $prenda->nombre_prenda ?? 'Sin nombre';
        $usuario = Auth::user();
        $nombreUsuario = $usuario?->name ?? 'Sistema';
        $fecha = now()->format('d/m/Y, g:i:s a');

        $mensaje = "[MODIFICADA PRENDA] {$nombrePrenda}\n";

        if (!empty($cambios['campos_basicos']) || !empty($cambios['origen'])) {
            $mensaje .= "Información:\n";

            if (!empty($cambios['campos_basicos'])) {
                foreach ($cambios['campos_basicos'] as $cambio) {
                    if ($cambio['tipo'] === 'nombre_modificado') {
                        $mensaje .= "  • Nombre: \"{$cambio['nombre_anterior']}\" → \"{$cambio['nombre_nuevo']}\"\n";
                    } elseif ($cambio['tipo'] === 'descripcion_modificada') {
                        $descAnt = $cambio['descripcion_anterior'] ?? '';
                        $descNueva = $cambio['descripcion_nueva'] ?? '';

                        // Show more characters for descriptions, but keep reasonable length
                        $maxLen = 150;
                        $descAntDisplay = strlen($descAnt) > $maxLen ? substr($descAnt, 0, $maxLen) . '...' : $descAnt;
                        $descNuevaDisplay = strlen($descNueva) > $maxLen ? substr($descNueva, 0, $maxLen) . '...' : $descNueva;

                        $mensaje .= "  • Descripción:\n    Antes: \"{$descAntDisplay}\"\n    Después: \"{$descNuevaDisplay}\"\n";
                    }
                }
            }

            if (!empty($cambios['origen'])) {
                foreach ($cambios['origen'] as $cambio) {
                    if ($cambio['tipo'] === 'origen_modificado') {
                        $mensaje .= "  • Origen: {$cambio['origen_anterior']} → {$cambio['origen_nuevo']}\n";
                    }
                }
            }
        }

        if (!empty($cambios['tallas'])) {
            $mensaje .= "Tallas:\n";
            foreach ($cambios['tallas'] as $cambio) {
                if ($cambio['tipo'] === 'agregada') {
                    $mensaje .= "  • Agregada: {$cambio['genero']} {$cambio['talla']} (cantidad: {$cambio['cantidad']})\n";
                } elseif ($cambio['tipo'] === 'removida') {
                    $mensaje .= "  • Removida: {$cambio['genero']} {$cambio['talla']} (cantidad: {$cambio['cantidad']})\n";
                } elseif ($cambio['tipo'] === 'modificada') {
                    $mensaje .= "  • Modificada: {$cambio['genero']} {$cambio['talla']} ({$cambio['cantidad_anterior']} → {$cambio['cantidad_nueva']})\n";
                }
            }
        }

        if (!empty($cambios['fotos'])) {
            $mensaje .= "Fotos:\n";
            foreach ($cambios['fotos'] as $cambio) {
                if ($cambio['tipo'] === 'agregada') {
                    $mensaje .= "  • Agregada foto\n";
                } elseif ($cambio['tipo'] === 'removida') {
                    $mensaje .= "  • Removida foto\n";
                }
            }
        }

        if (!empty($cambios['colores_telas'])) {
            $mensaje .= "Telas:\n";
            foreach ($cambios['colores_telas'] as $cambio) {
                if ($cambio['tipo'] === 'agregada') {
                    $mensaje .= "  • Agregada: {$cambio['tela']} en {$cambio['color']}\n";
                } elseif ($cambio['tipo'] === 'removida') {
                    $mensaje .= "  • Removida: {$cambio['tela']} en {$cambio['color']}\n";
                }
            }
        }

        if (!empty($cambios['procesos'])) {
            $mensaje .= "Procesos:\n";
            foreach ($cambios['procesos'] as $cambio) {
                if ($cambio['tipo'] === 'agregado') {
                    $mensaje .= "  • Agregado: {$cambio['nombre_proceso']} (estado: {$cambio['estado']})\n";
                } elseif ($cambio['tipo'] === 'removido') {
                    $mensaje .= "  • Removido: {$cambio['nombre_proceso']}\n";
                } elseif ($cambio['tipo'] === 'modificado') {
                    $mensaje .= "  • Modificado: {$cambio['nombre_proceso']} ({$cambio['estado_anterior']} → {$cambio['estado_nuevo']})\n";
                } elseif ($cambio['tipo'] === 'observaciones_modificadas') {
                    $mensaje .= "  • Observación en {$cambio['nombre_proceso']}: {$cambio['observacion']}\n";
                }
            }
        }

        $mensaje .= "({$nombreUsuario} - {$fecha})";

        return trim($mensaje);
    }
}
