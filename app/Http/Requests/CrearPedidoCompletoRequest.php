<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
     * Manejar validación fallida retornando JSON
     * 
     * CRÍTICO: Esto asegura que aunque venga como HTML request,
     * retornamos JSON cuando falla la validación
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validación fallida',
            'errors' => $validator->errors()
        ], 422));
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
            
            // ⭐ NUEVA ESTRUCTURA: prendas y epps separados
            'prendas' => 'nullable|array',
            'epps' => 'nullable|array',
            
            // ⭐ BACKWARDS COMPATIBILITY: items (antiguo)
            'items' => 'nullable|array|min:1',
            
            // Reglas para prendas (en nuevo array)
            'prendas.*.tipo' => 'nullable|string|in:prenda_nueva,prenda_bodega,epp',
            'prendas.*.nombre_prenda' => 'required|string|max:255',
            'prendas.*.descripcion' => 'nullable|string|max:1000',
            
            // Cantidades por talla (objeto asociativo: {DAMA: {S: 10, M: 20}, CABALLERO: {...}})
            'prendas.*.cantidad_talla' => 'nullable|array',
            'prendas.*.cantidad_talla.DAMA' => 'nullable|array',
            'prendas.*.cantidad_talla.DAMA.*' => 'nullable|integer|min:0',
            'prendas.*.cantidad_talla.CABALLERO' => 'nullable|array',
            'prendas.*.cantidad_talla.CABALLERO.*' => 'nullable|integer|min:0',
            'prendas.*.cantidad_talla.UNISEX' => 'nullable|array',
            'prendas.*.cantidad_talla.UNISEX.*' => 'nullable|integer|min:0',
            
            // 🎯 VARIACIONES - Value Object (UNA configuración por prenda)
            'prendas.*.variaciones' => 'nullable|array',
            'prendas.*.variaciones.tipo_manga' => 'nullable|string|max:100',
            'prendas.*.variaciones.tipo_manga_id' => 'nullable|integer|exists:tipos_manga,id',
            'prendas.*.variaciones.obs_manga' => 'nullable|string|max:500',
            'prendas.*.variaciones.tiene_bolsillos' => 'nullable|boolean',
            'prendas.*.variaciones.obs_bolsillos' => 'nullable|string|max:500',
            'prendas.*.variaciones.tipo_broche' => 'nullable|string|in:boton,cremallera,velcro,ninguno',
            'prendas.*.variaciones.obs_broche' => 'nullable|string|max:500',
            'prendas.*.variaciones.tipo_broche_boton_id' => 'nullable|integer|exists:tipos_broche_boton,id',
            'prendas.*.variaciones.tiene_reflectivo' => 'nullable|boolean',
            'prendas.*.variaciones.obs_reflectivo' => 'nullable|string|max:500',
            
            // Procesos (reflectivo, bordado, estampado, etc.)
            'prendas.*.procesos' => 'nullable|array',
            'prendas.*.procesos.*.tipo' => 'nullable|string',
            'prendas.*.procesos.*.datos' => 'nullable|array',
            
            // Telas
            'prendas.*.telas' => 'nullable|array',
            'prendas.*.telas.*.tela' => 'nullable|string|max:255',
            'prendas.*.telas.*.color' => 'nullable|string|max:100',
            'prendas.*.telas.*.referencia' => 'nullable|string|max:100',
            'prendas.*.telas.*.imagenes' => 'nullable|array',
            'prendas.*.telas.*.imagenes.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            
            // Imágenes de la prenda (aceptar File objects desde FormData)
            'prendas.*.imagenes' => 'nullable|array',
            'prendas.*.imagenes.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            
            // ⭐ REGLAS PARA EPPs (tabla separada)
            // NOTA: nombre_epp es informativo, se toma de tabla epps.nombre
            'epps.*.epp_id' => 'required|integer|exists:epps,id',
            'epps.*.nombre_epp' => 'nullable|string|max:255',  // Informativo, no obligatorio
            'epps.*.cantidad' => 'required|integer|min:1',
            'epps.*.observaciones' => 'nullable|string|max:500',
            'epps.*.imagenes' => 'nullable|array',
            'epps.*.imagenes.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            
            // BACKWARDS COMPATIBILITY: Items (antiguo formato)
            'items.*.tipo' => 'nullable|string|in:prenda_nueva,prenda_bodega,epp',
            'items.*.nombre_prenda' => 'required_without:prendas|string|max:255',
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
            'items.*.variaciones.tipo_manga_id' => 'nullable|integer|exists:tipos_manga,id',
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
            'items.*.telas.*.imagenes.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            
            // Imágenes de la prenda (aceptar File objects desde FormData)
            'items.*.imagenes' => 'nullable|array',
            'items.*.imagenes.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
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
     * Deserializa SOLO nivel 1 de JSON strings desde FormData:
     * - cantidad_talla (JSON string)
     * - variaciones (JSON string)  
     * - procesos (JSON string) ← TODO EL ÁRBOL deserializado de una vez
     * 
     * Esto evita "Over 9 levels deep" warning porque nunca enviamos
     * estructuras con más de 1 nivel de FormData keys
     */
    protected function prepareForValidation(): void
    {
        \Log::debug('[CrearPedidoCompletoRequest] prepareForValidation iniciado', [
            'cliente' => $this->input('cliente'),
            'items_count' => count($this->input('items', [])),
            'items_keys' => count($this->input('items', [])) > 0 ? array_keys($this->input('items')[0]) : [],
        ]);

        $items = $this->input('items', []);
        
        foreach ($items as $index => $item) {
            // ============================================================
            // DESERIALIZAR SOLO NIVEL 1 - JSON strings desde FormData
            // ============================================================
            
            // 1. Deserializar cantidad_talla
            if (isset($item['cantidad_talla']) && is_string($item['cantidad_talla'])) {
                try {
                    $item['cantidad_talla'] = json_decode($item['cantidad_talla'], true) ?? [];
                } catch (\Exception $e) {
                    $item['cantidad_talla'] = [];
                }
            }
            
            // 2. Deserializar variaciones
            if (isset($item['variaciones']) && is_string($item['variaciones'])) {
                try {
                    $item['variaciones'] = json_decode($item['variaciones'], true) ?? [];
                } catch (\Exception $e) {
                    $item['variaciones'] = [];
                }
            }
            
            // 3. Deserializar procesos COMPLETAMENTE
            // El frontend envía: items[i][procesos] = JSON.stringify(procesos)
            // Esto trae TODO el árbol en un string, incluyendo todas las tallas
            if (isset($item['procesos']) && is_string($item['procesos'])) {
                try {
                    $item['procesos'] = json_decode($item['procesos'], true) ?? [];
                } catch (\Exception $e) {
                    $item['procesos'] = [];
                }
            }
            
            // 4. Normalizar booleanos en variaciones
            if (isset($item['variaciones']) && is_array($item['variaciones'])) {
                $variaciones = $item['variaciones'];
                
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
                
                $item['variaciones'] = $variaciones;
            }
            
            $items[$index] = $item;
        }
        
        \Log::debug('[CrearPedidoCompletoRequest] Datos preparados', [
            'items_count' => count($items),
            'first_item' => count($items) > 0 ? array_keys($items[0]) : [],
        ]);

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

    /**
     * Validar que exista al menos prendas O epps
     * 
     * Permitir pedidos con SOLO prendas, SOLO epps, o ambos
     * Pero NO permitir pedidos vacíos
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $prendas = $this->input('prendas', []);
            $epps = $this->input('epps', []);
            $items = $this->input('items', []);  // Legacy format
            
            $tienePrendas = !empty($prendas) && count($prendas) > 0;
            $tieneEpps = !empty($epps) && count($epps) > 0;
            $tieneItemsLegacy = !empty($items) && count($items) > 0;
            
            // Si no hay prendas, ni epps, ni items legacy, error
            if (!$tienePrendas && !$tieneEpps && !$tieneItemsLegacy) {
                $validator->errors()->add('items', 'Debe agregar al menos una prenda o un EPP');
            }
        });
    }
}
