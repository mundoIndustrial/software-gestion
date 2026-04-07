<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\ActualizarPrendaPedidoUseCaseContract;

use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;
use App\Models\TipoProceso;
use Illuminate\Support\Facades\Log;

final class ActualizarPrendaPedidoUseCase implements ActualizarPrendaPedidoUseCaseContract
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

        // 1. Actualizar campos basicos
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

        $telaIdsEnPayload = $this->sincronizarColoresTelas($prenda, $dto->coloresTelas);

        $prenda->coloresTelas()
            ->whereNotIn('id', $telaIdsEnPayload)
            ->delete();
    }

    private function sincronizarColoresTelas(PrendaPedido $prenda, array $coloresTelas): array
    {
        $telaIdsEnPayload = [];

        foreach ($coloresTelas as $colorTela) {
            $id = $this->upsertColorTela($prenda, $colorTela);
            if (!is_null($id)) {
                $telaIdsEnPayload[] = $id;
            }
        }

        return $telaIdsEnPayload;
    }

    private function upsertColorTela(PrendaPedido $prenda, array $colorTela): ?int
    {
        $colorId = $colorTela['color_id'] ?? null;
        $telaId = $colorTela['tela_id'] ?? null;
        $id = $colorTela['id'] ?? null;

        if (!$colorId || !$telaId) {
            return null;
        }

        if ($id) {
            return $this->actualizarColorTelaExistente($prenda, (int) $id, (int) $colorId, (int) $telaId);
        }

        return $this->crearOReusarColorTela($prenda, (int) $colorId, (int) $telaId);
    }

    private function actualizarColorTelaExistente(
        PrendaPedido $prenda,
        int $id,
        int $colorId,
        int $telaId
    ): ?int {
        $colorTelaExistente = $prenda->coloresTelas()->where('id', $id)->first();
        if (!$colorTelaExistente) {
            return null;
        }

        $colorTelaExistente->update([
            'color_id' => $colorId,
            'tela_id' => $telaId,
        ]);

        return $id;
    }

    private function crearOReusarColorTela(PrendaPedido $prenda, int $colorId, int $telaId): int
    {
        $existente = $prenda->coloresTelas()
            ->where('color_id', $colorId)
            ->where('tela_id', $telaId)
            ->first();

        if ($existente) {
            return $existente->id;
        }

        $nueva = $prenda->coloresTelas()->create([
            'color_id' => $colorId,
            'tela_id' => $telaId,
        ]);

        return $nueva->id;
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

        $idsProcesosSincronizados = [];

        foreach ($dto->procesos as $proceso) {
            if (!is_array($proceso)) {
                continue;
            }

            $tipoProcesoId = $this->resolverTipoProcesoId($proceso);
            if (!$tipoProcesoId) {
                continue;
            }

            // Decodificar ubicaciones si vienen como JSON string
            $ubicaciones = $proceso['ubicaciones'] ?? null;
            if (is_string($ubicaciones)) {
                try {
                    $ubicacionesDecodificadas = json_decode($ubicaciones, true);
                    if (is_array($ubicacionesDecodificadas)) {
                        $ubicaciones = $ubicacionesDecodificadas;
                    }
                } catch (\Throwable $e) {
                    $ubicaciones = null;
                }
            }

            $modoTallas = $proceso['modo_tallas'] ?? $proceso['modoTallas'] ?? 'generico';
            $modosValidos = ['generico', 'general', 'especifico'];
            if (!in_array($modoTallas, $modosValidos, true)) {
                $modoTallas = 'generico';
            }

            $payload = [
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : null,
                'observaciones' => $proceso['observaciones'] ?? null,
                'estado' => $proceso['estado'] ?? 'PENDIENTE',
                'modo_tallas' => $modoTallas,
                'datos_adicionales' => json_encode($proceso),
            ];

            $procesoId = isset($proceso['id']) ? (int) $proceso['id'] : null;
            $procesoExistente = null;

            if ($procesoId) {
                $procesoExistente = $prenda->procesos()
                    ->withTrashed()
                    ->where('id', $procesoId)
                    ->first();
            }

            if (!$procesoExistente) {
                $procesoExistente = $prenda->procesos()
                    ->withTrashed()
                    ->where('tipo_proceso_id', $tipoProcesoId)
                    ->first();
            }

            if ($procesoExistente) {
                if (method_exists($procesoExistente, 'trashed') && $procesoExistente->trashed()) {
                    $procesoExistente->restore();
                }
                $procesoExistente->update($payload);
                $idsProcesosSincronizados[] = $procesoExistente->id;
                continue;
            }

            $nuevoProceso = $prenda->procesos()->create($payload);
            $idsProcesosSincronizados[] = $nuevoProceso->id;
        }

        if (!empty($idsProcesosSincronizados)) {
            $prenda->procesos()
                ->whereNotIn('id', $idsProcesosSincronizados)
                ->delete();
        }
    }

    private function resolverTipoProcesoId(array $proceso): ?int
    {
        $tipoProcesoId = $proceso['tipo_proceso_id'] ?? null;
        if ($tipoProcesoId) {
            return (int) $tipoProcesoId;
        }

        $tipoProcesoNombre = $proceso['tipo'] ?? $proceso['nombre'] ?? null;
        if (!$tipoProcesoNombre) {
            return null;
        }

        $tipo = TipoProceso::query()
            ->whereRaw('LOWER(slug) = ?', [strtolower((string) $tipoProcesoNombre)])
            ->orWhereRaw('LOWER(nombre) = ?', [strtolower((string) $tipoProcesoNombre)])
            ->first();

        return $tipo?->id ? (int) $tipo->id : null;
    }
}







