<?php

namespace App\Application\Services\Asesores;

use App\DTOs\Edit\EditPrendaPedidoDTO;
use App\DTOs\Edit\EditPrendaVariantePedidoDTO;
use App\Infrastructure\Services\Edit\PrendaPedidoEditService;
use App\Infrastructure\Services\Edit\PrendaVariantePedidoEditService;
use App\Infrastructure\Services\Procesos\ProcesoActualizarService;

final class PrendaPedidoEditApplicationFacadeService
{
    public function __construct(
        private readonly PrendaPedidoEditService $prendaEditService,
        private readonly PrendaVariantePedidoEditService $varianteEditService,
        private readonly ProcesoActualizarService $procesoActualizarService,
        private readonly PrendaPedidoEdicionAuditoriaService $prendaPedidoEdicionAuditoriaService,
        private readonly PrendaPedidoFinderService $prendaPedidoFinderService,
    ) {
    }

    public function editPrenda(int $id, array $payload): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($id);
        $dto = EditPrendaPedidoDTO::fromPayload($payload);
        $dto->id = $id;

        $camposAntes = $prenda->only(['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega']);
        $resultado = $this->prendaEditService->edit($prenda, $dto);

        $cambiosDetalle = [];
        foreach (['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega'] as $campo) {
            if (array_key_exists($campo, $dto->toArray() ?? [])) {
                $vAntes = (string) ($camposAntes[$campo] ?? '');
                $vDespues = (string) ($dto->$campo ?? $camposAntes[$campo] ?? '');
                if ($vAntes !== $vDespues) {
                    $cambiosDetalle[] = $campo . ': "' . $vAntes . '"  "' . $vDespues . '"';
                }
            }
        }

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $prenda->pedido_produccion_id,
            $id,
            $prenda->nombre_prenda ?? 'PRENDA',
            'campos generales',
            $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
        );

        return $resultado;
    }

    public function editPrendaFields(int $id, array $payload): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($id);
        $camposAntes = $prenda->only(['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega']);
        $resultado = $this->prendaEditService->updateBasic($prenda, $payload);

        $cambiosDetalle = [];
        foreach (['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega'] as $campo) {
            if (array_key_exists($campo, $payload)) {
                $vAntes = (string) ($camposAntes[$campo] ?? '');
                $vDespues = (string) ($payload[$campo] ?? '');
                if ($vAntes !== $vDespues) {
                    $cambiosDetalle[] = $campo . ': "' . $vAntes . '"  "' . $vDespues . '"';
                }
            }
        }

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $prenda->pedido_produccion_id,
            $id,
            $prenda->nombre_prenda ?? 'PRENDA',
            'campos basicos',
            $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
        );

        return $resultado;
    }

    public function editTallas(int $id, array $tallas): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($id);
        $tallasAntes = $prenda->tallas()->get()->keyBy(
            fn($t) => strtoupper($t->genero ?? '') . '_' . strtoupper($t->talla ?? '')
        );

        $resultado = $this->prendaEditService->updateTallas($prenda, $tallas);

        $cambiosDetalle = [];
        foreach ($tallas as $t) {
            $clave = strtoupper($t['genero'] ?? '') . '_' . strtoupper($t['talla'] ?? '');
            $antes = (int) ($tallasAntes[$clave]->cantidad ?? 0);
            $despues = (int) ($t['cantidad'] ?? 0);
            if ($antes !== $despues) {
                $cambiosDetalle[] = ($t['genero'] ?? '?') . ' ' . ($t['talla'] ?? '?') . ': ' . $antes . '' . $despues;
            }
        }

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $prenda->pedido_produccion_id,
            $id,
            $prenda->nombre_prenda ?? 'PRENDA',
            'tallas',
            $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
        );

        return $resultado;
    }

    public function editVariante(int $prendaId, int $varianteId, array $payload): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($prendaId);
        $variante = $this->prendaPedidoFinderService->findVarianteOrFail($prenda, $varianteId);

        $dto = EditPrendaVariantePedidoDTO::fromPayload($payload);
        $dto->id = $varianteId;

        if (!$this->varianteEditService->canEdit($variante, $dto)) {
            throw new \InvalidArgumentException('Intento de editar campos protegidos');
        }

        $varianteAntes = $variante->only(['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs']);
        $resultado = $this->varianteEditService->edit($variante, $dto);

        $cambiosDetalle = [];
        foreach (['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs'] as $campo) {
            if (array_key_exists($campo, $payload)) {
                $vAntes = (string) ($varianteAntes[$campo] ?? '');
                $vDespues = (string) ($payload[$campo] ?? '');
                if ($vAntes !== $vDespues) {
                    $cambiosDetalle[] = $campo . ': "' . $vAntes . '"  "' . $vDespues . '"';
                }
            }
        }

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $prenda->pedido_produccion_id,
            $prendaId,
            $prenda->nombre_prenda ?? 'PRENDA',
            'variante',
            $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
        );

        return $resultado;
    }

    public function editVarianteFields(int $prendaId, int $varianteId, array $payload): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($prendaId);
        $variante = $this->prendaPedidoFinderService->findVarianteOrFail($prenda, $varianteId);

        $varianteAntes = $variante->only(['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs']);
        $resultado = $this->varianteEditService->updateBasic($variante, $payload);

        $cambiosDetalle = [];
        foreach (['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs'] as $campo) {
            if (array_key_exists($campo, $payload)) {
                $vAntes = (string) ($varianteAntes[$campo] ?? '');
                $vDespues = (string) ($payload[$campo] ?? '');
                if ($vAntes !== $vDespues) {
                    $cambiosDetalle[] = $campo . ': "' . $vAntes . '"  "' . $vDespues . '"';
                }
            }
        }

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $prenda->pedido_produccion_id,
            $prendaId,
            $prenda->nombre_prenda ?? 'PRENDA',
            'campos de variante',
            $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
        );

        return $resultado;
    }

    public function editVarianteColores(int $prendaId, int $varianteId, array $colores): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($prendaId);
        $variante = $this->prendaPedidoFinderService->findVarianteOrFail($prenda, $varianteId);

        $coloresAntes = $prenda->coloresTelas()
            ->whereNotNull('color_id')
            ->with('color')
            ->get()
            ->map(fn($ct) => $ct->color->nombre ?? '#' . $ct->color_id)
            ->unique()->values()->toArray();

        $resultado = $this->varianteEditService->updateColores($variante, $colores);

        $colorIdsNuevos = collect($colores)->pluck('color_id')->filter()->unique()->values()->toArray();
        $coloresDespues = $this->prendaPedidoEdicionAuditoriaService->obtenerNombresColores($colorIdsNuevos);
        $detalleColores = 'Antes: ' . (implode(', ', $coloresAntes) ?: 'ninguno')
            . '  despues: ' . (implode(', ', $coloresDespues) ?: 'ninguno');

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $prenda->pedido_produccion_id,
            $prendaId,
            $prenda->nombre_prenda ?? 'PRENDA',
            'colores',
            $detalleColores
        );

        return $resultado;
    }

    public function editVarianteTelas(int $prendaId, int $varianteId, array $telas): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($prendaId);
        $variante = $this->prendaPedidoFinderService->findVarianteOrFail($prenda, $varianteId);

        $telasAntes = $prenda->coloresTelas()
            ->whereNotNull('tela_id')
            ->with('tela')
            ->get()
            ->map(fn($ct) => $ct->tela->nombre ?? '#' . $ct->tela_id)
            ->unique()->values()->toArray();

        $resultado = $this->varianteEditService->updateTelas($variante, $telas);

        $telaIdsNuevos = collect($telas)->pluck('tela_id')->filter()->unique()->values()->toArray();
        $telasDespues = $this->prendaPedidoEdicionAuditoriaService->obtenerNombresTelas($telaIdsNuevos);
        $detalleTelas = 'Antes: ' . (implode(', ', $telasAntes) ?: 'ninguna')
            . '  despues: ' . (implode(', ', $telasDespues) ?: 'ninguna');

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $prenda->pedido_produccion_id,
            $prendaId,
            $prenda->nombre_prenda ?? 'PRENDA',
            'telas',
            $detalleTelas
        );

        return $resultado;
    }

    public function getPrendaState(int $id): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($id);
        return $this->prendaEditService->getCurrentState($prenda);
    }

    public function getVarianteState(int $prendaId, int $varianteId): array
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($prendaId);
        $variante = $this->prendaPedidoFinderService->findVarianteOrFail($prenda, $varianteId);
        return $this->varianteEditService->getCurrentState($variante);
    }

    public function actualizarProcesoEspecifico(int $prendaId, int $procesoId, array $inputData, array $archivos): mixed
    {
        $prenda = $this->prendaPedidoFinderService->findOrFail($prendaId);
        $proceso = $this->prendaPedidoFinderService->findProcesoOrFail($prenda, $procesoId);

        return $this->procesoActualizarService->actualizar($proceso, $inputData, $archivos);
    }
}

