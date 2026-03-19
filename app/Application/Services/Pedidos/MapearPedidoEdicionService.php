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
        return $proceso->imagenes->map(function ($img) {
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
            $cantidad = (int) ($talla->cantidad ?? 0);

            if ($talla->es_sobremedida) {
                $tallas['sobremedida'][$claveTalla] = $cantidad;
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

            if ($talla->es_sobremedida) {
                $datosExtendidos['sobremedida'][$claveTalla] = [
                    'ubicaciones' => $ubicaciones,
                    'observaciones' => $observaciones,
                    'imagenes' => [],
                ];
                continue;
            }

            $datosExtendidos[$genero][$claveTalla] = [
                'ubicaciones' => $ubicaciones,
                'observaciones' => $observaciones,
                'imagenes' => [],
            ];
        }

        return $datosExtendidos;
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
            return [
                'id' => $prenda->id,
                'nombre' => $prenda->nombre_prenda,
                'genero' => $prenda->genero,
                'color' => $prenda->color,
                'observaciones' => $prenda->observaciones,
                
                // Cantidades por talla
                'cantidadesPorTalla' => $prenda->tallas->pluck('cantidad', 'talla')->toArray(),
                'generosConTallas' => $prenda->tallas
                    ->groupBy('genero')
                    ->map(fn($tallasGenero) => $tallasGenero->pluck('cantidad', 'talla'))
                    ->toArray(),

                // Telas/colores
                'telasAgregadas' => $prenda->coloresTelas->map(function ($ct) {
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
                })->toArray(),

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

                    return [
                        'id' => $proceso->id,
                        'tipo_proceso_id' => $proceso->tipo_proceso_id,
                        'tipo_proceso' => $tipoProceso,
                        'tipo' => $tipoProceso,
                        'nombre' => $tipoProceso,
                        'tecnica' => $tipoProceso,
                        'ubicaciones' => $this->decodificarJsonSeguro($proceso->ubicaciones),
                        'observaciones' => $proceso->observaciones ?? '',
                        'estado' => $proceso->estado,
                        'modo_tallas' => $proceso->modo_tallas ?? 'generico',
                        'datos_adicionales' => $this->decodificarJsonSeguro($proceso->datos_adicionales),
                        'tallas' => $this->construirTallasProceso($proceso),
                        'datosExtendidos' => $this->construirDatosExtendidosProceso($proceso),
                        'imagenes' => $this->mapearImagenesProceso($proceso),
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
                'epp_id' => $pedidoEpp->epp_id,
                'nombre_completo' => $nombre,
                'nombre_epp' => $nombre,
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
