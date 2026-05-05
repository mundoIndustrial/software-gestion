<?php

namespace App\Application\Pedidos\Presenters;

use App\Domain\Pedidos\ReadModels\PedidoProduccionListItem;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use stdClass;

final class PedidoTableRowPresenter
{
    /**
     * Adapt any pedido source to the stable shape expected by Blade row component.
     */
    public static function present(mixed $pedido): mixed
    {
        if ($pedido instanceof PedidoProduccionListItem) {
            return self::fromListItem($pedido);
        }

        return $pedido;
    }

    private static function fromListItem(PedidoProduccionListItem $item): stdClass
    {
        $row = new stdClass();

        $row->id = $item->id;
        $row->numero_pedido = $item->numero_pedido;
        $row->numero_pedido_mostrable = $item->numero_pedido;
        $row->numero_pedido_cost = null;
        $row->cliente = $item->cliente;
        $row->estado = $item->estado;
        $row->area = $item->area;
        $row->novedades = $item->novedades;

        // Blade legacy contract
        $row->forma_pago = $item->forma_pago;
        $row->forma_de_pago = $item->forma_pago;
        $row->created_at = self::parseDate($item->fecha_creacion);
        $row->fecha_estimada = $item->fecha_estimada;
        $row->fecha_estimada_de_entrega = $item->fecha_estimada;
        $row->dia_de_entrega = $item->dia_de_entrega;
        $row->dias_restantes_entrega = self::calcularDiasRestantes($item->fecha_creacion, $item->dia_de_entrega);

        // Keep shape compatible with optional relation access in Blade
        $row->asesora = null;
        $row->cotizacion = null;
        $row->prendas = collect();

        return $row;
    }

    private static function parseDate(?string $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function calcularDiasRestantes(?string $fechaCreacion, ?int $diaEntrega): ?int
    {
        if (empty($fechaCreacion) || empty($diaEntrega) || $diaEntrega <= 0) {
            return null;
        }

        try {
            $creacion = Carbon::parse($fechaCreacion)->startOfDay();
            $hoy = now()->startOfDay();

            $diasHabilesTranscurridos = 0;
            $cursor = $creacion->copy()->addDay(); // contar desde el día siguiente

            while ($cursor->lte($hoy)) {
                if ($cursor->isBusinessDay()) {
                    $diasHabilesTranscurridos++;
                }
                $cursor->addDay();
            }

            // El día de creación NO descuenta; el conteo inicia desde el siguiente día hábil.
            $restantes = $diaEntrega - $diasHabilesTranscurridos;
            return max(0, $restantes);
        } catch (\Throwable) {
            return null;
        }
    }
}
