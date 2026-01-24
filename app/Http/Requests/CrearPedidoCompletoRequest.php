<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CrearPedidoCompletoRequest
 * 
 * Valida la creación de un pedido completo con todas sus prendas
 * 
 * Responsabilidad:
 * - Validar estructura del pedido
 * - Validar items/prendas
 * - Validar variaciones como Value Object (no como colección)
 * - Validar procesos, telas, imágenes
 */
class CrearPedidoCompletoRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            // Datos del pedido
            'cliente' => 'required|string|min:2|max:255',
            'forma_de_pago' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string|max:1000',
            
            // Items (prendas)
            'items' => 'required|array|min:1',
            'items.*.tipo' => 'nullable|string|in:prenda_nueva,prenda_bodega,epp',
            'items.*.nombre_prenda' => 'required|string|max:255',
            'items.*.descripcion' => 'nullable|string|max:1000',
            
            // Cantidades por talla (objeto asociativo: {DAMA: {S: 10, M: 20}, CABALLERO: {...}})
            'items.*.cantidad_talla' => 'nullable|array',
            'items.*.cantidad_talla.DAMA' => 'nullable|array',
            'items.*.cantidad_talla.DAMA.*' => 'nullable|integer|min:0',
            'items.*.cantidad_talla.CABALLERO' => 'nullable|array',
            'items.*.cantidad_talla.CABALLERO.*' => 'nullable|integer|min:0',
            'items.*.cantidad_talla.UNISEX' => 'nullable|array',
            'items.*.cantidad_talla.UNISEX.*' => 'nullable|integer|min:0',
            
            // 🎯 VARIACIONES - Value Object (UNA configuración por prenda)
            'items.*.variaciones' => 'nullable|array',
            'items.*.variaciones.tipo_manga' => 'nullable|string|max:100',
            'items.*.variaciones.obs_manga' => 'nullable|string|max:500',
            'items.*.variaciones.tiene_bolsillos' => 'nullable|boolean',
            'items.*.variaciones.obs_bolsillos' => 'nullable|string|max:500',
            'items.*.variaciones.tipo_broche' => 'nullable|string|in:boton,cremallera,velcro,ninguno',
            'items.*.variaciones.obs_broche' => 'nullable|string|max:500',
            'items.*.variaciones.tipo_broche_boton_id' => 'nullable|integer|exists:tipos_broche_boton,id',
            'items.*.variaciones.tiene_reflectivo' => 'nullable|boolean',
            'items.*.variaciones.obs_reflectivo' => 'nullable|string|max:500',
            
            // Procesos (reflectivo, bordado, estampado, etc.)
            'items.*.procesos' => 'nullable|array',
            'items.*.procesos.*.tipo' => 'nullable|string',
            'items.*.procesos.*.datos' => 'nullable|array',
            
            // Telas
            'items.*.telas' => 'nullable|array',
            'items.*.telas.*.tela' => 'nullable|string|max:255',
            'items.*.telas.*.color' => 'nullable|string|max:100',
            'items.*.telas.*.referencia' => 'nullable|string|max:100',
            'items.*.telas.*.imagenes' => 'nullable|array',
            
            // Imágenes de la prenda
            'items.*.imagenes' => 'nullable|array',
            'items.*.imagenes.*.original' => 'nullable|string',
            'items.*.imagenes.*.webp' => 'nullable|string',
            'items.*.imagenes.*.thumbnail' => 'nullable|string',
        ];
    }

    /**
     * Mensajes personalizados
     */
    public function messages(): array
    {
        return [
            'cliente.required' => 'El nombre del cliente es obligatorio',
            'cliente.min' => 'El nombre del cliente debe tener al menos 2 caracteres',
            'items.required' => 'Debe agregar al menos una prenda al pedido',
            'items.min' => 'Debe agregar al menos una prenda al pedido',
            'items.*.nombre_prenda.required' => 'El nombre de la prenda es obligatorio',
            'items.*.nombre_prenda.max' => 'El nombre de la prenda no puede exceder 255 caracteres',
            'items.*.variaciones.tipo_broche.in' => 'El tipo de broche debe ser: boton, cremallera, velcro o ninguno',
            'items.*.variaciones.tipo_broche_boton_id.exists' => 'El tipo de broche/botón seleccionado no existe',
        ];
    }

    /**
     * Preparar datos antes de validación
     * 
     * Normaliza booleanos enviados como strings ("true" -> true)
     */
    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        
        foreach ($items as $index => $item) {
            // Normalizar booleanos en variaciones
            if (isset($item['variaciones'])) {
                $variaciones = $item['variaciones'];
                
                // Convertir strings "true"/"false" a booleanos
                if (isset($variaciones['tiene_bolsillos'])) {
                    $variaciones['tiene_bolsillos'] = filter_var(
                        $variaciones['tiene_bolsillos'], 
                        FILTER_VALIDATE_BOOLEAN, 
                        FILTER_NULL_ON_FAILURE
                    ) ?? false;
                }
                
                if (isset($variaciones['tiene_reflectivo'])) {
                    $variaciones['tiene_reflectivo'] = filter_var(
                        $variaciones['tiene_reflectivo'], 
                        FILTER_VALIDATE_BOOLEAN, 
                        FILTER_NULL_ON_FAILURE
                    ) ?? false;
                }
                
                $items[$index]['variaciones'] = $variaciones;
            }
        }
        
        $this->merge(['items' => $items]);
    }

    /**
     * Obtener nombres de atributos personalizados
     */
    public function attributes(): array
    {
        return [
            'cliente' => 'cliente',
            'forma_de_pago' => 'forma de pago',
            'items' => 'prendas',
            'items.*.nombre_prenda' => 'nombre de la prenda',
            'items.*.variaciones.tipo_manga' => 'tipo de manga',
            'items.*.variaciones.tipo_broche' => 'tipo de broche',
        ];
    }
}
