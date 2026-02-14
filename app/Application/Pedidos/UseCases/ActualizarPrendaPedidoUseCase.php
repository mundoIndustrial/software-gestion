<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

final class ActualizarPrendaPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function ejecutar(ActualizarPrendaPedidoDTO $dto)
    {
        Log::info('[ActualizarPrendaPedidoUseCase] Iniciando actualizacion de prenda', [
            'prenda_id' => $dto->prendaId,
        ]);

        $prenda = PrendaPedido::find($dto->prendaId);
        
        $this->validarObjetoExiste(
            $prenda,
            'Prenda',
            $dto->prendaId
        );

        // 1. Actualizar campos bÃ¡sicos
        $this->actualizarCamposBasicos($prenda, $dto);

        // 2. Actualizar relaciones
        $this->actualizarTallas($prenda, $dto);
        $this->actualizarVariantes($prenda, $dto);
        $this->actualizarColoresTelas($prenda, $dto);
        $this->actualizarProcesos($prenda, $dto);

        // Recargar prenda con relaciones actualizadas
        $prenda->load('tallas', 'variantes', 'coloresTelas', 'procesos');

        Log::info('[ActualizarPrendaPedidoUseCase] Prenda actualizada exitosamente', [
            'prenda_id' => $prenda->id,
        ]);

        return $prenda;
    }

    private function actualizarCamposBasicos(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
    {
        if ($dto->nombrePrenda !== null) {
            $prenda->nombre_prenda = $dto->nombrePrenda;
        }
        
        if ($dto->descripcion !== null) {
            $prenda->descripcion = $dto->descripcion;
        }
        
        if ($dto->deBodega !== null) {
            $prenda->de_bodega = $dto->deBodega;
        }

        $prenda->save();
    }

    private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
    {
        if (is_null($dto->cantidadTalla)) {
            return;
        }

        if (empty($dto->cantidadTalla)) {
            $prenda->tallas()->delete();
            return;
        }

        $prenda->tallas()->delete();
        foreach ($dto->cantidadTalla as $genero => $tallasCantidad) {
            if (is_array($tallasCantidad)) {
                foreach ($tallasCantidad as $talla => $cantidad) {
                    $prenda->tallas()->create([
                        'genero' => $genero,
                        'talla' => $talla,
                        'cantidad' => (int) $cantidad,
                    ]);
                }
            }
        }
    }

    private function actualizarVariantes(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
    {
        if (is_null($dto->variantes)) {
            return;
        }

        if (empty($dto->variantes)) {
            $prenda->variantes()->delete();
            return;
        }

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

    private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
    {
        if (is_null($dto->coloresTelas)) {
            return;
        }

        if (empty($dto->coloresTelas)) {
            $prenda->coloresTelas()->delete();
            return;
        }

        // 🗑️ RECOPILAR IDs DE TELAS EN EL PAYLOAD PARA IDENTIFICAR CUÁLES ELIMINAR
        $telaIdsEnPayload = [];
        
        //  MERGE PATTERN: UPDATE o CREATE según id
        foreach ($dto->coloresTelas as $colorTela) {
            $colorId = $colorTela['color_id'] ?? null;
            $telaId = $colorTela['tela_id'] ?? null;
            $id = $colorTela['id'] ?? null;  // ID de relación existente
            
            if (!$colorId || !$telaId) {
                continue;
            }
            
            //  UPDATE: Si viene con ID, actualizar relación existente
            if ($id) {
                $colorTelaExistente = $prenda->coloresTelas()->where('id', $id)->first();
                if ($colorTelaExistente) {
                    $colorTelaExistente->update([
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                    ]);
                    $telaIdsEnPayload[] = $id;  //  Guardar ID para no eliminar
                }
            } 
            //  CREATE: Si NO viene con ID, crear nueva relación
            else {
                // Verificar si ya existe esta combinación
                $existente = $prenda->coloresTelas()
                    ->where('color_id', $colorId)
                    ->where('tela_id', $telaId)
                    ->first();
                
                if (!$existente) {
                    $nueva = $prenda->coloresTelas()->create([
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                    ]);
                    $telaIdsEnPayload[] = $nueva->id;  //  Guardar ID de la nueva tela
                } else {
                    $telaIdsEnPayload[] = $existente->id;  //  Guardar ID de la existente
                }
            }
        }
        
        // 🗑️ ELIMINAR TELAS QUE NO ESTÁN EN EL PAYLOAD (FUERON ELIMINADAS POR EL USUARIO)
        $prenda->coloresTelas()
            ->whereNotIn('id', $telaIdsEnPayload)
            ->delete();
    }

    private function actualizarProcesos(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
    {
        if (is_null($dto->procesos)) {
            return;
        }

        if (empty($dto->procesos)) {
            $prenda->procesos()->delete();
            return;
        }

        $prenda->procesos()->delete();
        foreach ($dto->procesos as $proceso) {
            // Decodificar ubicaciones si vienen como JSON string
            $ubicaciones = $proceso['ubicaciones'] ?? null;
            if (is_string($ubicaciones)) {
                try {
                    $ubicacionesDecodificadas = json_decode($ubicaciones, true);
                    if (is_array($ubicacionesDecodificadas)) {
                        $ubicaciones = $ubicacionesDecodificadas;
                    }
                } catch (\Exception $e) {
                    $ubicaciones = null;
                }
            }

            $prenda->procesos()->create([
                'tipo_proceso_id' => $proceso['tipo_proceso_id'] ?? null,
                'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : null,
            ]);
        }
    }
}


