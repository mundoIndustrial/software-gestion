<?php

namespace App\Infrastructure\Services\Edit;

use App\DTOs\Edit\EditPrendaVariantePedidoDTO;
use App\Infrastructure\Services\Strategies\MergeRelationshipStrategy;
use App\Models\PrendaVariantePed;
use Illuminate\Support\Facades\DB;

/**
 * PrendaVariantePedidoEditService - Servicio de edición segura de variantes
 * 
 * ARQUITECTURA SEPARADA:
 * ──────────────────────
 * 
 * CREACIÓN (Constructor de estado desde DOM):
 * - Extrae datos desde elementos HTML
 * - Construye estructura completa de variante
 * - Ubicación: JavaScript builder en frontend
 * - Método: POST nueva variante
 * 
 * EDICIÓN (Parche de estado):
 * - Actualiza SOLO campos explícitamente enviados
 * - Preserva campos no mencionados
 * - NO reemplaza relaciones de colores/telas
 * - Usa MERGE para actualizar relaciones
 * - Ubicación: variante ya persistida
 * - Método: PATCH variante existente
 * 
 * GARANTÍAS:
 * ✓ Editar no reconstruye desde DOM
 * ✓ Campos simples se actualizan directamente
 * ✓ Colores/telas se mergean, no se reemplazan
 * ✓ Separación clara de responsabilidades
 * ✓ Sin efectos colaterales en otras variantes
 */
class PrendaVariantePedidoEditService
{
    /**
     * Editar variante persistida (operación PATCH)
     * 
     * @param PrendaVariantePed $variante
     * @param EditPrendaVariantePedidoDTO $dto
     * @return array
     * @throws \Exception
     */
    public function edit(PrendaVariantePed $variante, EditPrendaVariantePedidoDTO $dto): array
    {
        DB::beginTransaction();

        try {
            // 1. Actualizar campos simples
            $simpleFields = $dto->getSimpleFields();
            if (!empty($simpleFields)) {
                $this->updateBasicFields($variante, $simpleFields);
            }

            // 2. Actualizar relaciones con MERGE
            $relationships = $dto->getRelationshipFields();
            if (!empty($relationships)) {
                $this->updateRelationships($variante, $relationships);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Variante actualizada exitosamente',
                'variante_id' => $variante->id,
                'fields_updated' => array_keys($dto->getExplicitFields()),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar solo campos simples (no relaciones)
     * 
     * @param PrendaVariantePed $variante
     * @param array $fields
     * @return void
     */
    private function updateBasicFields(PrendaVariantePed $variante, array $fields): void
    {
        $allowed = [
            'tipo_manga_id',
            'tipo_broche_boton_id',
            'tiene_bolsillos',
            'obs_bolsillos',
            'tiene_reflectivo',
            'obs_reflectivo',
        ];

        $updateData = array_intersect_key($fields, array_flip($allowed));

        if (!empty($updateData)) {
            $variante->update($updateData);
        }
    }

    /**
     * Actualizar relaciones mediante MERGE
     * 
     * @param PrendaVariantePed $variante
     * @param array $relationships
     * @return void
     */
    private function updateRelationships(PrendaVariantePed $variante, array $relationships): void
    {
        // MERGE de colores
        if (isset($relationships['colores'])) {
            MergeRelationshipStrategy::mergeColores($variante, $relationships['colores']);
        }

        // MERGE de telas
        if (isset($relationships['telas'])) {
            MergeRelationshipStrategy::mergeTelas($variante, $relationships['telas']);
        }
    }

    /**
     * Editar solo campos simples de una variante
     * 
     * @param PrendaVariantePed $variante
     * @param array $simpleUpdates
     * @return array
     */
    public function updateBasic(PrendaVariantePed $variante, array $simpleUpdates): array
    {
        $dto = new EditPrendaVariantePedidoDTO(
            id: $variante->id,
            tipo_manga_id: $simpleUpdates['tipo_manga_id'] ?? null,
            tipo_broche_boton_id: $simpleUpdates['tipo_broche_boton_id'] ?? null,
            tiene_bolsillos: $simpleUpdates['tiene_bolsillos'] ?? null,
            obs_bolsillos: $simpleUpdates['obs_bolsillos'] ?? null,
            tiene_reflectivo: $simpleUpdates['tiene_reflectivo'] ?? null,
            obs_reflectivo: $simpleUpdates['obs_reflectivo'] ?? null,
        );

        return $this->edit($variante, $dto);
    }

    /**
     * Editar solo colores de una variante (MERGE)
     * 
     * @param PrendaVariantePed $variante
     * @param array $coloresPayload
     * @return array
     */
    public function updateColores(PrendaVariantePed $variante, array $coloresPayload): array
    {
        DB::beginTransaction();

        try {
            MergeRelationshipStrategy::mergeColores($variante, $coloresPayload);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Colores actualizados exitosamente',
                'variante_id' => $variante->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Editar solo telas de una variante (MERGE)
     * 
     * @param PrendaVariantePed $variante
     * @param array $telasPayload
     * @return array
     */
    public function updateTelas(PrendaVariantePed $variante, array $telasPayload): array
    {
        DB::beginTransaction();

        try {
            MergeRelationshipStrategy::mergeTelas($variante, $telasPayload);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Telas actualizadas exitosamente',
                'variante_id' => $variante->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estado actual de una variante
     * 
     * @param PrendaVariantePed $variante
     * @return array
     */
    public function getCurrentState(PrendaVariantePed $variante): array
    {
        return [
            'id' => $variante->id,
            'prenda_pedido_id' => $variante->prenda_pedido_id,
            'tipo_manga_id' => $variante->tipo_manga_id,
            'tipo_broche_boton_id' => $variante->tipo_broche_boton_id,
            'tiene_bolsillos' => $variante->tiene_bolsillos,
            'tiene_reflectivo' => $variante->tiene_reflectivo,
            'colores_count' => $variante->colores()->count(),
            'telas_count' => $variante->telas()->count(),
        ];
    }

    /**
     * Validar que una variante puede ser editada
     * 
     * @param PrendaVariantePed $variante
     * @param EditPrendaVariantePedidoDTO $dto
     * @return bool
     */
    public function canEdit(PrendaVariantePed $variante, EditPrendaVariantePedidoDTO $dto): bool
    {
        // Validar que no intenta editar campos protegidos
        $prohibited = [
            'prenda_pedido_id',
            'id',
            'created_at',
            'updated_at',
        ];

        foreach ($prohibited as $field) {
            if ($dto->hasField($field)) {
                return false;
            }
        }

        return true;
    }
}
