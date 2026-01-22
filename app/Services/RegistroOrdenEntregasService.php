<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * RegistroOrdenEntregasService
 * 
 * Responsabilidad: Transformar prendas de órdenes al formato de entregas
 * Extrae la lógica de manipulación de datos del controlador
 * 
 * CUMPLE SRP: Solo transforma prendas a entregas
 */
class RegistroOrdenEntregasService
{
    /**
     * Obtener entregas de una orden en formato normalizado
     * Convierte prendas con tallas desde relación a array plano de entregas
     * 
     * @param int $pedido - Número de pedido
     * @return array - Array de entregas con estructura: [prenda, talla, cantidad, entregado, pendiente]
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getEntregas(int $pedido): array
    {
        // Obtener la orden con relación de prendas y tallas
        $orden = PedidoProduccion::where('numero_pedido', $pedido)
            ->with('prendas.tallas')  // Cargar prendas y sus tallas relacionales
            ->firstOrFail();

        // Transformar prendas a entregas
        return $orden->prendas()
            ->with('tallas')  // Asegurar que tallas estén cargadas
            ->get()
            ->flatMap(function($prenda) {
                return $this->transformPrendaToEntregas($prenda);
            })
            ->values()
            ->toArray();
    }

    /**
     * Transformar una prenda individual a array de entregas por talla
     * 
     * @param object $prenda - Modelo PrendaPedido
     * @return array - Array de entregas para la prenda
     */
    private function transformPrendaToEntregas(object $prenda): array
    {
        // Obtener tallas desde la relación (prenda_pedido_tallas)
        $tallasDb = $prenda->tallas ?? [];

        $resultado = [];
        foreach ($tallasDb as $tallaRecord) {
            $resultado[] = [
                'prenda' => $prenda->nombre_prenda,
                'talla' => $tallaRecord->talla,
                'cantidad' => $tallaRecord->cantidad,
                'genero' => $tallaRecord->genero,
                'total_producido_por_talla' => 0,
                'total_pendiente_por_talla' => $tallaRecord->cantidad
            ];
        }

        return $resultado;
    }

}
