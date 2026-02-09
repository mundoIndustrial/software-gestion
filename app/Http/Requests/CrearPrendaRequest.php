<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearPrendaRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud
     */
    public function rules(): array
    {
        return [
            // Datos básicos
            'nombre_producto' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'tipo_prenda' => 'required|string|max:50',
            'genero' => 'nullable|string|max:50',

            // Tallas
            'tallas' => 'required|array|min:1',
            'tallas.*' => 'required|string|in:XS,S,M,L,XL,XXL,XXXL,XXXXL',

            // Variantes
            'variantes' => 'required|array|min:1',
            'variantes.*.tipo_manga_id' => 'nullable|integer|exists:tipo_mangas,id',
            'variantes.*.tipo_broche_id' => 'nullable|integer|exists:tipo_broches,id',
            'variantes.*.tiene_bolsillos' => 'nullable|boolean',
            'variantes.*.tiene_reflectivo' => 'nullable|boolean',
            'variantes.*.descripcion_adicional' => 'nullable|string|max:500',

            // Telas
            'telas' => 'required|array|min:1',
            'telas.*.nombre' => 'required|string|max:255',
            'telas.*.referencia' => 'nullable|string|max:100',
            'telas.*.color' => 'nullable|string|max:100',
            'telas.*.foto' => 'nullable|image|mimes:jpeg,png,webp|max:5120',

            // Fotos
            'fotos' => 'nullable|array',
            'fotos.*.archivo' => 'required|image|mimes:jpeg,png,webp|max:5120',
            'fotos.*.tipo' => 'required|in:foto_prenda,foto_tela',
            'fotos.*.orden' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Obtener los mensajes de validación personalizados
     */
    public function messages(): array
    {
        return [
            'nombre_producto.required' => 'El nombre del producto es requerido',
            'nombre_producto.max' => 'El nombre no puede exceder 255 caracteres',
            'descripcion.required' => 'La descripción es requerida',
            'descripcion.max' => 'La descripción no puede exceder 1000 caracteres',
            'tipo_prenda.required' => 'El tipo de prenda es requerido',
            'tallas.required' => 'Debe seleccionar al menos una talla',
            'tallas.*.in' => 'La talla seleccionada no es válida',
            'variantes.required' => 'Debe agregar al menos una variante',
            'telas.required' => 'Debe agregar al menos una tela',
            'telas.*.nombre.required' => 'El nombre de la tela es requerido',
            'fotos.*.archivo.image' => 'El archivo debe ser una imagen válida',
            'fotos.*.archivo.mimes' => 'La imagen debe ser JPEG, PNG o WebP',
            'fotos.*.archivo.max' => 'La imagen no puede exceder 5MB',
        ];
    }

    /**
     * Preparar los datos para validación
     */
    protected function prepareForValidation(): void
    {
        // Convertir booleanos de string a boolean
        if ($this->has('variantes')) {
            $variantes = $this->input('variantes', []);
            foreach ($variantes as $key => $variante) {
                if (isset($variante['tiene_bolsillos'])) {
                    $variantes[$key]['tiene_bolsillos'] = filter_var(
                        $variante['tiene_bolsillos'],
                        FILTER_VALIDATE_BOOLEAN
                    );
                }
                if (isset($variante['tiene_reflectivo'])) {
                    $variantes[$key]['tiene_reflectivo'] = filter_var(
                        $variante['tiene_reflectivo'],
                        FILTER_VALIDATE_BOOLEAN
                    );
                }
            }
            $this->merge(['variantes' => $variantes]);
        }
    }
}
