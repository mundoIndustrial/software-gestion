<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCotizacionRequest extends FormRequest
{
    /**
     * Autorizar la solicitud
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Obtener las reglas de validación
     */
    public function rules(): array
    {
        return [
            'cliente' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-\.]+$/',
            'tipo' => 'required|in:borrador,enviada',
            'tipo_venta' => 'required_if:tipo,enviada|nullable|string|in:M,D,X',
            'cotizacion_id' => 'nullable|integer|exists:cotizaciones,id',
            
            'productos' => 'required_if:tipo,enviada|array',
            'productos.*.nombre_producto' => 'required|string|max:255',
            'productos.*.descripcion' => 'nullable|string',
            'productos.*.tallas' => 'array',
            'productos.*.tallas.*' => 'string',
            'productos.*.variantes' => 'array',
            'productos.*.variantes.color' => 'nullable|string|max:100',
            'productos.*.variantes.tela' => 'nullable|string|max:100',
            'productos.*.variantes.tela_referencia' => 'nullable|string|max:100',
            'productos.*.variantes.manga_nombre' => 'nullable|string|max:100',
            'productos.*.variantes.obs_manga' => 'nullable|string',
            'productos.*.variantes.obs_bolsillos' => 'nullable|string',
            'productos.*.variantes.obs_broche' => 'nullable|string',
            'productos.*.variantes.obs_reflectivo' => 'nullable|string',
            'productos.*.variantes.genero' => 'nullable|string|in:hombre,mujer,niño,unisex,caballero,dama',
            'productos.*.variantes.tipo' => 'nullable|string',
            'productos.*.variantes.tipo_manga_id' => 'nullable|integer|string',
            'productos.*.variantes.tipo_broche_id' => 'nullable|integer',
            'productos.*.variantes.tiene_bolsillos' => 'nullable|boolean',
            'productos.*.variantes.tiene_reflectivo' => 'nullable|boolean',
            'productos.*.variantes.descripcion_adicional' => 'nullable|string',
            
            'tecnicas' => 'array',
            'tecnicas.*' => 'string',
            
            'ubicaciones' => 'array',
            'ubicaciones.*.seccion' => 'nullable|string',
            'ubicaciones.*.ubicaciones_seleccionadas' => 'nullable|array',
            'ubicaciones.*.ubicaciones_seleccionadas.*' => 'string',
            'ubicaciones.*.observaciones' => 'nullable|string',
            'observaciones_tecnicas' => 'nullable|string',
            
            'imagenes' => 'array',
            'imagenes.*' => 'url',
            
            'especificaciones' => 'array',
            'observaciones_generales' => 'array',
            'observaciones_check' => 'array',
            'observaciones_valor' => 'array',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'cliente.required' => 'El nombre del cliente es requerido',
            'cliente.regex' => 'El cliente contiene caracteres no permitidos',
            'tipo.required' => 'El tipo de cotización es requerido',
            'tipo.in' => 'Tipo de cotización inválido (debe ser "borrador" o "enviada")',
            'tipo_venta.required_if' => 'El tipo de venta (M/D/X) es requerido para cotizaciones enviadas',
            'tipo_venta.in' => 'El tipo de venta debe ser M (Mayoreo), D (Detalle) o X (Otra)',
            'productos.required_if' => 'Los productos son requeridos para cotizaciones enviadas',
            'productos.*.nombre_producto.required' => 'Cada producto debe tener un nombre',
            'productos.*.variantes.genero.in' => 'El género debe ser: hombre, mujer, niño, unisex, caballero o dama',
            'imagenes.*.url' => 'Las imágenes deben ser URLs válidas',
        ];
    }

    /**
     * Preparar los datos para la validación
     */
    public function prepareForValidation(): void
    {
        // Convertir strings a arrays si es necesario
        if (is_string($this->productos ?? null)) {
            $this->merge([
                'productos' => json_decode($this->productos, true) ?? []
            ]);
        }
        
        if (is_string($this->tecnicas ?? null)) {
            $this->merge([
                'tecnicas' => json_decode($this->tecnicas, true) ?? []
            ]);
        }
        
        if (is_string($this->ubicaciones ?? null)) {
            $this->merge([
                'ubicaciones' => json_decode($this->ubicaciones, true) ?? []
            ]);
        }
        
        if (is_string($this->imagenes ?? null)) {
            $this->merge([
                'imagenes' => json_decode($this->imagenes, true) ?? []
            ]);
        }
        
        if (is_string($this->especificaciones ?? null)) {
            $this->merge([
                'especificaciones' => json_decode($this->especificaciones, true) ?? []
            ]);
        }
        
        if (is_string($this->observaciones_generales ?? null)) {
            $this->merge([
                'observaciones_generales' => json_decode($this->observaciones_generales, true) ?? []
            ]);
        }
    }

    /**
     * Manejar validación fallida
     * Devolver JSON en lugar de HTML redirect
     */
    protected function failedValidation(Validator $validator)
    {
        \Log::error('Validación fallida en StoreCotizacionRequest', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->all()
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

