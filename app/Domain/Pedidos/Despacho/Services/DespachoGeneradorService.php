<?php

namespace App\Domain\Pedidos\Despacho\Services;

use App\Models\PedidoProduccion;
use App\Application\Pedidos\Despacho\DTOs\FilaDespachoDTO;
use Illuminate\Support\Collection;

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
        $prendas = $pedido->prendas()
            ->with(['prendaPedidoTallas'])
            ->get();

        foreach ($prendas as $prenda) {
            $tallas = $prenda->prendaPedidoTallas()->get();

            if ($tallas->count() > 0) {
                // Una fila por talla
                // IMPORTANTE: El 'id' debe ser el de prenda_pedido_tallas para guardar correctamente
                foreach ($tallas as $talla) {
                    $filas->push(new FilaDespachoDTO(
                        tipo: 'prenda',
                        id: $talla->id,  // ID de prenda_pedido_tallas
                        tallaId: $talla->id,  // También se guarda aquí para referencia
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
                // Una fila única sin talla (fallback)
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
            $filas->push(new FilaDespachoDTO(
                tipo: 'epp',
                id: $pedidoEpp->id,
                tallaId: null,
                descripcion: "{$pedidoEpp->epp->nombre_completo} ({$pedidoEpp->epp->codigo})",
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
