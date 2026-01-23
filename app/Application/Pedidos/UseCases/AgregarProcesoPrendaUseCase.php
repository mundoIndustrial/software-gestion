<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarProcesoPrendaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar proceso a una prenda
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Crea un registro en pedidos_procesos_prenda_detalles
 * Si se proporcionan tallas, las agrupa por genero y puebla:
 * - tallas_dama: JSON con ['S', 'M', 'L'] para genero DAMA
 * - tallas_caballero: JSON con ['XL', 'XXL'] para genero CABALLERO
 * 
 * También crea registros en pedidos_procesos_prenda_tallas (uno por cada talla)
 * 
 * Tabla: pedidos_procesos_prenda_detalles
 * 
 * Antes: 69 líneas | Después: ~59 líneas | Reducción: ~14%
 */
final class AgregarProcesoPrendaUseCase
{
    use ManejaPedidosUseCase;

    public function execute(AgregarProcesoPrendaDTO $dto)
    {
        // CENTRALIZADO: Validar prenda existe (trait)
        $prenda = $this->validarObjetoExiste(
            PrendaPedido::find($dto->prendaId),
            "Prenda con ID {$dto->prendaId}"
        );

        // Agrupar tallas por genero
        $tallasDama = [];
        $tallasCaballero = [];
        
        if (!empty($dto->tallas)) {
            foreach ($dto->tallas as $talla) {
                $genero = $talla['genero'] ?? null;
                $nombreTalla = $talla['talla'] ?? null;
                
                if ($genero === 'DAMA' && $nombreTalla) {
                    $tallasDama[] = $nombreTalla;
                } elseif ($genero === 'CABALLERO' && $nombreTalla) {
                    $tallasCaballero[] = $nombreTalla;
                }
            }
        }

        // Crear proceso con tallas agrupadas en JSON
        $proceso = $prenda->procesos()->create([
            'tipo_proceso_id' => $dto->tipo_proceso_id,
            'ubicaciones' => !empty($dto->ubicaciones) ? json_encode($dto->ubicaciones) : null,
            'observaciones' => $dto->observaciones,
            'tallas_dama' => !empty($tallasDama) ? json_encode($tallasDama) : null,
            'tallas_caballero' => !empty($tallasCaballero) ? json_encode($tallasCaballero) : null,
            'estado' => $dto->estado,
            'notas_rechazo' => $dto->notas_rechazo,
            'aprobado_por' => $dto->aprobado_por,
            'datos_adicionales' => !empty($dto->datos_adicionales) ? json_encode($dto->datos_adicionales) : null,
        ]);

        // Crear registros en tabla de tallas (uno por cada talla)
        if (!empty($dto->tallas)) {
            foreach ($dto->tallas as $talla) {
                $proceso->tallas()->create([
                    'genero' => $talla['genero'],
                    'talla' => $talla['talla'],
                    'cantidad' => $talla['cantidad'] ?? 0,
                ]);
            }
        }

        return $proceso;
    }
}
