<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\NovedadReciboRepository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ObtenerNovedadesPrendaUseCase
{
    public function __construct(
        private readonly NovedadReciboRepository $novedades,
    ) {}

    /**
     * @return array{success:bool,status:int,novedades?:array<int,object>,message?:string}
     */
    public function execute(int $prendaId): array
    {
        $usuario = Auth::user();

        $novedades = $this->novedades->obtenerPorPrenda((int) $prendaId)
            ->map(function ($n) use ($usuario) {
                $n->creado_en = \Carbon\Carbon::parse($n->creado_en)->format('d/m/Y H:i');

                $creador = User::find((int) $n->creado_por);
                $n->creado_por_nombre = $creador?->name ?? 'Usuario Desconocido';
                $n->usuario_nombre = $n->creado_por_nombre;

                if ($creador) {
                    $roles = $creador->getRoleNames()->toArray();
                    $n->creado_por_rol = !empty($roles) ? strtoupper($roles[0]) : 'USUARIO';
                } else {
                    $n->creado_por_rol = 'USUARIO';
                }

                $n->usuario_rol = $n->creado_por_rol;
                $n->es_mia = $usuario ? ((int) $n->creado_por === (int) $usuario->id) : false;

                return $n;
            })
            ->values()
            ->all();

        return [
            'success' => true,
            'status' => 200,
            'novedades' => $novedades,
        ];
    }
}

