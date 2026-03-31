<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\NovedadReciboRepository;
use App\Models\PrendaPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrearNovedadReciboUseCase
{
    public function __construct(
        private readonly NovedadReciboRepository $novedades,
    ) {}

    /**
     * @return array{success:bool,status:int,message:string}
     */
    public function execute(Request $request): array
    {
        $usuario = Auth::user();

        $prenda = PrendaPedido::find((int) $request->prenda_id);
        if (!$prenda) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Prenda no encontrada',
            ];
        }

        $now = now();

        $this->novedades->crear([
            'prenda_pedido_id' => (int) $request->prenda_id,
            'numero_recibo' => (string) $request->numero_recibo,
            'novedad_texto' => (string) $request->novedad_texto,
            'tipo_novedad' => (string) $request->tipo_novedad,
            'estado_novedad' => 'activa',
            'creado_por' => (int) $usuario->id,
            'creado_en' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->novedades->marcarPedidoPendientePorNumero((int) $request->numero_pedido, $now);

        \Log::info('[CrearNovedadReciboUseCase] Novedad creada', [
            'prenda_id' => (int) $request->prenda_id,
            'usuario_id' => (int) $usuario->id,
            'tipo_novedad' => (string) $request->tipo_novedad,
            'numero_pedido' => (int) $request->numero_pedido,
        ]);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Novedad registrada correctamente',
        ];
    }
}

