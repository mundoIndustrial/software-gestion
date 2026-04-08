<?php

namespace App\Infrastructure\Services\Bodega;

use App\Application\Bodega\Constants\WarehouseConstants;

class BodegaPedidoDetalleUtilsService
{
    public function aplicarRowspans(array $items, string $fieldRowspan, callable $keyFunction): array
    {
        $grupos = [];

        foreach ($items as $index => $item) {
            $clave = $keyFunction($item);
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [];
            }
            $grupos[$clave][] = $index;
        }

        foreach ($grupos as $indices) {
            $rowspan = count($indices);
            foreach ($indices as $itemIndex) {
                $items[$itemIndex][$fieldRowspan] = $itemIndex === $indices[0] ? $rowspan : 0;
            }
        }

        return $items;
    }

    public function obtenerIdArticulo(array $item): string
    {
        if (isset($item['prenda_id']) && !empty($item['prenda_id'])) {
            return 'prenda_' . $item['prenda_id'];
        }

        if (isset($item['pedido_epp_id']) && !empty($item['pedido_epp_id'])) {
            return 'epp_' . $item['pedido_epp_id'];
        }

        $nombreArticulo = $item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? WarehouseConstants::DEFAULT_SIN_NOMBRE;
        return 'nombre_' . md5(strtolower(trim($nombreArticulo)));
    }

    public function obtenerGeneroDelItem(array $item): string
    {
        $genero = '';

        if (isset($item['descripcion']['variantes']) && is_array($item['descripcion']['variantes']) && count($item['descripcion']['variantes']) > 0) {
            foreach ($item['descripcion']['variantes'] as $variante) {
                if (($variante['talla'] ?? '') === ($item['talla'] ?? '')) {
                    $genero = $variante['genero'] ?? '';
                    break;
                }
            }
            if (empty($genero)) {
                $genero = $item['descripcion']['variantes'][0]['genero'] ?? '';
            }
        } elseif (isset($item['genero'])) {
            $genero = $item['genero'];
        }

        $genero = strtoupper(trim($genero));
        return (empty($genero) || $genero === WarehouseConstants::GENERIC_GENDER) ? WarehouseConstants::GENERIC_GENDER : $genero;
    }
}
