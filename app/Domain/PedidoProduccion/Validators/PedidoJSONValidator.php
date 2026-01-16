<?php

namespace App\Domain\PedidoProduccion\Validators;

use Illuminate\Support\Facades\Validator;

/**
 * Validador: Pedido desde JSON
 * 
 * Valida la estructura y datos del JSON que viene del frontend
 * antes de intentar guardarlo en la BD.
 */
class PedidoJSONValidator
{
    /**
     * Validar estructura completa del pedido
     */
    public static function validar(array $datos): array
    {
        $validator = Validator::make($datos, self::reglas(), self::mensajes());

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    /**
     * Reglas de validación Larevel
     */
    private static function reglas(): array
    {
        return [
            'pedido_produccion_id' => 'required|integer|min:1',
            'prendas' => 'required|array|min:1',
            'prendas.*.nombre_prenda' => 'required|string|max:255',
            'prendas.*.descripcion' => 'nullable|string',
            'prendas.*.genero' => 'nullable|string|in:dama,caballero,mixto',
            'prendas.*.de_bodega' => 'required|boolean',

            // Fotos de prenda
            'prendas.*.fotos_prenda' => 'nullable|array',
            'prendas.*.fotos_prenda.*' => 'nullable|file|mimes:jpeg,png,webp|max:10240', // 10MB

            // Fotos de telas
            'prendas.*.fotos_tela' => 'nullable|array',
            'prendas.*.fotos_tela.*.tela_id' => 'nullable|integer',
            'prendas.*.fotos_tela.*.color_id' => 'nullable|integer',
            'prendas.*.fotos_tela.*.archivo' => 'nullable|file|mimes:jpeg,png,webp|max:10240',
            'prendas.*.fotos_tela.*.ancho' => 'nullable|numeric',
            'prendas.*.fotos_tela.*.alto' => 'nullable|numeric',
            'prendas.*.fotos_tela.*.tamaño' => 'nullable|integer',
            'prendas.*.fotos_tela.*.observaciones' => 'nullable|string',

            // Variantes (tallas)
            'prendas.*.variantes' => 'required|array|min:1',
            'prendas.*.variantes.*.talla' => 'required|string|max:50',
            'prendas.*.variantes.*.cantidad' => 'required|integer|min:1',
            'prendas.*.variantes.*.color_id' => 'nullable|integer',
            'prendas.*.variantes.*.tela_id' => 'nullable|integer',
            'prendas.*.variantes.*.tipo_manga_id' => 'nullable|integer',
            'prendas.*.variantes.*.manga_obs' => 'nullable|string',
            'prendas.*.variantes.*.tipo_broche_boton_id' => 'nullable|integer',
            'prendas.*.variantes.*.broche_boton_obs' => 'nullable|string',
            'prendas.*.variantes.*.tiene_bolsillos' => 'required|boolean',
            'prendas.*.variantes.*.bolsillos_obs' => 'nullable|string',

            // Procesos
            'prendas.*.procesos' => 'nullable|array',
            'prendas.*.procesos.*.tipo_proceso_id' => 'required|integer',
            'prendas.*.procesos.*.ubicaciones' => 'nullable|array',
            'prendas.*.procesos.*.observaciones' => 'nullable|string',
            'prendas.*.procesos.*.tallas_dama' => 'nullable|array',
            'prendas.*.procesos.*.tallas_caballero' => 'nullable|array',
            'prendas.*.procesos.*.imagenes' => 'nullable|array',
            'prendas.*.procesos.*.imagenes.*' => 'nullable|file|mimes:jpeg,png,webp|max:10240',
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    private static function mensajes(): array
    {
        return [
            'pedido_produccion_id.required' => 'El ID del pedido es requerido',
            'prendas.required' => 'Debe agregar al menos una prenda',
            'prendas.*.nombre_prenda.required' => 'El nombre de la prenda es requerido',
            'prendas.*.variantes.required' => 'La prenda debe tener al menos una variante (talla)',
            'prendas.*.variantes.*.talla.required' => 'La talla es requerida en cada variante',
            'prendas.*.variantes.*.cantidad.required' => 'La cantidad es requerida en cada variante',
            'prendas.*.procesos.*.tipo_proceso_id.required' => 'El tipo de proceso es requerido',
        ];
    }
}
