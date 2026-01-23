<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para actualizar una prenda y fotos
 * 
 * Responsabilidades:
 * - Actualizar registro en prendas_pedido (nombre, descripción, de_bodega)
 * - Actualizar fotos de referencia (prenda_fotos_pedido)
 * 
 * Responsabilidades SEPARADAS en otros Use Cases:
 * - Actualizar variantes → ActualizarVariantePrendaUseCase
 * - Actualizar colores y telas → ActualizarColorTelaUseCase
 * - Actualizar tallas → ActualizarTallaPrendaUseCase
 * - Actualizar procesos → ActualizarProcesoPrendaUseCase
 */
final class ActualizarPrendaCompletaUseCase
{
    public function execute(ActualizarPrendaCompletaDTO $dto): PrendaPedido
    {
        $prenda = PrendaPedido::findOrFail($dto->prendaId);

        // 1. Actualizar campos básicos si se proporcionan
        $datosActualizar = [];
        
        if ($dto->nombrePrenda !== null) {
            $datosActualizar['nombre_prenda'] = $dto->nombrePrenda;
        }
        
        if ($dto->descripcion !== null) {
            $datosActualizar['descripcion'] = $dto->descripcion;
        }
        
        if ($dto->deBodega !== null) {
            $datosActualizar['de_bodega'] = $dto->deBodega;
        }

        if (!empty($datosActualizar)) {
            $prenda->update($datosActualizar);
        }

        // 2. Actualizar fotos si se proporcionan
        if (!empty($dto->imagenes)) {
            // Eliminar fotos antiguas
            $prenda->fotos()->delete();

            // Crear nuevas fotos
            foreach ($dto->imagenes as $orden => $rutaOriginal) {
                $prenda->fotos()->create([
                    'ruta_original' => $rutaOriginal,
                    'ruta_webp' => $this->generarRutaWebp($rutaOriginal),
                    'orden' => $orden + 1,
                ]);
            }
        }

        return $prenda->refresh();
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        // Reemplazar extensión por .webp
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
