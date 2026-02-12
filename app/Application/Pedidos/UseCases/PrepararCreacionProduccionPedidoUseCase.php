<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\PrepararCreacionProduccionPedidoDTO;

class PrepararCreacionProduccionPedidoUseCase
{
    public function ejecutar(PrepararCreacionProduccionPedidoDTO $dto): array
    {
        $esEdicion = false;
        $cotizacion = null;

        // Si estÃ¡ editando, obtener la cotización
        if ($dto->editarId) {
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas' => function($query) {
                    $query->with(['fotos', 'telaFotos', 'tallas', 'variantes']);
                },
                'prendas.logoCotizacionesTecnicas',
                'logoCotizacion.fotos',
                'logoCotizacion.prendas.tipoLogo',
                'logoCotizacion.prendas.fotos',
                'logoCotizacion.prendas.prendaCot.fotos',
            ])->findOrFail($dto->editarId);

            $cotizacion->setRelation(
                'prendas',
                $cotizacion->prendas
                    ->filter(function ($prenda) {
                        return $prenda->logoCotizacionesTecnicas->isEmpty();
                    })
                    ->values()
            );
            
            // Validar permisos
            if ($cotizacion->asesor_id !== $dto->usuarioId || !$cotizacion->es_borrador) {
                throw new \Exception('No tienes permiso para editar este borrador');
            }
            
            $esEdicion = true;
        }

        return [
            'tipo' => $dto->tipo ?? 'PB',
            'esEdicion' => $esEdicion,
            'cotizacion' => $cotizacion
        ];
    }
}

