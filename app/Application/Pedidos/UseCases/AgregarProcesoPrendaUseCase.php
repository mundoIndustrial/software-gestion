<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarProcesoPrendaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar proceso a una prenda
 * 
 * Crea un registro en pedidos_procesos_prenda_detalles
 * Las tallas se agregan DESPUÉS con AgregarTallaProcesoPrendaUseCase
 * 
 * Tabla: pedidos_procesos_prenda_detalles
 * Campos manejados: tipo_proceso_id, ubicaciones (json), observaciones, 
 *                   estado, notas_rechazo, fecha_aprobacion, aprobado_por, datos_adicionales
 */
final class AgregarProcesoPrendaUseCase
{
    public function execute(AgregarProcesoPrendaDTO $dto)
    {
        $prenda = PrendaPedido::findOrFail($dto->prendaId);

        return $prenda->procesos()->create([
            'tipo_proceso_id' => $dto->tipo_proceso_id,
            'ubicaciones' => !empty($dto->ubicaciones) ? json_encode($dto->ubicaciones) : null,
            'observaciones' => $dto->observaciones,
            'estado' => $dto->estado,
            'notas_rechazo' => $dto->notas_rechazo,
            'aprobado_por' => $dto->aprobado_por,
            'datos_adicionales' => !empty($dto->datos_adicionales) ? json_encode($dto->datos_adicionales) : null,
        ]);
    }
}
