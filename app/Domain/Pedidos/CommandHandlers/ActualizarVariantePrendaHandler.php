<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\Pedidos\Commands\ActualizarVariantePrendaCommand;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use Illuminate\Support\Facades\Log;

/**
 * ActualizarVariantePrendaHandler
 * 
 * Handler para ActualizarVariantePrendaCommand
 * Realiza actualización de variante de prenda con MERGE (preserva datos no enviados)
 * 
 * IMPORTANTE:
 * - NO elimina variantes existentes
 * - Hace update parcial solo de campos enviados
 * - Preserva relaciones con imágenes, colores, telas, procesos
 * - Valida que prenda y variante existan
 * - Maneja errores gracefully
 */
class ActualizarVariantePrendaHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        if (!$command instanceof ActualizarVariantePrendaCommand) {
            throw new \InvalidArgumentException(
                'Command debe ser ActualizarVariantePrendaCommand'
            );
        }

        try {
            Log::info('[ActualizarVariantePrendaHandler] Iniciando actualización de variante', [
                'pedido_id' => $command->getPedidoId(),
                'prenda_id' => $command->getPrendaId(),
                'campos_a_actualizar' => count($command->getCamposActualizables()),
            ]);

            // 1. Validar que el pedido exista
            $pedido = $this->validarPedidoExiste($command->getPedidoId());

            // 2. Validar que la prenda exista y pertenezca al pedido
            $prenda = $this->validarPrendaExisteEnPedido(
                $command->getPrendaId(),
                $command->getPedidoId()
            );

            // 3. Obtener la variante existente
            $variante = $this->obtenerVarianteExistente($prenda);

            if (!$variante) {
                Log::warning('[ActualizarVariantePrendaHandler] No hay variante para actualizar', [
                    'prenda_id' => $command->getPrendaId(),
                ]);

                throw new \Exception(
                    'No hay variante registrada para esta prenda. Use otro endpoint para crear variantes.'
                );
            }

            // 4. Si no hay campos a actualizar, retornar sin cambios
            if (!$command->hayAlgunCampo()) {
                Log::info('[ActualizarVariantePrendaHandler] Sin campos a actualizar, retornando existente', [
                    'variante_id' => $variante->id,
                ]);

                return $variante->load('tipoManga', 'tipoBroche');
            }

            // 5. MERGE: actualizar solo los campos proporcionados
            $this->actualizarVarianteConMerge($variante, $command->getCamposActualizables());

            // 6. Validar los datos actualizados (ej: IDs válidos en DB)
            $this->validarDatosActualizados($variante);

            // 7. Recargar con relaciones
            $variante->load('tipoManga', 'tipoBroche', 'prenda');

            // 8. Log exitoso
            Log::info('[ActualizarVariantePrendaHandler] Variante actualizada exitosamente', [
                'variante_id' => $variante->id,
                'prenda_id' => $variante->prenda_pedido_id,
                'campos_actualizados' => array_keys($command->getCamposActualizables()),
            ]);

            // 9. Invalidar caches relacionados
            $this->invalidarCaches($command->getPedidoId());

            return $variante;

        } catch (\Exception $e) {
            Log::error('[ActualizarVariantePrendaHandler] Error actualizando variante', [
                'pedido_id' => $command->getPedidoId(),
                'prenda_id' => $command->getPrendaId(),
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validar que el pedido existe y está en estado actualizable
     */
    private function validarPedidoExiste(int $pedidoId): mixed
    {
        $pedido = \App\Models\PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            Log::error('[ActualizarVariantePrendaHandler] Pedido no encontrado', [
                'pedido_id' => $pedidoId,
            ]);

            throw new \Exception("Pedido no encontrado (ID: {$pedidoId})");
        }

        // Verificar estado: permitir actualizar solo en estados activos/edición
        $estadosPermitidos = ['activo', 'pendiente', 'no iniciado', 'en edición'];
        if (!in_array(strtolower($pedido->estado), $estadosPermitidos)) {
            Log::warning('[ActualizarVariantePrendaHandler] Pedido en estado no actualizable', [
                'pedido_id' => $pedidoId,
                'estado' => $pedido->estado,
            ]);

            throw new \Exception(
                "No se puede actualizar prenda de pedido en estado: {$pedido->estado}"
            );
        }

        return $pedido;
    }

    /**
     * Validar que la prenda existe y pertenece al pedido
     */
    private function validarPrendaExisteEnPedido(int $prendaId, int $pedidoId): PrendaPedido
    {
        $prenda = PrendaPedido::find($prendaId);

        if (!$prenda) {
            Log::error('[ActualizarVariantePrendaHandler] Prenda no encontrada', [
                'prenda_id' => $prendaId,
            ]);

            throw new \Exception("Prenda no encontrada (ID: {$prendaId})");
        }

        if ($prenda->pedido_produccion_id != $pedidoId) {
            Log::warning('[ActualizarVariantePrendaHandler] Prenda no pertenece al pedido', [
                'prenda_id' => $prendaId,
                'pedido_id_esperado' => $pedidoId,
                'pedido_id_real' => $prenda->pedido_produccion_id,
            ]);

            throw new \Exception(
                "Prenda no pertenece al pedido especificado"
            );
        }

        return $prenda;
    }

    /**
     * Obtener la primera variante de la prenda (generalmente hay solo una)
     */
    private function obtenerVarianteExistente(PrendaPedido $prenda): ?PrendaVariantePed
    {
        return $prenda->variantes()->first();
    }

    /**
     * MERGE: Actualizar solo los campos proporcionados, preservar el resto
     */
    private function actualizarVarianteConMerge(
        PrendaVariantePed $variante,
        array $camposActualizables
    ): void {
        Log::debug('[ActualizarVariantePrendaHandler] Aplicando merge', [
            'variante_id' => $variante->id,
            'campos_nuevos' => $camposActualizables,
            'datos_existentes' => $variante->only(array_keys($camposActualizables)),
        ]);

        // Actualizar solo los campos proporcionados
        foreach ($camposActualizables as $campo => $valor) {
            $variante->$campo = $valor;
        }

        // Guardar cambios
        if (!$variante->save()) {
            Log::error('[ActualizarVariantePrendaHandler] Error guardando variante', [
                'variante_id' => $variante->id,
            ]);

            throw new \Exception('Error guardando cambios en la variante');
        }

        Log::debug('[ActualizarVariantePrendaHandler] Variante guardada en DB', [
            'variante_id' => $variante->id,
        ]);
    }

    /**
     * Validar que los datos actualizados son válidos (ej: IDs existen en tablas ref)
     */
    private function validarDatosActualizados(PrendaVariantePed $variante): void
    {
        // Validar tipo_manga_id si fue actualizado
        if ($variante->tipo_manga_id !== null) {
            $tipoManga = \App\Models\TipoManga::find($variante->tipo_manga_id);

            if (!$tipoManga) {
                Log::error('[ActualizarVariantePrendaHandler] Tipo manga no existe', [
                    'tipo_manga_id' => $variante->tipo_manga_id,
                ]);

                throw new \Exception(
                    "Tipo de manga no válido (ID: {$variante->tipo_manga_id})"
                );
            }
        }

        // Validar tipo_broche_boton_id si fue actualizado
        if ($variante->tipo_broche_boton_id !== null) {
            $tipoBroche = \App\Models\TipoBrocheBoton::find($variante->tipo_broche_boton_id);

            if (!$tipoBroche) {
                Log::error('[ActualizarVariantePrendaHandler] Tipo broche no existe', [
                    'tipo_broche_boton_id' => $variante->tipo_broche_boton_id,
                ]);

                throw new \Exception(
                    "Tipo de broche no válido (ID: {$variante->tipo_broche_boton_id})"
                );
            }
        }
    }

    /**
     * Invalidar caches para que se recargu en la próxima petición
     */
    private function invalidarCaches(int $pedidoId): void
    {
        cache()->forget("pedido_{$pedidoId}_completo");
        cache()->forget("pedido_{$pedidoId}_prendas");
        cache()->forget("pedido_{$pedidoId}_factura");

        Log::debug('[ActualizarVariantePrendaHandler] Caches invalidados', [
            'pedido_id' => $pedidoId,
        ]);
    }
}
