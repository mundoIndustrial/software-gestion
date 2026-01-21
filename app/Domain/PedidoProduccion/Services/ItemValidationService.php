<?php

namespace App\Domain\PedidoProduccion\Services;

/**
 * Servicio de Dominio para validar items del pedido
 */
class ItemValidationService
{
    /**
     * Validar un item del pedido
     * @param array $item
     * @param int $itemNum - Número del item (para mensajes de error)
     * @return array - Array de errores (vacío si es válido)
     */
    public function validarItem(array $item, int $itemNum): array
    {
        $errores = [];
        $tipo = $item['tipo'] ?? 'cotizacion';

        if ($tipo === 'epp') {
            $errores = array_merge($errores, $this->validarEpp($item, $itemNum));
        } else {
            $errores = array_merge($errores, $this->validarPrenda($item, $itemNum));
        }

        return $errores;
    }

    /**
     * Validar EPP
     */
    private function validarEpp(array $item, int $itemNum): array
    {
        $errores = [];

        if (empty($item['epp_id'])) {
            $errores[] = "Ítem {$itemNum} (EPP): ID del EPP no especificado";
        }

        if (empty($item['cantidad']) || $item['cantidad'] <= 0) {
            $errores[] = "Ítem {$itemNum} (EPP): Cantidad debe ser mayor a 0";
        }

        return $errores;
    }

    /**
     * Validar Prenda
     */
    private function validarPrenda(array $item, int $itemNum): array
    {
        $errores = [];

        if (empty($item['nombre_producto'])) {
            $errores[] = "Ítem {$itemNum}: Prenda no especificada";
        }

        // Validar cantidad_talla
        $cantidadTalla = $item['cantidad_talla'] ?? [];

        // Si es string JSON, parsear
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }

        if (empty($cantidadTalla) || !is_array($cantidadTalla) || count($cantidadTalla) === 0) {
            $errores[] = "Ítem {$itemNum}: Debe especificar cantidades por talla";
        }

        return $errores;
    }

    /**
     * Validar que haya al menos un item
     */
    public function validarHayItems(array $items): array
    {
        if (empty($items)) {
            return ['Debe agregar al menos un ítem al pedido'];
        }
        return [];
    }

    /**
     * Validar todos los items
     */
    public function validarTodosLosItems(array $items): array
    {
        $errores = [];

        foreach ($items as $index => $item) {
            $itemNum = $index + 1;
            $itemErrores = $this->validarItem($item, $itemNum);
            $errores = array_merge($errores, $itemErrores);
        }

        return $errores;
    }
}
