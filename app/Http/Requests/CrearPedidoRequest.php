<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request con limpieza automática de estructuras JSON complejas
 */
class CrearPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * CRÍTICO: Limpiar datos ANTES de validar
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // Limpiar items
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = array_map(function ($item) {
                return $this->sanitizeItem($item);
            }, $data['items']);
        }

        $this->merge($data);
    }

    /**
     * Sanitizar un item
     */
    private function sanitizeItem(array $item): array
    {
        return [
            'tipo' => $item['tipo'] ?? 'prenda_nueva',
            'nombre_prenda' => $item['nombre_prenda'] ?? $item['nombre_producto'] ?? '',
            'descripcion' => $this->cleanString($item['descripcion'] ?? null),
            'origen' => $item['origen'] ?? 'bodega',
            'de_bodega' => ($item['origen'] ?? 'bodega') === 'bodega' ? 1 : 0,
            'cantidad_talla' => $this->sanitizeCantidadTalla($item['cantidad_talla'] ?? []),
            'variaciones' => $this->sanitizeVariaciones($item['variaciones'] ?? $item['variantes'] ?? []),
            'telas' => $this->sanitizeTelas($item['telas'] ?? []),
            'imagenes' => $this->sanitizeImagenes($item['imagenes'] ?? []),
            'procesos' => $this->sanitizeProcesos($item['procesos'] ?? []),
        ];
    }

    /**
     * Limpiar cantidad_talla
     */
    private function sanitizeCantidadTalla($cantidadTalla): array
    {
        if (!is_array($cantidadTalla)) {
            return ['DAMA' => [], 'CABALLERO' => [], 'UNISEX' => []];
        }

        $cleaned = [];
        foreach (['DAMA', 'CABALLERO', 'UNISEX'] as $genero) {
            $tallas = $cantidadTalla[$genero] ?? [];
            
            if (is_array($tallas) && !$this->isListArray($tallas)) {
                $cleaned[$genero] = array_filter($tallas, function ($cantidad) {
                    return is_numeric($cantidad) && $cantidad > 0;
                });
            } else {
                $cleaned[$genero] = [];
            }
        }

        return $cleaned;
    }

    /**
     * Limpiar variaciones
     */
    private function sanitizeVariaciones($variaciones): array
    {
        if (!is_array($variaciones)) return [];

        return [
            'tipo_manga' => $this->cleanString($variaciones['tipo_manga'] ?? null),
            'obs_manga' => $this->cleanString($variaciones['obs_manga'] ?? null),
            'tiene_bolsillos' => (bool)($variaciones['tiene_bolsillos'] ?? false),
            'obs_bolsillos' => $this->cleanString($variaciones['obs_bolsillos'] ?? null),
            'tipo_broche' => $this->cleanString($variaciones['tipo_broche'] ?? null),
            'obs_broche' => $this->cleanString($variaciones['obs_broche'] ?? null),
            'tipo_broche_boton_id' => $this->cleanInt($variaciones['tipo_broche_boton_id'] ?? null),
            'tipo_manga_id' => $this->cleanInt($variaciones['tipo_manga_id'] ?? null),
            'tiene_reflectivo' => (bool)($variaciones['tiene_reflectivo'] ?? false),
            'obs_reflectivo' => $this->cleanString($variaciones['obs_reflectivo'] ?? null),
        ];
    }

    /**
     * Limpiar telas (CRÍTICO)
     */
    private function sanitizeTelas($telas): array
    {
        if (!is_array($telas)) return [];

        return array_values(array_filter(array_map(function ($tela) {
            if (!is_array($tela)) return null;

            return [
                'tela' => $this->cleanString($tela['tela'] ?? null),
                'color' => $this->cleanString($tela['color'] ?? null),
                'referencia' => $this->cleanString($tela['referencia'] ?? null),
                'tela_id' => $this->cleanInt($tela['tela_id'] ?? null),
                'color_id' => $this->cleanInt($tela['color_id'] ?? null),
                'imagenes' => $this->sanitizeImagenes($tela['imagenes'] ?? []),
            ];
        }, $telas)));
    }

    /**
     * Limpiar imágenes (eliminar [[]], nulls, vacíos)
     */
    private function sanitizeImagenes($imagenes): array
    {
        if (!is_array($imagenes)) return [];

        $flattened = $this->flattenArray($imagenes);
        
        return array_values(array_filter($flattened, function ($img) {
            return is_string($img) && trim($img) !== '';
        }));
    }

    /**
     * Limpiar procesos
     */
    private function sanitizeProcesos($procesos): array
    {
        if (!is_array($procesos)) return [];

        $cleaned = [];
        $tiposProceso = ['reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado'];

        foreach ($tiposProceso as $tipo) {
            if (isset($procesos[$tipo]) && is_array($procesos[$tipo])) {
                $datos = $procesos[$tipo]['datos'] ?? $procesos[$tipo];
                
                $cleaned[$tipo] = [
                    'tipo' => $tipo,
                    'datos' => [
                        'tipo' => $tipo,
                        'ubicaciones' => $this->sanitizeUbicaciones($datos['ubicaciones'] ?? []),
                        'observaciones' => $this->cleanString($datos['observaciones'] ?? null),
                        'tallas' => $this->sanitizeTallasProceso($datos['tallas'] ?? []),
                        'imagenes' => $this->sanitizeImagenes($datos['imagenes'] ?? []),
                    ],
                ];
            }
        }

        return $cleaned;
    }

    /**
     * Limpiar ubicaciones
     */
    private function sanitizeUbicaciones($ubicaciones): array
    {
        if (is_string($ubicaciones)) {
            return [$ubicaciones];
        }

        if (is_array($ubicaciones)) {
            return array_values(array_filter($ubicaciones, function ($u) {
                return is_string($u) && trim($u) !== '';
            }));
        }

        return [];
    }

    /**
     * Limpiar tallas de proceso
     */
    private function sanitizeTallasProceso($tallas): array
    {
        if (!is_array($tallas)) return ['dama' => [], 'caballero' => []];

        $cleaned = ['dama' => [], 'caballero' => []];

        foreach (['dama', 'caballero'] as $genero) {
            $generoTallas = $tallas[$genero] ?? [];
            
            if (is_array($generoTallas) && !$this->isListArray($generoTallas)) {
                $cleaned[$genero] = array_filter($generoTallas, function ($cantidad) {
                    return is_numeric($cantidad) && $cantidad > 0;
                });
            }
        }

        return $cleaned;
    }

    /**
     * Aplanar array recursivamente
     */
    private function flattenArray(array $array, int $depth = 0): array
    {
        if ($depth > 5) return [];

        $result = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $result = array_merge($result, $this->flattenArray($item, $depth + 1));
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Verificar si es array de lista (índices numéricos consecutivos)
     */
    private function isListArray($arr): bool
    {
        if (!is_array($arr)) return false;
        if (empty($arr)) return true;
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Helpers
     */
    private function cleanString($value): ?string
    {
        if ($value === null || $value === '') return null;
        return is_string($value) ? trim($value) : (string)$value;
    }

    private function cleanInt($value): ?int
    {
        if ($value === null || $value === '') return null;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed !== false ? $parsed : null;
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            'cliente' => 'required|string',
            'forma_de_pago' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.nombre_prenda' => 'required|string',
            'items.*.cantidad_talla' => 'required|array',
            'items.*.telas' => 'nullable|array',
            'items.*.imagenes' => 'nullable|array',
            'items.*.procesos' => 'nullable|array',
        ];
    }
}
