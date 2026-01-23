<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar una prenda al pedido con fotos y tallas
 * 
 * Responsabilidades:
 * - Crear registro en prendas_pedido
 * - Crear fotos de referencia (prenda_fotos_pedido)
 * - Crear tallas y cantidades (prenda_pedido_tallas)
 * 
 * Responsabilidades SEPARADAS en otros Use Cases:
 * - Agregar variantes → AgregarVariantePrendaUseCase
 * - Agregar colores y telas → AgregarColorTelaUseCase
 * - Agregar procesos → AgregarProcesoPrendaUseCase
 */
final class AgregarPrendaCompletaUseCase
{
    public function execute(AgregarPrendaCompletaDTO $dto): PrendaPedido
    {
        // 1. Crear prenda base
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $dto->pedidoId,
            'nombre_prenda' => $dto->nombre_prenda,
            'descripcion' => $dto->descripcion,
            'de_bodega' => $dto->de_bodega,
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

        // 3. Agregar tallas si existen
        if (!empty($dto->tallas)) {
            foreach ($dto->tallas as $talla) {
                $prenda->tallas()->create([
                    'genero' => $talla['genero'],
                    'talla' => $talla['talla'],
                    'cantidad' => $talla['cantidad'] ?? 0,
                ]);
            }
        }

        return $prenda;
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
