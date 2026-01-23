<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;
use App\Models\PedidosProcesosPrendaDetalle;

/**
 * Use Case para actualizar una prenda y fotos
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Responsabilidades:
 * - Validar prenda existe ✅ TRAIT
 * - Actualizar registro en prendas_pedido (nombre, descripción, de_bodega)
 * - Actualizar fotos de referencia (prenda_fotos_pedido)
 * 
 * Responsabilidades SEPARADAS en otros Use Cases:
 * - Actualizar variantes → ActualizarVariantePrendaUseCase
 * - Actualizar colores y telas → ActualizarColorTelaUseCase
 * - Actualizar tallas → ActualizarTallaPrendaUseCase
 * - Actualizar procesos → ActualizarProcesoPrendaUseCase
 * 
 * Antes: 70 líneas | Después: ~60 líneas | Reducción: ~14%
 */
final class ActualizarPrendaCompletaUseCase
{
    use ManejaPedidosUseCase;

    public function ejecutar(ActualizarPrendaCompletaDTO $dto): PrendaPedido
    {
        // CENTRALIZADO: Validar prenda existe (trait)
        $prenda = PrendaPedido::find($dto->prendaId);
        
        $this->validarObjetoExiste(
            $prenda,
            'Prenda',
            $dto->prendaId
        );

        // 1. Actualizar campos básicos
        $this->actualizarCamposBasicos($prenda, $dto);

        // 2. Actualizar fotos de referencia
        $this->actualizarFotos($prenda, $dto);

        // 3. Actualizar tallas
        $this->actualizarTallas($prenda, $dto);

        // 4. Actualizar variantes (manga, broche, bolsillos)
        $this->actualizarVariantes($prenda, $dto);

        // 5. Actualizar colores y telas
        $this->actualizarColoresTelas($prenda, $dto);

        // 6. Actualizar fotos de telas
        $this->actualizarFotosTelas($prenda, $dto);

        // 7. Actualizar procesos y sus imágenes
        $this->actualizarProcesos($prenda, $dto);

        return $prenda->refresh();
    }

