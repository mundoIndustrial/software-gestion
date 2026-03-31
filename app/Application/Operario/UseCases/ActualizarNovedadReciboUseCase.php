<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\NovedadReciboRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActualizarNovedadReciboUseCase
{
    public function __construct(
        private readonly NovedadReciboRepository $novedades,
    ) {}

    /**
     * @return array{success:bool,status:int,message:string}
     */
    public function execute(Request $request, int $id): array
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
                'message' => 'No tienes permiso para editar esta novedad',
            ];
        }

        $now = now();

        $this->novedades->actualizar((int) $id, [
            'novedad_texto' => (string) $request->novedad_texto,
            'tipo_novedad' => (string) $request->tipo_novedad,
            'editado' => 1,
            'editado_por' => (int) $usuario->id,
            'editado_en' => $now,
            'updated_at' => $now,
        ]);

        \Log::info('[ActualizarNovedadReciboUseCase] Novedad actualizada', [
            'novedad_id' => (int) $id,
            'usuario_id' => (int) $usuario->id,
        ]);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Novedad actualizada correctamente',
        ];
    }
}

