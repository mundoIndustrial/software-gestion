<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;
use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;
use Illuminate\Http\Request;

class DespachoController extends Controller
{
    public function __construct(
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private GuardarDespachoUseCase $guardarDespacho,
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search', '');
        
        $query = PedidoProduccion::query();
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                  ->orWhere('cliente', 'like', "%{$search}%");
            });
        }
        
        $pedidos = $query->paginate(15);
        return view('despacho.index', ['pedidos' => $pedidos, 'search' => $search]);
    }

    public function show(PedidoProduccion $pedido)
    {
        $prendas = $this->obtenerFilas->obtenerPrendas($pedido->id);
        $epps = $this->obtenerFilas->obtenerEpp($pedido->id);
        return view('despacho.show', [
            'pedido' => $pedido,
            'prendas' => $prendas,
            'epps' => $epps,
        ]);
    }

    public function guardarDespacho(Request $request, PedidoProduccion $pedido)
    {
        $validated = $request->validate([
            'despachos' => 'required|array',
        ]);

        $control = new ControlEntregasDTO(
            pedidoId: $pedido->id,
            numeroPedido: $pedido->numero_pedido,
            cliente: $pedido->cliente,
            despachos: $validated['despachos'],
        );

        return response()->json($this->guardarDespacho->ejecutar($control));
    }

    public function printDespacho(PedidoProduccion $pedido)
    {
        $filas = $this->obtenerFilas->obtenerTodas($pedido->id);
        return view('despacho.print', ['pedido' => $pedido, 'filas' => $filas]);
    }
}
