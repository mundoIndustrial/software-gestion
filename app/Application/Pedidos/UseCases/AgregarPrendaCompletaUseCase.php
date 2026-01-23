<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar una prenda al pedido con fotos
 * 
 * Responsabilidades:
 * - Crear registro en prendas_pedido (nombre, descripción, de_bodega)
 * - Asociar fotos de referencia (prenda_fotos_pedido)
 * 
 * Responsabilidades SEPARADAS en otros Use Cases:
 * - Agregar variantes (tipo_manga, tipo_broche) → AgregarVariantePrendaUseCase
 * - Agregar colores y telas → AgregarColorTelaUseCase
 * - Agregar tallas y cantidades → AgregarTallaPrendaUseCase
 * - Agregar procesos → AgregarProcesoPrendaUseCase
 */
final class AgregarPrendaCompletaUseCase
{
    public function execute(AgregarPrendaCompletaDTO $dto): PrendaPedido
    {
        // 1. Crear prenda base
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $dto->pedidoId,
            'nombre_prenda' => $dto->nombrePrenda,
            'descripcion' => $dto->descripcion,
            'de_bodega' => $dto->deBodega,
        ]);

        // 2. Agregar fotos si existen
        if (!empty($dto->imagenes)) {
            foreach ($dto->imagenes as $orden => $rutaOriginal) {
                $prenda->fotos()->create([
                    'ruta_original' => $rutaOriginal,
                    'ruta_webp' => $this->generarRutaWebp($rutaOriginal),
                    'orden' => $orden + 1,
                ]);
            }
        }

        return $prenda;
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        // Reemplazar extensión por .webp
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
