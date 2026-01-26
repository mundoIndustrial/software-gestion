<?php

namespace App\Domain\Epp\Repositories;

use App\Models\PedidoEpp;
use Illuminate\Support\Collection;

/**
 * Implementación de Repositorio Pedido-EPP
 */
class PedidoEppRepository implements PedidoEppRepositoryInterface
{
    /**
     * Obtener EPP de un pedido
     */
    public function obtenerEppDelPedido(int $pedidoId): Collection
    {
        return PedidoEpp::where('pedido_id', $pedidoId)
            ->with('epp.imagenes')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Agregar EPP a un pedido
     */
    public function agregarEppAlPedido(
        int $pedidoId,
        int $eppId,
        int $cantidad,
        ?string $observaciones = null,
        array $imagenes = []
    ): array {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1');
        }

        // Crear o actualizar relación pedido-epp
        $pedidoEpp = PedidoEpp::updateOrCreate(
            [
                'pedido_produccion_id' => $pedidoId,
                'epp_id' => $eppId,
            ],
            [
                'cantidad' => $cantidad,
                'observaciones' => $observaciones,
            ]
        );

        // Procesar imágenes si existen
        if (!empty($imagenes) && is_array($imagenes)) {
            foreach ($imagenes as $index => $imagen) {
                // Aquí irá la lógica para guardar las imágenes en pedido_epp_imagenes
                // Por ahora solo guardamos la metadata
                \DB::table('pedido_epp_imagenes')->updateOrCreate(
                    [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'orden' => $index + 1,
                    ],
                    [
                        'ruta_original' => $imagen,
                        'ruta_web' => $imagen,
                        'principal' => $index === 0 ? 1 : 0,
                    ]
                );
            }
        }

        return [
            'id' => $pedidoEpp->id,
            'pedido_id' => $pedidoId,
            'epp_id' => $eppId,
        ];
    }

    /**
     * Actualizar EPP en pedido
     */
    public function actualizarEppEnPedido(
        int $pedidoId,
        int $eppId,
        array $datos
    ): void {
        PedidoEpp::where('pedido_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->update($datos);
    }

    /**
     * Eliminar EPP de un pedido
     */
    public function eliminarEppDelPedido(int $pedidoId, int $eppId): void
    {
        PedidoEpp::where('pedido_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->delete();
    }

    /**
     * Verificar si un EPP está agregado a un pedido
     */
    public function estaEppEnPedido(int $pedidoId, int $eppId): bool
    {
        return PedidoEpp::where('pedido_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->exists();
    }
}
