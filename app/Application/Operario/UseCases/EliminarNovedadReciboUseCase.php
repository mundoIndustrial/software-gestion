<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\NovedadReciboRepository;
use Illuminate\Support\Facades\Auth;

class EliminarNovedadReciboUseCase
{
    public function __construct(
        private readonly NovedadReciboRepository $novedades,
    ) {}

    /**
     * @return array{success:bool,status:int,message:string}
     */
    public function execute(int $id): array
    {
        $usuario = Auth::user();

        $novedad = $this->novedades->obtenerPorId((int) $id);
        if (!$novedad) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Novedad no encontrada',
            ];
        }

        if ((int) $novedad->creado_por !== (int) $usuario->id && !$usuario->hasRole('admin')) {
            return [
                'success' => false,
                'status' => 403,
                'message' => 'No tienes permiso para eliminar esta novedad',
            ];
        }

        $this->novedades->eliminar((int) $id);

        \Log::info('[EliminarNovedadReciboUseCase] Novedad eliminada', [
            'novedad_id' => (int) $id,
            'usuario_id' => (int) $usuario->id,
        ]);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Novedad eliminada correctamente',
        ];
    }
}

