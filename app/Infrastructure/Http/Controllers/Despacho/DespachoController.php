<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;
use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Http\Request;

class DespachoController extends Controller
{
    public function __construct(
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private GuardarDespachoUseCase $guardarDespacho,
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search', '');
        
        $query = PedidoProduccion::query();
        
        // Mostrar todos los pedidos excepto los rechazados o en cartera
        $query->whereNotIn('estado', ['pendiente_cartera', 'RECHAZADO_CARTERA']);
        
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
            'fecha_hora' => 'nullable|date_format:Y-m-d\TH:i',
            'cliente_empresa' => 'nullable|string',
        ]);

        $fechaHora = null;
        if ($validated['fecha_hora']) {
            $fechaHora = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $validated['fecha_hora']);
        }

        $control = new ControlEntregasDTO(
            pedidoId: $pedido->id,
            numeroPedido: $pedido->numero_pedido,
            cliente: $pedido->cliente,
            fechaHora: $fechaHora,
            clienteEmpresa: $validated['cliente_empresa'] ?? $pedido->cliente,
            despachos: $validated['despachos'],
        );

        return response()->json($this->guardarDespacho->ejecutar($control));
    }

    public function printDespacho(PedidoProduccion $pedido)
    {
        $filas = $this->obtenerFilas->obtenerTodas($pedido->id);
        return view('despacho.print', ['pedido' => $pedido, 'filas' => $filas]);
    }

    public function obtenerDespachos(PedidoProduccion $pedido)
    {
        $despachos = \App\Models\DesparChoParcialesModel::where('pedido_id', $pedido->id)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($d) {
                return [
                    'tipo' => $d->tipo_item,
                    'id' => $d->item_id,
                    'talla_id' => $d->talla_id,
                    'pendiente_inicial' => $d->pendiente_inicial,
                    'parcial_1' => $d->parcial_1,
                    'pendiente_1' => $d->pendiente_1,
                    'parcial_2' => $d->parcial_2,
                    'pendiente_2' => $d->pendiente_2,
                    'parcial_3' => $d->parcial_3,
                    'pendiente_3' => $d->pendiente_3,
                ];
            });

        return response()->json(['despachos' => $despachos]);
    }

    public function obtenerFacturaDatos(PedidoProduccion $pedido)
    {
        // Usar el repositorio que ya obtiene los datos completos (igual que asesores)
        $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
        
        return response()->json($datos);
    }
}
