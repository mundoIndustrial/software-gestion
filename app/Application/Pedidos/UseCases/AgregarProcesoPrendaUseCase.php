<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\AgregarProcesoPrendaUseCaseContract;

use App\Application\Pedidos\DTOs\AgregarProcesoPrendaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar proceso a una prenda
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * Crea un registro en pedidos_procesos_prenda_detalles
 * Si se proporcionan tallas, las agrupa por genero y puebla:
 * - tallas_dama: JSON con ['S', 'M', 'L'] para genero DAMA
 * - tallas_caballero: JSON con ['XL', 'XXL'] para genero CABALLERO
 * también crea registros en pedidos_procesos_prenda_tallas (uno por cada talla)
 * Tabla: pedidos_procesos_prenda_detalles
 * Antes: 69 lineas | despues: ~59 lineas | Reducción: ~14%
 */
final class AgregarProcesoPrendaUseCase implements AgregarProcesoPrendaUseCaseContract
{
    use ManejaPedidosUseCase;

    public function execute(AgregarProcesoPrendaDTO $dto)
    {
        $prenda = PrendaPedido::find($dto->prendaId);
        $this->validarObjetoExiste($prenda, 'Prenda', $dto->prendaId);

        [$tallasDama, $tallasCaballero] = $this->agruparTallasPorGenero($dto->tallas);
        $ubicaciones = $this->normalizarUbicaciones($dto->ubicaciones);

        $proceso = $prenda->procesos()->create($this->buildProcesoPayload(
            $dto,
            $ubicaciones,
            $tallasDama,
            $tallasCaballero
        ));

        $this->crearRegistrosDeTallas($proceso, $dto->tallas);

        return $proceso;
    }

    private function agruparTallasPorGenero(array $tallas): array
    {
        $tallasDama = [];
        $tallasCaballero = [];

        foreach ($tallas as $talla) {
            $genero = $talla['genero'] ?? null;
            $nombreTalla = $talla['talla'] ?? null;

            if (!$nombreTalla) {
                continue;
            }

            if ($genero === 'DAMA') {
                $tallasDama[] = $nombreTalla;
                continue;
            }

            if ($genero === 'CABALLERO') {
                $tallasCaballero[] = $nombreTalla;
            }
        }

        return [$tallasDama, $tallasCaballero];
    }

    private function normalizarUbicaciones(mixed $ubicaciones): ?array
    {
        if (is_array($ubicaciones)) {
            return $ubicaciones;
        }

        if (!is_string($ubicaciones)) {
            return null;
        }

        $ubicacionesDecodificadas = json_decode($ubicaciones, true);
        return is_array($ubicacionesDecodificadas) ? $ubicacionesDecodificadas : null;
    }

    private function buildProcesoPayload(
        AgregarProcesoPrendaDTO $dto,
        ?array $ubicaciones,
        array $tallasDama,
        array $tallasCaballero
    ): array {
        return [
            'tipo_proceso_id' => $dto->tipo_proceso_id,
            'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : null,
            'observaciones' => $dto->observaciones,
            'tallas_dama' => !empty($tallasDama) ? json_encode($tallasDama) : null,
            'tallas_caballero' => !empty($tallasCaballero) ? json_encode($tallasCaballero) : null,
            'estado' => $dto->estado,
            'notas_rechazo' => $dto->notas_rechazo,
            'aprobado_por' => $dto->aprobado_por,
            'datos_adicionales' => !empty($dto->datos_adicionales) ? json_encode($dto->datos_adicionales) : null,
        ];
    }

    private function crearRegistrosDeTallas(mixed $proceso, array $tallas): void
    {
        foreach ($tallas as $talla) {
            $proceso->tallas()->create([
                'genero' => $talla['genero'],
                'talla' => $talla['talla'],
                'cantidad' => $talla['cantidad'] ?? 0,
            ]);
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {AgregarProcesoPrendaUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}




