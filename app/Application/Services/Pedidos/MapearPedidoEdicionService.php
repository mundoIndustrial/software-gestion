<?php

namespace App\Application\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

/**
 * MapearPedidoEdicionService
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Transformar datos de pedido existente para modo edición
 * - Mapear prendas con sus relaciones
 * - Preparar EPPs para edición
 * 
 * SACADO DEL CONTROLLER (Refactor Fase 9):
 * Antes: Lógica de mapeo inline en crearNuevo() cuando ?edit=ID
 * Ahora: Servicio especializado
 */
class MapearPedidoEdicionService
{
    private function normalizarRutaImagen(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        $ruta = str_replace('\\', '/', $ruta);

        if (str_starts_with($ruta, 'http') || str_starts_with($ruta, 'blob:') || str_starts_with($ruta, 'data:')) {
            return $ruta;
        }

        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }

        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }

        return '/storage/' . ltrim($ruta, '/');
    }

    private function decodificarJsonSeguro($valor, array $default = []): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        if (empty($valor) || !is_string($valor)) {
            return $default;
        }

        $decodificado = json_decode($valor, true);
        return is_array($decodificado) ? $decodificado : $default;
    }

    private function mapearImagenesProceso($proceso): array
    {
        return $this->mapearImagenesColeccion($proceso->imagenes ?? []);
    }

    private function mapearImagenesColeccion($imagenes): array
    {
        if (empty($imagenes)) {
            return [];
        }

        return collect($imagenes)->map(function ($img) {
            $rutaOriginal = $this->normalizarRutaImagen($img->ruta_original ?? null);
            $rutaWebp = $this->normalizarRutaImagen($img->ruta_webp ?? $img->ruta_original ?? null);
            $url = $rutaWebp ?: $rutaOriginal;

            return [
                'id' => $img->id,
                'ruta_original' => $rutaOriginal,
                'ruta_webp' => $rutaWebp,
                'url' => $url,
                'orden' => (int) ($img->orden ?? 0),
                'es_principal' => (bool) ($img->es_principal ?? false),
            ];
        })->values()->toArray();
    }

    private function construirTallasProceso($proceso): array
    {
        $tallas = [
            'dama' => [],
            'caballero' => [],
            'unisex' => [],
            'sobremedida' => [],
        ];

        foreach ($proceso->tallas as $talla) {
            $genero = strtolower((string) ($talla->genero ?? ''));
            if (!in_array($genero, ['dama', 'caballero', 'unisex'], true)) {
                $genero = 'caballero';
            }

            $claveTalla = $talla->es_sobremedida ? ('SOBREMEDIDA__' . ($talla->talla ?? 'SM')) : (string) $talla->talla;
            $cantidad = (int) ($talla->obtenerCantidadTotal());

            if ($talla->es_sobremedida) {
                $tallas['sobremedida'][$claveTalla] = $cantidad;
                continue;
            }

            if ($talla->coloresAsignados->isNotEmpty()) {
                foreach ($talla->coloresAsignados as $colorAsignado) {
                    $colorNombre = strtoupper(trim((string) ($colorAsignado->color_nombre ?? 'SIN_COLOR')));
                    $claveColor = $claveTalla . '__' . $colorNombre;
                    $tallas[$genero][$claveColor] = (int) ($colorAsignado->cantidad ?? 0);
                }
                continue;
            }

            $tallas[$genero][$claveTalla] = $cantidad;
        }

        return $tallas;
    }

    private function construirDatosExtendidosProceso($proceso): array
    {
        $datosExtendidos = [
            'dama' => [],
            'caballero' => [],
            'unisex' => [],
            'sobremedida' => [],
        ];

        foreach ($proceso->tallas as $talla) {
            $genero = strtolower((string) ($talla->genero ?? ''));
            if (!in_array($genero, ['dama', 'caballero', 'unisex'], true)) {
                $genero = 'caballero';
            }

            $claveTalla = $talla->es_sobremedida ? ('SOBREMEDIDA__' . ($talla->talla ?? 'SM')) : (string) $talla->talla;
            $ubicaciones = $this->decodificarJsonSeguro($talla->ubicaciones);
            $observaciones = $talla->observaciones ?? '';
            $imagenesTalla = $this->mapearImagenesColeccion($talla->imagenes ?? []);

            if ($talla->es_sobremedida) {
                $datosExtendidos['sobremedida'][$claveTalla] = [
                    'ubicaciones' => $ubicaciones,
                    'observaciones' => $observaciones,
                    'imagenes' => $imagenesTalla,
                ];
                continue;
            }

            if ($talla->coloresAsignados->isNotEmpty()) {
                foreach ($talla->coloresAsignados as $colorAsignado) {
                    $colorNombre = strtoupper(trim((string) ($colorAsignado->color_nombre ?? 'SIN_COLOR')));
                    $claveColor = $claveTalla . '__' . $colorNombre;
                    $ubicacionesColor = $this->decodificarJsonSeguro($colorAsignado->ubicaciones, $ubicaciones);
                    $observacionColor = $colorAsignado->observaciones ?? $observaciones;

                    $datosExtendidos[$genero][$claveColor] = [
                        'ubicaciones' => $ubicacionesColor,
                        'observaciones' => $observacionColor,
                        'imagenes' => $imagenesTalla,
                    ];
                }
                continue;
            }

            $datosExtendidos[$genero][$claveTalla] = [
                'ubicaciones' => $ubicaciones,
                'observaciones' => $observaciones,
                'imagenes' => $imagenesTalla,
            ];
        }

        return $datosExtendidos;
    }

    private function resolverModoTallasProceso($proceso): string
    {
        $modoActual = strtolower((string) ($proceso->modo_tallas ?? ''));
        $modosValidos = ['generico', 'general', 'especifico'];

        if (in_array($modoActual, $modosValidos, true)) {
            return $modoActual;
        }

        return 'generico';
    }

    private function construirTelasAgregadas($prenda, array $tallaColores): array
    {
        if (!empty($tallaColores)) {
            $telasDesdeTallaColor = [];

            foreach ($tallaColores as $item) {
                $tela = trim((string) ($item['tela_nombre'] ?? ''));
                $color = trim((string) ($item['color_nombre'] ?? ''));
                $referencia = trim((string) ($item['referencia'] ?? ''));
                $observaciones = trim((string) ($item['observaciones'] ?? ''));
                $clave = strtolower($tela . '|' . $color . '|' . $referencia);

                if (!isset($telasDesdeTallaColor[$clave])) {
                    $telasDesdeTallaColor[$clave] = [
                        'tela' => $tela,
                        'nombre_tela' => $tela,
                        'color' => $color,
                        'color_nombre' => $color,
                        'referencia' => $referencia,
                        'observaciones' => $observaciones,
                        'imagenes' => [],
                    ];
                }

                if (!empty($item['imagen_ruta'])) {
                    $ruta = $this->normalizarRutaImagen($item['imagen_ruta']);
                    $telasDesdeTallaColor[$clave]['imagenes'][] = [
                        'ruta_original' => $ruta,
                        'ruta_webp' => $ruta,
                        'url' => $ruta,
                    ];
                }
            }

            if (!empty($telasDesdeTallaColor)) {
                return array_values($telasDesdeTallaColor);
            }
        }

        return $prenda->coloresTelas->map(function ($ct) {
            return [
                'tela' => $ct->tela?->nombre ?? '',
                'nombre_tela' => $ct->tela?->nombre ?? '',
                'color' => $ct->color?->nombre ?? '',
                'color_nombre' => $ct->color?->nombre ?? '',
                'referencia' => $ct->referencia ?? '',
                'imagenes' => $ct->fotos->map(function ($foto) {
                    $rutaOriginal = $this->normalizarRutaImagen($foto->ruta_original ?? null);
                    $rutaWebp = $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->ruta_original ?? null);

                    return [
                        'id' => $foto->id,
                        'ruta_original' => $rutaOriginal,
                        'ruta_webp' => $rutaWebp,
                        'url' => $rutaWebp ?: $rutaOriginal,
                        'orden' => (int) ($foto->orden ?? 0),
                        'es_principal' => (bool) ($foto->es_principal ?? false),
                    ];
                })->values()->toArray(),
            ];
        })->toArray();
    }

    private function detectarTipoFlujoTallas($prenda, array $tallaColores): string
    {
        $persistido = strtolower((string) ($prenda->tipo_flujo_tallas ?? ''));
        $tieneTallaColor = !empty($tallaColores);
        $tieneTallas = $prenda->tallas->isNotEmpty();

        if ($persistido === 'talla_color') {
            return $persistido;
        }

        if ($persistido === 'normal') {
            return $tieneTallaColor ? 'talla_color' : 'normal';
        }

        if ($persistido === 'sin_tallas') {
            if ($tieneTallaColor) {
                return 'talla_color';
            }

            return $tieneTallas ? 'normal' : 'sin_tallas';
        }

        if ($tieneTallaColor) {
            return 'talla_color';
        }

        if ($tieneTallas) {
            return 'normal';
        }

        return 'sin_tallas';
    }

    /**
     * Preparar datos de pedido para modo edición
     * 
     * @param PedidoProduccion $pedido
     * @return array [
     *   'cliente_nombre' => string,
     *   'prendas' => array,
     *   'epps' => array
     * ]
     */
    public function mapearPedidoParaEdicion(PedidoProduccion $pedido): array
    {
        $inicioMapeo = microtime(true);

        // Obtener nombre del cliente
        $clienteNombre = $this->obtenerClienteNombre($pedido);

        // Mapear prendas
        $prendasMapeadas = $this->mapearPrendas($pedido);

        // Mapear EPPs
        $eppsMapeados = $this->mapearEpps($pedido);

        $tiempoMapeo = round((microtime(true) - $inicioMapeo) * 1000, 2);
        Log::info('[MapearPedidoEdicionService] Pedido mapeado para edición', [
            'pedido_id' => $pedido->id,
            'prendas' => count($prendasMapeadas),
            'epps' => count($eppsMapeados),
            'tiempo_ms' => $tiempoMapeo,
        ]);

        return [
            'cliente_nombre' => $clienteNombre,
            'prendas' => $prendasMapeadas,
            'epps' => $eppsMapeados,
        ];
    }

    /**
     * Obtener nombre del cliente desde pedido
     * 
     * @param PedidoProduccion $pedido
     * @return string
     */
    private function obtenerClienteNombre(PedidoProduccion $pedido): string
    {
        // Primero intentar obtener del campo cliente (string) de la tabla
        $nombre = $pedido->getOriginal('cliente');
        
        if (!empty($nombre)) {
            return $nombre;
        }

        // Si no existe, obtener del cliente_id (relación)
        if ($pedido->cliente_id) {
            $cliente = Cliente::find($pedido->cliente_id);
            return $cliente?->nombre ?? '';
        }

        return '';
    }

    /**
     * Mapear prendas del pedido
     * 
     * @param PedidoProduccion $pedido
     * @return array
     */
    private function mapearPrendas(PedidoProduccion $pedido): array
    {
        return $pedido->prendas->map(function ($prenda) {
            $tallaColores = $prenda->tallas
                ->flatMap(function ($talla) {
                    return $talla->coloresAsignados->map(function ($color) use ($talla) {
                        return [
                            'id' => $color->id,
                            'prenda_pedido_talla_id' => $talla->id,
                            'genero' => $talla->genero,
                            'talla' => $talla->talla,
                            'tela_id' => $color->tela_id,
                            'tela_nombre' => $color->tela_nombre,
                            'color_id' => $color->color_id,
                            'color_nombre' => $color->color_nombre,
                            'cantidad' => (int) ($color->cantidad ?? 0),
                            'referencia' => $color->referencia,
                            'imagen_ruta' => $this->normalizarRutaImagen($color->imagen_ruta ?? null),
                            'observaciones' => $color->observaciones,
                        ];
                    });
                })
                ->values()
                ->toArray();

            $asignacionesColoresPorTalla = [];
            foreach ($tallaColores as $color) {
                $genero = strtolower((string) ($color['genero'] ?? ''));
                $talla = strtoupper((string) ($color['talla'] ?? ''));
                $clave = "{$genero}-{$talla}";

                if (!isset($asignacionesColoresPorTalla[$clave])) {
                    $asignacionesColoresPorTalla[$clave] = [
                        'genero' => $genero,
                        'talla' => $talla,
                        'tela' => $color['tela_nombre'] ?? '',
                        'tela_id' => $color['tela_id'] ?? null,
                        'colores' => [],
                    ];
                }

                $asignacionesColoresPorTalla[$clave]['colores'][] = [
                    'nombre' => $color['color_nombre'] ?? '',
                    'color_id' => $color['color_id'] ?? null,
                    'cantidad' => (int) ($color['cantidad'] ?? 0),
                    'referencia' => $color['referencia'] ?? null,
                    'observaciones' => $color['observaciones'] ?? null,
                    'imagen_ruta' => $color['imagen_ruta'] ?? null,
                    'tela' => $color['tela_nombre'] ?? '',
                ];
            }

            $tipoFlujoTallas = $this->detectarTipoFlujoTallas($prenda, $tallaColores);

            return [
                'id' => $prenda->id,
                'nombre' => $prenda->nombre_prenda,
                'nombre_prenda' => $prenda->nombre_prenda,
                'descripcion' => $prenda->descripcion ?? '',
                'de_bodega' => $prenda->de_bodega ?? 1,
                'genero' => $prenda->genero,
                'color' => $prenda->color,
                'observaciones' => $prenda->observaciones,
                
                // Cantidades por talla
                'cantidadesPorTalla' => $prenda->tallas
                    ->mapWithKeys(fn($t) => [$t->talla => (int) $t->obtenerCantidadTotal()])
                    ->toArray(),
                'generosConTallas' => $prenda->tallas
                    ->groupBy('genero')
                    ->map(fn($tallasGenero) => $tallasGenero->mapWithKeys(
                        fn($t) => [$t->talla => (int) $t->obtenerCantidadTotal()]
                    ))
                    ->toArray(),
                'talla_colores' => $tallaColores,
                'asignacionesColoresPorTalla' => $asignacionesColoresPorTalla,
                'asignacionesColores' => $asignacionesColoresPorTalla,
                'tipo_flujo_tallas' => $tipoFlujoTallas,
                'tipoFlujoTallas' => $tipoFlujoTallas,

                // Variantes (primera variante como objeto, para compatibilidad con frontend)
                'variantes' => $prenda->variantes && $prenda->variantes->count() > 0 
                    ? (function($variante) {
                        return [
                            'id' => $variante->id,
                            'tipo_manga_id' => $variante->tipo_manga_id,
                            'tipo_manga' => $variante->tipoManga?->nombre ?? null,
                            'obs_manga' => $variante->manga_obs,
                            'tiene_bolsillos' => $variante->tiene_bolsillos ?? false,
                            'obs_bolsillos' => $variante->bolsillos_obs,
                            'tipo_broche_boton_id' => $variante->tipo_broche_boton_id,
                            'tipo_broche' => $variante->tipoBrocheBoton?->nombre ?? null,
                            'obs_broche' => $variante->broche_boton_obs,
                            'tiene_reflectivo' => $variante->tiene_reflectivo ?? false,
                            'obs_reflectivo' => $variante->obs_reflectivo ?? null,
                        ];
                    })($prenda->variantes->first())
                    : [],

                // Telas/colores
                'telasAgregadas' => $this->construirTelasAgregadas($prenda, $tallaColores),

                // Imágenes
                'fotos' => $prenda->fotos->map(function ($foto) {
                    $rutaOriginal = $this->normalizarRutaImagen($foto->ruta_original ?? null);
                    $rutaWebp = $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->ruta_original ?? null);

                    return [
                        'id' => $foto->id,
                        'url' => $rutaWebp ?: $rutaOriginal,
                        'ruta_original' => $rutaOriginal,
                        'ruta_webp' => $rutaWebp,
                        'principal' => $foto->principal ?? false,
                    ];
                })->toArray(),

                // Procesos
                'procesos' => $prenda->procesos->map(function ($proceso) {
                    $tipoProceso = $proceso->tipoProceso?->nombre
                        ?? $proceso->tipo_proceso
                        ?? $proceso->nombre
                        ?? 'Proceso';

                    $modoTallas = $this->resolverModoTallasProceso($proceso);
                    $ubicaciones = $this->decodificarJsonSeguro($proceso->ubicaciones);
                    $datosAdicionales = $this->decodificarJsonSeguro($proceso->datos_adicionales);
                    $tallas = $this->construirTallasProceso($proceso);
                    $datosExtendidos = $this->construirDatosExtendidosProceso($proceso);
                    $imagenes = $this->mapearImagenesProceso($proceso);

                    $datos = [
                        'nombre' => $tipoProceso,
                        'modo_tallas' => $modoTallas,
                        'ubicaciones' => $ubicaciones,
                        'observaciones' => $proceso->observaciones ?? '',
                        'estado' => $proceso->estado,
                        'datos_adicionales' => $datosAdicionales,
                        'tallas' => $tallas,
                        'datosExtendidos' => $datosExtendidos,
                        'imagenes' => $imagenes,
                    ];

                    return [
                        'id' => $proceso->id,
                        'tipo_proceso_id' => $proceso->tipo_proceso_id,
                        'tipo_proceso' => $tipoProceso,
                        'tipo' => $tipoProceso,
                        'nombre' => $tipoProceso,
                        'tecnica' => $tipoProceso,
                        'ubicaciones' => $ubicaciones,
                        'observaciones' => $proceso->observaciones ?? '',
                        'estado' => $proceso->estado,
                        'modo_tallas' => $modoTallas,
                        'datos_adicionales' => $datosAdicionales,
                        'tallas' => $tallas,
                        'datosExtendidos' => $datosExtendidos,
                        'imagenes' => $imagenes,
                        'datos' => $datos,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Mapear EPPs del pedido para modo edición
     * 
     * @param PedidoProduccion $pedido
     * @return array
     */
    private function mapearEpps(PedidoProduccion $pedido): array
    {
        return $pedido->epps->map(function ($pedidoEpp) {
            $nombre = $pedidoEpp->epp?->nombre_completo ?? 'EPP #' . $pedidoEpp->epp_id;
            
            return [
                'id' => $pedidoEpp->id,
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $pedidoEpp->epp_id,
                'data_epp_original_id' => $pedidoEpp->epp_id,
                'nombre_completo' => $nombre,
                'nombre_epp' => $nombre,
                'nombre' => $nombre,
                'tipo' => 'epp',
                'cantidad' => $pedidoEpp->cantidad,
                'observaciones' => $pedidoEpp->observaciones,
                'imagenes' => $this->mapearImagenesEpp($pedidoEpp),
            ];
        })->toArray();
    }

    /**
     * Mapear imágenes de un EPP
     * 
     * @param mixed $pedidoEpp
     * @return array
     */
    private function mapearImagenesEpp($pedidoEpp): array
    {
        return $pedidoEpp->imagenes->map(function ($img) {
            return [
                'id' => $img->id,
                'ruta_web' => $img->ruta_web,
                'principal' => $img->principal ?? false,
            ];
        })->toArray();
    }
}
