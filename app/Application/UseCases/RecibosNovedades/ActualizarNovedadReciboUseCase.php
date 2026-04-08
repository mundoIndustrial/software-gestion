<?php

namespace App\Application\UseCases\RecibosNovedades;

use App\Infrastructure\Repositories\NovedadesReciboRepository;
use App\Models\PrendaPedidoNovedadRecibo;
use Illuminate\Support\Facades\Auth;

class ActualizarNovedadReciboUseCase
{
    public function __construct(
        private readonly NovedadesReciboRepository $repository
    ) {}

    public function execute(
        int $novedadId,
        string $novedadTexto,
        ?string $tipoNovedad = null,
        ?string $estadoNovedad = null,
        ?string $notasAdicionales = null
    ): PrendaPedidoNovedadRecibo {
        $novedad = $this->repository->obtenerPorId($novedadId);
        
        // Solo el autor puede editar
        if ($novedad->creado_por !== Auth::id()) {
            throw new \Exception('No tienes permiso para editar esta novedad', 403);
        }
        
        $updateData = [
            'novedad_texto' => $novedadTexto,
            'editado' => true,
            'editado_en' => now(),
            'editado_por' => Auth::id(),
        ];
        
        // Campos opcionales
        if ($tipoNovedad) {
            $updateData['tipo_novedad'] = $tipoNovedad;
        }
        if ($estadoNovedad) {
            $updateData['estado_novedad'] = $estadoNovedad;
        }
        if ($notasAdicionales) {
            $updateData['notas_adicionales'] = $notasAdicionales;
        }
        
        return $this->repository->actualizar($novedadId, $updateData);
    }
}