    private function actualizarCamposBasicos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
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
    }

    private function actualizarFotos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan fotos de prendas
        if (is_null($dto->fotos)) {
            return;
        }

        if (empty($dto->fotos)) {
            // Si viene vacío, eliminar todas
            $prenda->fotos()->delete();
            return;
        }

        // Eliminar fotos viejas y crear nuevas
        $prenda->fotos()->delete();
        foreach ($dto->fotos as $idx => $foto) {
            $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
            if ($ruta) {
                $prenda->fotos()->create([
                    'ruta_original' => $ruta,
                    'ruta_webp' => $this->generarRutaWebp($ruta),
                    'orden' => $idx + 1,
                ]);
            }
        }
    }

    private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan tallas
        if (is_null($dto->cantidadTalla)) {
            return;
        }

        if (empty($dto->cantidadTalla)) {
            // Si viene vacío, eliminar todas
            $prenda->tallas()->delete();
            return;
        }

        // Obtener tallas existentes
        $tallasExistentes = $prenda->tallas()->get()->keyBy(function($t) {
            return "{$t->genero}_{$t->talla}";
        });

        // Nuevas tallas a guardar
        $tallasNuevas = [];
        foreach ($dto->cantidadTalla as $genero => $tallasCantidad) {
            if (is_array($tallasCantidad)) {
                foreach ($tallasCantidad as $talla => $cantidad) {
                    $key = "{$genero}_{$talla}";
                    $tallasNuevas[$key] = [
                        'genero' => $genero,
                        'talla' => $talla,
                        'cantidad' => (int) $cantidad,
                    ];
                }
            }
        }

        // Eliminar tallas que ya no están en la nueva lista
        foreach ($tallasExistentes as $key => $tallaRecord) {
            if (!isset($tallasNuevas[$key])) {
                $tallaRecord->delete();
            }
        }

        // Insertar o actualizar tallas
        foreach ($tallasNuevas as $key => $dataTalla) {
            if (isset($tallasExistentes[$key])) {
                // Actualizar existente
                $tallasExistentes[$key]->update([
                    'cantidad' => $dataTalla['cantidad'],
                ]);
            } else {
                // Crear nueva
                $prenda->tallas()->create($dataTalla);
            }
        }
    }

    private function actualizarVariantes(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan variantes
        if (is_null($dto->variantes)) {
            return;
        }

        if (empty($dto->variantes)) {
            // Si viene vacío, eliminar todas
            $prenda->variantes()->delete();
            return;
        }

        // Eliminar variantes viejas y crear nuevas
        // (Las variantes no tienen un ID único como "genero_talla", entonces simplemente reemplazamos)
        $prenda->variantes()->delete();
        foreach ($dto->variantes as $variante) {
            $prenda->variantes()->create([
                'tipo_manga_id' => $variante['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variante['tipo_broche_boton_id'] ?? null,
                'manga_obs' => $variante['manga_obs'] ?? null,
                'broche_boton_obs' => $variante['broche_boton_obs'] ?? null,
                'tiene_bolsillos' => $variante['tiene_bolsillos'] ?? false,
                'bolsillos_obs' => $variante['bolsillos_obs'] ?? null,
            ]);
        }
    }

    private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan colores/telas
        if (is_null($dto->coloresTelas)) {
            return;
        }

        if (empty($dto->coloresTelas)) {
            // Si viene vacío, eliminar todas
            $prenda->coloresTelas()->delete();
            return;
        }

        // Eliminar colores/telas viejos y crear nuevos
        // (No tienen un ID único compuesto, entonces simplemente reemplazamos)
        $prenda->coloresTelas()->delete();
        foreach ($dto->coloresTelas as $color) {
            $prenda->coloresTelas()->create([
                'color_id' => $color['color_id'] ?? null,
                'tela_id' => $color['tela_id'] ?? null,
            ]);
        }
    }

    private function actualizarFotosTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan fotos de telas
        if (is_null($dto->fotosTelas)) {
            return;
        }

        if (empty($dto->fotosTelas)) {
            // Si viene vacío, eliminar todas
            $prenda->fotosTelas()->delete();
            return;
        }

        // Eliminar fotos de telas viejas y crear nuevas
        // Las fotos de telas se asocian a través de prenda_pedido_colores_telas
        $prenda->fotosTelas()->delete();
        foreach ($dto->fotosTelas as $idx => $foto) {
            $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? $foto['color_tela_id'] ?? null;
            $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
            
            if ($colorTelaId && $ruta) {
                $prenda->fotosTelas()->create([
                    'prenda_pedido_colores_telas_id' => $colorTelaId,
                    'ruta_original' => $ruta,
                    'ruta_webp' => $this->generarRutaWebp($ruta),
                    'orden' => $idx + 1,
                ]);
            }
        }
    }

    private function actualizarProcesos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan procesos
        if (is_null($dto->procesos)) {
            return;
        }

        if (empty($dto->procesos)) {
            // Si viene vacío, eliminar todos (imágenes se eliminan en cascada)
            $prenda->procesos()->delete();
            return;
        }

        // Eliminar procesos antiguos (y sus imágenes se eliminarán en cascada)
        $prenda->procesos()->delete();

        // Crear nuevos procesos
        foreach ($dto->procesos as $proceso) {
            $procesoCreado = $prenda->procesos()->create([
                'tipo_proceso_id' => $proceso['tipo_proceso_id'] ?? null,
                'ubicaciones' => !empty($proceso['ubicaciones']) ? json_encode($proceso['ubicaciones']) : null,
                'observaciones' => $proceso['observaciones'] ?? null,
                'estado' => $proceso['estado'] ?? 'PENDIENTE',
            ]);

            // Agregar imágenes del proceso si existen
            $this->agregarImagenesProceso($procesoCreado, $proceso, $dto);
        }
    }

    private function agregarImagenesProceso(
        PedidosProcesosPrendaDetalle $procesoCreado,
        array $proceso,
        ActualizarPrendaCompletaDTO $dto
    ): void {
        if (empty($dto->fotosProcesosPorProceso)) {
            return;
        }

        foreach ($dto->fotosProcesosPorProceso as $fotoProceso) {
            if ($fotoProceso['proceso_id'] !== $proceso['id'] || empty($fotoProceso['imagenes'])) {
                continue;
            }

            foreach ($fotoProceso['imagenes'] as $idx => $ruta) {
                $procesoCreado->imagenes()->create([
                    'ruta_original' => $ruta,
                    'ruta_webp' => $this->generarRutaWebp($ruta),
                    'orden' => $idx + 1,
                    'es_principal' => $idx === 0 ? 1 : 0,
                ]);
            }
        }
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        // Reemplazar extensión por .webp
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
