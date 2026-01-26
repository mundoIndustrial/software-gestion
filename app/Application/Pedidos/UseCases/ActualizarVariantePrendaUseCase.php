<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarVariantePrendaDTO;
use App\Domain\Pedidos\Commands\ActualizarVariantePrendaCommand;
use App\Domain\Shared\CQRS\CommandBus;
use Illuminate\Support\Facades\Log;

/**
 * ActualizarVariantePrendaUseCase
 * 
 * Orquesta la actualización de variante de prenda con preservación de datos.
 * 
 * Responsabilidades:
 * 1. Recibir DTO desde HTTP
 * 2. Validar sintaxis/negocio básica
 * 3. Crear Command
 * 4. Disparar a través de CommandBus
 * 5. Transformar resultado para respuesta HTTP
 * 
 * IMPORTANTE:
 * - Realiza MERGE de campos (solo actualiza los enviados)
 * - Preserva datos no mencionados en la request
 * - Valida IDs de referencias (tipos_manga, tipos_broche_boton)
 * - No elimina imágenes, procesos o relaciones existentes
 * - Retorna la variante actualizada con todas sus relaciones
 */
final class ActualizarVariantePrendaUseCase
{
    public function __construct(
        private CommandBus $commandBus,
    ) {}

    /**
     * Ejecutar actualización de variante de prenda
     * 
     * @param ActualizarVariantePrendaDTO $dto
     * @return array Datos de la variante actualizada
     * @throws \Exception Si hay error de validación o DB
     */
    public function ejecutar(ActualizarVariantePrendaDTO $dto): array
    {
        Log::info('[ActualizarVariantePrendaUseCase] Iniciando actualización', [
            'pedido_id' => $dto->pedidoId,
            'prenda_id' => $dto->prendaId,
            'campos_a_actualizar' => count($dto->getCamposActualizables()),
        ]);

        try {
            // Validación básica
            $this->validarDTO($dto);

            // Crear comando
            $command = new ActualizarVariantePrendaCommand(
                pedidoId: $dto->pedidoId,
                prendaId: $dto->prendaId,
                tipoMangaId: $dto->tipoMangaId,
                mangaObs: $dto->mangaObs,
                tipoBrocheBotónId: $dto->tipoBrocheBotónId,
                brocheBotónObs: $dto->brocheBotónObs,
                tieneBolsillos: $dto->tieneBolsillos,
                bolsillosObs: $dto->bolsillosObs,
            );

            // Ejecutar comando a través del bus
            $variante = $this->commandBus->dispatch($command);

            // Transformar resultado
            $resultado = $this->transformarVariante($variante);

            Log::info('[ActualizarVariantePrendaUseCase] Actualización exitosa', [
                'variante_id' => $variante->id,
            ]);

            return $resultado;

        } catch (\Exception $e) {
            Log::error('[ActualizarVariantePrendaUseCase] Error en actualización', [
                'pedido_id' => $dto->pedidoId,
                'prenda_id' => $dto->prendaId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Validar DTO
     */
    private function validarDTO(ActualizarVariantePrendaDTO $dto): void
    {
        if ($dto->pedidoId <= 0) {
            throw new \InvalidArgumentException('pedido_id debe ser mayor a 0');
        }

        if ($dto->prendaId <= 0) {
            throw new \InvalidArgumentException('prenda_id debe ser mayor a 0');
        }

        if (!$dto->hayAlgunCampo()) {
            throw new \InvalidArgumentException(
                'Debe enviar al menos un campo a actualizar'
            );
        }

        // Validaciones de longitud de strings
        if ($dto->mangaObs && strlen($dto->mangaObs) > 500) {
            throw new \InvalidArgumentException(
                'manga_obs no puede exceder 500 caracteres'
            );
        }

        if ($dto->brocheBotónObs && strlen($dto->brocheBotónObs) > 500) {
            throw new \InvalidArgumentException(
                'broche_boton_obs no puede exceder 500 caracteres'
            );
        }

        if ($dto->bolsillosObs && strlen($dto->bolsillosObs) > 500) {
            throw new \InvalidArgumentException(
                'bolsillos_obs no puede exceder 500 caracteres'
            );
        }
    }

    /**
     * Transformar modelo a array para respuesta HTTP
     */
    private function transformarVariante(mixed $variante): array
    {
        return [
            'id' => $variante->id,
            'prenda_pedido_id' => $variante->prenda_pedido_id,
            'tipo_manga_id' => $variante->tipo_manga_id,
            'tipo_manga_nombre' => $variante->tipoManga?->nombre ?? null,
            'manga_obs' => $variante->manga_obs,
            'tipo_broche_boton_id' => $variante->tipo_broche_boton_id,
            'tipo_broche_nombre' => $variante->tipoBroche?->nombre ?? null,
            'broche_boton_obs' => $variante->broche_boton_obs,
            'tiene_bolsillos' => (bool) $variante->tiene_bolsillos,
            'bolsillos_obs' => $variante->bolsillos_obs,
            'tiene_reflectivo' => (bool) ($variante->tiene_reflectivo ?? false),
            'created_at' => $variante->created_at?->toIso8601String(),
            'updated_at' => $variante->updated_at?->toIso8601String(),
        ];
    }
}
