<?php

namespace App\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CrearBorradorRequest
 *
 * Validación estricta para CREAR un nuevo borrador
 * ❌ NO debe venir pedido_id
 * ✅ Debe venir pedido JSON válido
 */
class CrearBorradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // 🔧 CRÍTICO: Si viene pedido_id, rechazar automáticamente
            'pedido_id' => 'prohibited',

            // ✅ El JSON del pedido es obligatorio
            'pedido' => 'required|string|json',

            // ✅ Archivos opcionales pero deben ser válidos
            'fotos_*' => 'nullable|file|mimes:jpeg,png,webp|max:5120',
            'fotos_tela.*' => 'nullable|file|mimes:jpeg,png,webp|max:5120',

            // ✅ Idempotency key recomendada pero no obligatoria
            'X-Idempotency-Key' => 'nullable|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'pedido_id.prohibited' => 'No puede venir un ID de pedido. Use PUT /borrador/{id} para actualizar.',
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

        // 🔧 Asegurar que NO hay pedido_id
        if (is_array($data)) {
            unset($data['pedido_id']);
        }

        return $data;
    }
}
