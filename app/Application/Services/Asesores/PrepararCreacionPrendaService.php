<?php

namespace App\Application\Services\Asesores;

use App\Application\Services\Pedidos\ProcesarImagenesPrendaService;
use Illuminate\Http\Request;

final class PrepararCreacionPrendaService
{
    public function __construct(
        private readonly ProcesarImagenesPrendaService $procesarImagenesService,
    ) {
    }

    public function preparar(Request $request, int $pedidoId, array $validated): array
    {
        $imgs = $this->procesarImagenesService->procesarParaCrear(
            $request,
            $pedidoId,
            $validated['asignaciones_colores'] ?? null
        );

        if ($imgs['asignaciones_colores'] !== null) {
            $validated['asignaciones_colores'] = $imgs['asignaciones_colores'];
        }

        return [
            'validated' => $validated,
            'imagenes_guardadas' => $imgs['imagenes_guardadas'],
            'imagenes_existentes' => $imgs['imagenes_existentes'],
            'fotos_proceso_nuevo' => $imgs['fotos_proceso_nuevo'],
            'fotos_tela_rutas' => $imgs['fotos_tela_rutas'],
        ];
    }
}

