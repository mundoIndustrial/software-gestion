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
     * Convierte prendas con tallas JSON a array plano de entregas
     * 
     * @param int $pedido - Número de pedido
     * @return array - Array de entregas con estructura: [prenda, talla, cantidad, entregado, pendiente]
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getEntregas(int $pedido): array
    {
        // Obtener la orden con relación de prendas
        $orden = PedidoProduccion::where('numero_pedido', $pedido)
            ->with('prendas')
            ->firstOrFail();

        // Transformar prendas a entregas
        return $orden->prendas()
            ->select('nombre_prenda', 'cantidad_talla')
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
        // Decodificar JSON de cantidad_talla
        $cantidadTalla = $this->decodeTallasJson($prenda->cantidad_talla);

        $resultado = [];
        if (is_array($cantidadTalla)) {
            foreach ($cantidadTalla as $talla => $cantidad) {
                $resultado[] = [
                    'prenda' => $prenda->nombre_prenda,
                    'talla' => $talla,
                    'cantidad' => $cantidad,
                    'total_producido_por_talla' => 0,
                    'total_pendiente_por_talla' => $cantidad
                ];
            }
        }

        return $resultado;
    }

    /**
     * Decodificar JSON de tallas manteniendo compatibilidad
     * 
     * @param mixed $cantidadTalla - String JSON o array
     * @return array|null
     */
    private function decodeTallasJson($cantidadTalla): ?array
    {
        if (is_array($cantidadTalla)) {
            return $cantidadTalla;
        }

        if (is_string($cantidadTalla)) {
            return json_decode($cantidadTalla, true);
        }

        return null;
    }
}
