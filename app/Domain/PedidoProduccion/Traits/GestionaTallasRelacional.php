<?php

namespace App\Domain\PedidoProduccion\Traits;

use App\Models\PrendaPedidoTalla;

/**
 * Trait para gestionar tallas de prendas
 * Reemplaza la lógica de JSON cantidad_talla con queries a prenda_pedido_tallas
 */
trait GestionaTallasRelacional
{
    /**
     * Guardar tallas de una prenda desde un array
     * 
     * @param int $prendaPedidoId
     * @param array $tallas Estructura: 
     *   ['DAMA' => ['M' => 10, 'L' => 20], 'CABALLERO' => ['32' => 15]]
     * 
     * @return void
     */
    public function guardarTallas(int $prendaPedidoId, array $tallas): void
    {
        // Limpiar tallas antiguas
        PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)->delete();

        // Insertar nuevas tallas
        foreach ($tallas as $genero => $tallasGenero) {
            foreach ($tallasGenero as $talla => $cantidad) {
                if ($cantidad > 0) {
                    PrendaPedidoTalla::create([
                        'prenda_pedido_id' => $prendaPedidoId,
                        'genero' => strtoupper($genero),
                        'talla' => $talla,
                        'cantidad' => (int)$cantidad,
                    ]);
                }
            }
        }
    }

    /**
     * Guardar tallas desde JSON string (compatibilidad con cantidad_talla JSON)
     * 
     * @param int $prendaPedidoId
     * @param string $tallaJson JSON como string
     * 
     * @return void
     */
    public function guardarTallasDesdeJson(int $prendaPedidoId, string $tallaJson): void
    {
        $tallas = json_decode($tallaJson, true);
        if (is_array($tallas)) {
            $this->guardarTallas($prendaPedidoId, $tallas);
        }
    }

    /**
     * Obtener tallas de una prenda como array estructurado
     * 
     * @param int $prendaPedidoId
     * 
     * @return array
     */
    public function obtenerTallas(int $prendaPedidoId): array
    {
        $tallas = [];
        
        PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)
            ->get()
            ->each(function ($tallaRecord) use (&$tallas) {
                $genero = $tallaRecord->genero;
                if (!isset($tallas[$genero])) {
                    $tallas[$genero] = [];
                }
                $tallas[$genero][$tallaRecord->talla] = $tallaRecord->cantidad;
            });

        return $tallas;
    }

    /**
     * Obtener tallas como JSON (compatibilidad con cantidad_talla)
     * 
     * @param int $prendaPedidoId
     * 
     * @return string JSON encoded
     */
    public function obtenerTallasJson(int $prendaPedidoId): string
    {
        return json_encode($this->obtenerTallas($prendaPedidoId));
    }

    /**
     * Actualizar cantidad de una talla específica
     * 
     * @param int $prendaPedidoId
     * @param string $genero
     * @param string $talla
     * @param int $cantidad
     * 
     * @return void
     */
    public function actualizarTalla(int $prendaPedidoId, string $genero, string $talla, int $cantidad): void
    {
        if ($cantidad > 0) {
            PrendaPedidoTalla::updateOrCreate(
                [
                    'prenda_pedido_id' => $prendaPedidoId,
                    'genero' => strtoupper($genero),
                    'talla' => $talla,
                ],
                ['cantidad' => $cantidad]
            );
        } else {
            // Si cantidad es 0 o negativa, eliminar
            PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)
                ->where('genero', strtoupper($genero))
                ->where('talla', $talla)
                ->delete();
        }
    }

    /**
     * Obtener el total de prendas (suma de todas las cantidades)
     * 
     * @param int $prendaPedidoId
     * 
     * @return int
     */
    public function obtenerCantidadTotal(int $prendaPedidoId): int
    {
        return PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)
            ->sum('cantidad');
    }

    /**
     * Obtener tallas de un género específico
     * 
     * @param int $prendaPedidoId
     * @param string $genero
     * 
     * @return array
     */
    public function obtenerTallasGenero(int $prendaPedidoId, string $genero): array
    {
        $tallas = [];
        
        PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)
            ->where('genero', strtoupper($genero))
            ->get()
            ->each(function ($tallaRecord) use (&$tallas) {
                $tallas[$tallaRecord->talla] = $tallaRecord->cantidad;
            });

        return $tallas;
    }
}
