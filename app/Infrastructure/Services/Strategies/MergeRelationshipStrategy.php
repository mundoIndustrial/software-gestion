<?php

namespace App\Infrastructure\Services\Strategies;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * MergeRelationshipStrategy - Estrategia de actualización MERGE para relaciones
 * 
 * RESPONSABILIDAD:
 * Actualizar relaciones existentes sin eliminar ni recrear automáticamente.
 * 
 * LÓGICA MERGE (3-way strategy):
 * 1. Si viene en payload CON id → UPDATE existente
 * 2. Si viene en payload SIN id → CREATE nuevo
 * 3. Si NO viene en payload → CONSERVA intacto
 * 
 * NUNCA:
 * ✗ Borra relaciones que no vienen en payload
 * ✗ Limpia tablas de relaciones completas
 * ✗ Elimina asociaciones sin request explícito
 * 
 * EJEMPLO:
 * Estado actual: colores [1, 2, 3]
 * Payload: [{'id': 1, 'color_id': 5}, {'color_id': 7}]
 * Resultado: colores [1, 2, 3, 5 (updated), 7 (created)]
 * - Color 1: updateado de id 1 a id 5
 * - Color 2: conservado (no mencionado)
 * - Color 3: conservado (no mencionado)
 * - Nueva relación con color 7: creada
 */
class MergeRelationshipStrategy
{
    /**
     * Actualizar relaciones mediante MERGE
     * 
     * @param Model $parent - Modelo padre
     * @param string $relationship - Nombre de la relación
     * @param array $payload - Datos a mergear
     * @param array $relationshipConfig - Configuración: ['key' => 'field', 'fill' => ['field1', 'field2']]
     * @return void
     */
    public static function merge(
        Model $parent,
        string $relationship,
        array $payload,
        array $relationshipConfig = []
    ): void {
        if (empty($payload)) {
            return;
        }

        $fillables = $relationshipConfig['fill'] ?? [];
        $keyField = $relationshipConfig['key'] ?? 'id';

        foreach ($payload as $item) {
            // UPDATE: viene con ID (actualizar existente)
            if (isset($item[$keyField])) {
                $existingModel = $parent->$relationship()
                    ->where($keyField, $item[$keyField])
                    ->first();

                if ($existingModel) {
                    // Actualizar solo campos permitidos
                    $updateData = array_intersect_key($item, array_flip($fillables));
                    $existingModel->update($updateData);
                }
            } 
            // CREATE: no viene con ID (crear nuevo)
            else {
                $createData = array_intersect_key($item, array_flip($fillables));
                $parent->$relationship()->create($createData);
            }
        }
    }

    /**
     * Mergear colores de prenda variante
     * 
     * @param Model $prendaVariante - PrendaVariantePed
     * @param array $coloresPayload - Array de colores con {id?, color_id, ...}
     * @return void
     */
    public static function mergeColores(Model $prendaVariante, array $coloresPayload): void
    {
        self::merge(
            $prendaVariante,
            'colores',
            $coloresPayload,
            [
                'key' => 'id',
                'fill' => ['color_id'],
            ]
        );
    }

    /**
     * Mergear telas de prenda variante
     * 
     * @param Model $prendaVariante - PrendaVariantePed
     * @param array $telasPayload - Array de telas con {id?, tela_id, ...}
     * @return void
     */
    public static function mergeTelas(Model $prendaVariante, array $telasPayload): void
    {
        self::merge(
            $prendaVariante,
            'telas',
            $telasPayload,
            [
                'key' => 'id',
                'fill' => ['tela_id'],
            ]
        );
    }

    /**
     * Mergear tallas de prenda pedido
     * 
     * @param Model $prendaPedido - PrendaPedido
     * @param array $tallasPayload - Array de tallas con {id?, genero, talla, cantidad}
     * @return void
     */
    public static function mergeTallas(Model $prendaPedido, array $tallasPayload): void
    {
        self::merge(
            $prendaPedido,
            'tallas',
            $tallasPayload,
            [
                'key' => 'id',
                'fill' => ['genero', 'talla', 'cantidad'],
            ]
        );
    }

    /**
     * Mergear variantes de prenda pedido
     * 
     * @param Model $prendaPedido - PrendaPedido
     * @param array $variantesPayload - Array de variantes con {id?, tipo_manga_id, ...}
     * @return void
     */
    public static function mergeVariantes(Model $prendaPedido, array $variantesPayload): void
    {
        self::merge(
            $prendaPedido,
            'variantes',
            $variantesPayload,
            [
                'key' => 'id',
                'fill' => [
                    'tipo_manga_id',
                    'tipo_broche_boton_id',
                    'tiene_bolsillos',
                    'tiene_reflectivo',
                ],
            ]
        );
    }

    /**
     * Obtener IDs que solo están en payload (para validar deletes)
     * 
     * @param Collection $existing
     * @param array $payload
     * @param string $idField
     * @return array
     */
    public static function getOnlyInPayload(Collection $existing, array $payload, string $idField = 'id'): array
    {
        $existingIds = $existing->pluck($idField)->toArray();
        $payloadIds = array_filter(array_column($payload, $idField), fn($id) => $id !== null);
        
        return array_diff($payloadIds, $existingIds);
    }

    /**
     * Obtener IDs que solo están en existing (para validar conservación)
     * 
     * @param Collection $existing
     * @param array $payload
     * @param string $idField
     * @return array
     */
    public static function getOnlyInExisting(Collection $existing, array $payload, string $idField = 'id'): array
    {
        $existingIds = $existing->pluck($idField)->toArray();
        $payloadIds = array_filter(array_column($payload, $idField), fn($id) => $id !== null);
        
        return array_diff($existingIds, $payloadIds);
    }
}
