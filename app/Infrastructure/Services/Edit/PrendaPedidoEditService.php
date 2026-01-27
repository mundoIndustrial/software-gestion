<?php

namespace App\Infrastructure\Services\Edit;

use App\DTOs\Edit\EditPrendaPedidoDTO;
use App\Infrastructure\Services\Strategies\MergeRelationshipStrategy;
use App\Infrastructure\Services\Validators\PrendaEditSecurityValidator;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

/**
 * PrendaPedidoEditService - Servicio de edición segura de prendas persistidas
 * 
 * ARQUITECTURA SEPARADA:
 * ─────────────────────
 * 
 * CREACIÓN (Constructor de estado):
 * - Extrae TODA la información desde el DOM
 * - Construye estructura completa de prenda
 * - Responsabilidad: PrendaCreationService, PrendaDataBuilder (JS)
 * - Ubicación: nuevo pedido, prenda no persistida
 * 
 * EDICIÓN (Parche de estado):
 * - Actualiza SOLO campos explícitamente enviados
 * - Preserva campos no mencionados
 * - Actualiza relaciones mediante MERGE
 * - NO recrea estructuras completas
 * - Responsabilidad: PrendaPedidoEditService
 * - Ubicación: prenda ya persistida
 * 
 * GARANTÍAS:
 * ✓ Editar no es reconstruir
 * ✓ Campos no enviados se conservan
 * ✓ Relaciones se mergean (no se borran)
 * ✓ Procesos no se ven afectados
 * ✓ Restricciones de negocio se validan
 * 
 * MÉTODOS:
 * - edit(): actualización PATCH completa
 * - updateBasicFields(): solo campos simples
 * - updateRelationships(): solo relaciones con MERGE
 * - applySecurityConstraints(): valida restricciones
 */
class PrendaPedidoEditService
{
    /**
     * Editar prenda persistida (operación PATCH)
     * 
     * @param PrendaPedido $prenda
     * @param EditPrendaPedidoDTO $dto
     * @return array - Resultado de la edición
     * @throws \Exception
     */
    public function edit(PrendaPedido $prenda, EditPrendaPedidoDTO $dto): array
    {
        DB::beginTransaction();

        try {
            // 1. Validar restricciones de negocio
            PrendaEditSecurityValidator::validateEdit($prenda, $dto);

            // 2. Actualizar campos simples
            $simpleFields = $dto->getSimpleFields();
            if (!empty($simpleFields)) {
                $this->updateBasicFields($prenda, $simpleFields);
            }

            // 3. Actualizar relaciones con MERGE
            $relationships = $dto->getRelationshipFields();
            if (!empty($relationships)) {
                $this->updateRelationships($prenda, $relationships);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Prenda actualizada exitosamente',
                'prenda_id' => $prenda->id,
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
     * @param PrendaPedido $prenda
     * @param array $fields
     * @return void
     */
    private function updateBasicFields(PrendaPedido $prenda, array $fields): void
    {
        // Filtrar campos permitidos
        $allowed = [
            'nombre_prenda',
            'descripcion',
            'cantidad',
            'de_bodega',
        ];

        $updateData = array_intersect_key($fields, array_flip($allowed));

        if (!empty($updateData)) {
            $prenda->update($updateData);
        }
    }

    /**
     * Actualizar relaciones mediante MERGE
     * 
     * @param PrendaPedido $prenda
     * @param array $relationships
     * @return void
     */
    private function updateRelationships(PrendaPedido $prenda, array $relationships): void
    {
        // MERGE de tallas
        if (isset($relationships['tallas'])) {
            MergeRelationshipStrategy::mergeTallas($prenda, $relationships['tallas']);
        }

        // MERGE de variantes
        if (isset($relationships['variantes'])) {
            MergeRelationshipStrategy::mergeVariantes($prenda, $relationships['variantes']);
        }

        // MERGE de colores (si existe relación directa)
        if (isset($relationships['colores'])) {
            MergeRelationshipStrategy::mergeColores($prenda, $relationships['colores']);
        }

        // MERGE de telas (si existe relación directa)
        if (isset($relationships['telas'])) {
            MergeRelationshipStrategy::mergeTelas($prenda, $relationships['telas']);
        }
    }

    /**
     * Editar solo campos simples de una prenda
     * 
     * @param PrendaPedido $prenda
     * @param array $simpleUpdates - {nombre_prenda, descripcion, cantidad, de_bodega}
     * @return array
     */
    public function updateBasic(PrendaPedido $prenda, array $simpleUpdates): array
    {
        $dto = new EditPrendaPedidoDTO(
            id: $prenda->id,
            nombre_prenda: $simpleUpdates['nombre_prenda'] ?? null,
            descripcion: $simpleUpdates['descripcion'] ?? null,
            cantidad: $simpleUpdates['cantidad'] ?? null,
            de_bodega: $simpleUpdates['de_bodega'] ?? null,
        );

        return $this->edit($prenda, $dto);
    }

    /**
     * Editar solo tallas de una prenda (MERGE)
     * 
     * @param PrendaPedido $prenda
     * @param array $tallasPayload
     * @return array
     */
    public function updateTallas(PrendaPedido $prenda, array $tallasPayload): array
    {
        DB::beginTransaction();

        try {
            PrendaEditSecurityValidator::validateTallasChange($prenda, $tallasPayload);

            MergeRelationshipStrategy::mergeTallas($prenda, $tallasPayload);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tallas actualizadas exitosamente',
                'prenda_id' => $prenda->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Editar solo variantes de una prenda (MERGE)
     * 
     * @param PrendaPedido $prenda
     * @param array $variantesPayload
     * @return array
     */
    public function updateVariantes(PrendaPedido $prenda, array $variantesPayload): array
    {
        DB::beginTransaction();

        try {
            MergeRelationshipStrategy::mergeVariantes($prenda, $variantesPayload);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Variantes actualizadas exitosamente',
                'prenda_id' => $prenda->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Editar solo una variante específica (MERGE interna)
     * 
     * @param PrendaPedido $prenda
     * @param int $varianteId
     * @param array $updates
     * @return array
     */
    public function updateSingleVariante(PrendaPedido $prenda, int $varianteId, array $updates): array
    {
        DB::beginTransaction();

        try {
            $variante = $prenda->variantes()->findOrFail($varianteId);

            // Actualizar campos permitidos
            $allowed = [
                'tipo_manga_id',
                'tipo_broche_boton_id',
                'tiene_bolsillos',
                'obs_bolsillos',
                'tiene_reflectivo',
                'obs_reflectivo',
            ];

            $updateData = array_intersect_key($updates, array_flip($allowed));

            if (!empty($updateData)) {
                $variante->update($updateData);
            }

            // Actualizar colores y telas si vienen
            if (isset($updates['colores'])) {
                MergeRelationshipStrategy::mergeColores($variante, $updates['colores']);
            }

            if (isset($updates['telas'])) {
                MergeRelationshipStrategy::mergeTelas($variante, $updates['telas']);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Variante actualizada exitosamente',
                'prenda_id' => $prenda->id,
                'variante_id' => $varianteId,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estado actual de una prenda para comparar antes/después edición
     * 
     * @param PrendaPedido $prenda
     * @return array
     */
    public function getCurrentState(PrendaPedido $prenda): array
    {
        return [
            'id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'cantidad' => $prenda->cantidad,
            'de_bodega' => $prenda->de_bodega,
            'tallas_count' => $prenda->tallas()->count(),
            'variantes_count' => $prenda->variantes()->count(),
            'procesos_count' => $prenda->procesos()->count(),
        ];
    }
}
