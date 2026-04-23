<?php

namespace App\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ActualizarBorradorRequest
 *
 * Validación estricta para ACTUALIZAR un borrador existente
 * ✅ El ID viene en la URL (no en payload)
 * ✅ Debe venir pedido JSON válido
 * ❌ NO debe venir pedido_id en el body (ya está en URL)
 */
class ActualizarBorradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // 🔧 CRÍTICO: No debe venir pedido_id en body (ya está en URL)
            'pedido_id' => 'prohibited',

            // ✅ El JSON del pedido es obligatorio
            'pedido' => 'required|string|json',

            // ✅ Archivos opcionales pero deben ser válidos
            'fotos_*' => 'nullable|file|mimes:jpeg,png,webp|max:5120',
            'fotos_tela.*' => 'nullable|file|mimes:jpeg,png,webp|max:5120',

            // Datos de prendas a eliminar
            'prendas_eliminadas' => 'nullable|array',
            'prendas_existentes' => 'nullable|array',
            'nuevas_prendas' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'pedido_id.prohibited' => 'El ID del pedido debe venir en la URL, no en el body.',
            'pedido.required' => 'El JSON del pedido es requerido.',
            'pedido.json' => 'El campo pedido debe ser un JSON válido.',
            'fotos_*.mimes' => 'Las imágenes deben ser JPEG, PNG o WebP.',
            'fotos_*.max' => 'Las imágenes no deben exceder 5MB.',
        ];
    }

    /**
     * Preparar datos para el usecase
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // 🔧 Asegurar que NO hay pedido_id en body
        if (is_array($data)) {
            unset($data['pedido_id']);
        }

        return $data;
    }
}
