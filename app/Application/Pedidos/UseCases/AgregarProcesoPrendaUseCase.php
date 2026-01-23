<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarProcesoPrendaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar proceso a una prenda
 * 
 * Maneja la creación de registro en pedidos_procesos_prenda_detalles
 * Los procesos incluyen datos complejos como ubicaciones y tallas en JSON
 * 
 * Tabla: pedidos_procesos_prenda_detalles
 * Campos: tipo_proceso_id, ubicaciones (json), observaciones, tallas_dama (json), 
 *         tallas_caballero (json), estado, notas_rechazo, fecha_aprobacion, 
 *         aprobado_por, datos_adicionales
 */
final class AgregarProcesoPrendaUseCase
{
    public function execute(AgregarProcesoPrendaDTO $dto)
    {
        $prenda = PrendaPedido::findOrFail($dto->prendaId);

        return $prenda->procesos()->create([
            'tipo_proceso_id' => $dto->tipoProcesosId,
            'ubicaciones' => !empty($dto->ubicaciones) ? json_encode($dto->ubicaciones) : null,
            'observaciones' => $dto->observaciones,
            'tallas_dama' => !empty($dto->tallasDama) ? json_encode($dto->tallasDama) : null,
            'tallas_caballero' => !empty($dto->tallasCaballero) ? json_encode($dto->tallasCaballero) : null,
            'estado' => $dto->estado,
            'notas_rechazo' => $dto->notasRechazo,
            'aprobado_por' => $dto->aprobadoPor,
            'datos_adicionales' => !empty($dto->datosAdicionales) ? json_encode($dto->datosAdicionales) : null,
        ]);
    }
}
