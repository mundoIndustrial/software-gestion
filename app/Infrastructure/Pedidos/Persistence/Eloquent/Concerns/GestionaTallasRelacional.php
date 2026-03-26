<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent\Concerns;

use App\Models\PrendaPedidoTalla;

/**
 * Trait para gestionar tallas de prendas desde persistencia relacional.
 */
trait GestionaTallasRelacional
{
    public function guardarTallas(int $prendaPedidoId, array $tallas): void
    {
        PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)->delete();

        foreach ($tallas as $genero => $tallasGenero) {
            foreach ($tallasGenero as $talla => $cantidad) {
                if ($cantidad > 0) {
                    PrendaPedidoTalla::create([
                        'prenda_pedido_id' => $prendaPedidoId,
                        'genero' => strtoupper($genero),
                        'talla' => $talla,
                        'cantidad' => (int) $cantidad,
                    ]);
                }
            }
        }
    }

    public function guardarTallasDesdeJson(int $prendaPedidoId, string $tallaJson): void
    {
        $tallas = json_decode($tallaJson, true);
        if (is_array($tallas)) {
            $this->guardarTallas($prendaPedidoId, $tallas);
        }
    }

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

    public function obtenerTallasJson(int $prendaPedidoId): string
    {
        return json_encode($this->obtenerTallas($prendaPedidoId));
    }

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
            PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)
                ->where('genero', strtoupper($genero))
                ->where('talla', $talla)
                ->delete();
        }
    }

    public function obtenerCantidadTotal(int $prendaPedidoId): int
    {
        return PrendaPedidoTalla::where('prenda_pedido_id', $prendaPedidoId)
            ->sum('cantidad');
    }

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
