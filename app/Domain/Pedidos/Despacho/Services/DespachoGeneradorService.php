<?php

namespace App\Domain\Pedidos\Despacho\Services;

use App\Models\PedidoProduccion;
use App\Application\Pedidos\Despacho\DTOs\FilaDespachoDTO;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * DespachoGeneradorService
 * 
 * Domain Service que encapsula la lógica de negocio para generar
 * la estructura de despacho a partir de un pedido.
 * 
 * Responsabilidades:
 * - Obtener prendas con tallas
 * - Obtener EPP
 * - Unificar en una estructura común (FilaDespachoDTO)
 * - Validar que los datos sean consistentes
 */
class DespachoGeneradorService
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase
    ) {}
    /**
     * Generar todas las filas de despacho (prendas + EPP)
     * 
     * @param PedidoProduccion $pedido
     * @return Collection<FilaDespachoDTO>
     */
    public function generarFilasDespacho(PedidoProduccion $pedido): Collection
    {
        $filas = collect();

        // Agregar prendas con tallas
        $this->agregarPrendas($pedido, $filas);

        // Agregar EPP
        $this->agregarEpp($pedido, $filas);

        return $filas;
    }

    /**
     * Generar filas solo de prendas
     * 
     * @param PedidoProduccion $pedido
     * @return Collection<FilaDespachoDTO>
     */
    public function generarPrendas(PedidoProduccion $pedido): Collection
    {
        $filas = collect();
        $this->agregarPrendas($pedido, $filas);
        return $filas;
    }

    /**
     * Generar filas solo de EPP
     * 
     * @param PedidoProduccion $pedido
     * @return Collection<FilaDespachoDTO>
     */
    public function generarEpp(PedidoProduccion $pedido): Collection
    {
        $filas = collect();
        $this->agregarEpp($pedido, $filas);
        return $filas;
    }

    /**
     * Agregar prendas con tallas a la colección de filas
     */
    private function agregarPrendas(PedidoProduccion $pedido, Collection $filas): void
    {
        try {
            // Obtener datos COMPLETOS del pedido (con procesos, variantes, etc)
            $datos = $this->obtenerPedidoUseCase->ejecutar($pedido->id);
            
            if (!isset($datos->prendas) || !is_array($datos->prendas)) {
                Log::warning('[DespachoGenerador] No se encontraron prendas en ObtenerPedidoUseCase', [
                    'pedido_id' => $pedido->id
                ]);
                return;
            }

            foreach ($datos->prendas as $prendaEnriquecida) {
                // La prenda ya viene con procesos, variantes, manga, broche, bolsillos desde ObtenerPedidoUseCase
                $variantes = $prendaEnriquecida['variantes'] ?? [];
                
                if (count($variantes) > 0) {
                    // Una fila por variante (que representa cada TALLA)
                    foreach ($variantes as $variante) {
                        $talla = $variante['talla'] ?? '—';
                        $cantidad = $variante['cantidad'] ?? 0;
                        $tallaId = $variante['talla_id'] ?? null;  //  Obtener ID de la talla
                        $genero = $variante['genero'] ?? null;  //  Obtener género
                        
                        $filas->push(new FilaDespachoDTO(
                            tipo: 'prenda',
                            id: $prendaEnriquecida['id'] ?? null,
                            tallaId: $tallaId,  //  Usar ID de la talla
                            descripcion: "{$prendaEnriquecida['nombre_prenda']} - {$talla}",
                            cantidadTotal: (int)$cantidad,
                            talla: $talla,
                            genero: $genero,  //  Usar género de la talla
                            objetoPrenda: $prendaEnriquecida,  //  Datos completos con procesos, variantes, etc
                            objetoTalla: null,
                            objetoEpp: null,
                        ));
                    }
                } else {
                    // Fallback: Sin variantes
                    $filas->push(new FilaDespachoDTO(
                        tipo: 'prenda',
                        id: $prendaEnriquecida['id'] ?? null,
                        tallaId: null,
                        descripcion: $prendaEnriquecida['nombre_prenda'] ?? 'Prenda sin nombre',
                        cantidadTotal: (int)($prendaEnriquecida['cantidad_total'] ?? 0),
                        talla: '—',
                        genero: null,
                        objetoPrenda: $prendaEnriquecida,
                        objetoTalla: null,
                        objetoEpp: null,
                    ));
                }
            }
        } catch (\Exception $e) {
            Log::error('[DespachoGenerador] Error al obtener prendas completas', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: usar datos básicos del modelo
            $this->agregarPrendasFallback($pedido, $filas);
        }
    }
    
    /**
     * Fallback: Agregar prendas sin datos completos (si ObtenerPedidoUseCase falla)
     */
    private function agregarPrendasFallback(PedidoProduccion $pedido, Collection $filas): void
    {
        $prendas = $pedido->prendas()
            ->with(['prendaPedidoTallas'])
            ->get();

        foreach ($prendas as $prenda) {
            $tallas = $prenda->prendaPedidoTallas()->get();

            if ($tallas->count() > 0) {
                foreach ($tallas as $talla) {
                    $filas->push(new FilaDespachoDTO(
                        tipo: 'prenda',
                        id: $talla->id,
                        tallaId: $talla->id,
                        descripcion: "{$prenda->nombre_prenda} - {$talla->genero}",
                        cantidadTotal: $talla->cantidad,
                        talla: $talla->talla,
                        genero: $talla->genero,
                        objetoPrenda: $prenda->toArray(),
                        objetoTalla: $talla->toArray(),
                        objetoEpp: null,
                    ));
                }
            } else {
                $filas->push(new FilaDespachoDTO(
                    tipo: 'prenda',
                    id: $prenda->id,
                    tallaId: null,
                    descripcion: $prenda->nombre_prenda,
                    cantidadTotal: $prenda->cantidad ?? 0,
                    talla: '—',
                    genero: null,
                    objetoPrenda: $prenda->toArray(),
                    objetoTalla: null,
                    objetoEpp: null,
                ));
            }
        }
    }

    /**
     * Agregar EPP a la colección de filas
     */
    private function agregarEpp(PedidoProduccion $pedido, Collection $filas): void
    {
        $epps = $pedido->epps()
            ->with(['epp'])
            ->get();

        foreach ($epps as $pedidoEpp) {
            $descripcion = $pedidoEpp->epp->nombre_completo;
                if (!empty($pedidoEpp->epp->codigo)) {
                    $descripcion .= " ({$pedidoEpp->epp->codigo})";
                }
                
                $filas->push(new FilaDespachoDTO(
                    tipo: 'epp',
                    id: $pedidoEpp->id,
                    tallaId: null,
                    descripcion: $descripcion,
                    cantidadTotal: $pedidoEpp->cantidad,
                    talla: '—',
                    genero: null,
                    objetoPrenda: null,
                    objetoTalla: null,
                    objetoEpp: $pedidoEpp->toArray(),
                ));
        }
    }
}
