<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConsecutivoReciboPedido;
use App\Models\EntregaReciboCostura;
use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;
use App\Models\SeguimientoPedidosPorPrenda;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EntregasTalleresController extends Controller
{
    public function index()
    {
        return view('entregas_talleres.index');
    }

    public function buscar(Request $request, \App\Application\EntregasTalleres\UseCases\BuscarRecibosTallerUseCase $useCase)
    {
        $busqueda = $request->input('busqueda');

        if (!$busqueda) {
            return redirect()->route('entregas-talleres.index');
        }

        $recibos = $useCase->execute($busqueda);

        return view('entregas_talleres.resultados', compact('recibos', 'busqueda'));
    }

    public function showRecibo(Request $request, $id, \App\Application\EntregasTalleres\UseCases\ObtenerDetalleReciboTallerUseCase $useCase)
    {
        $esParcial = $request->query('es_parcial') == '1';
        $data = $useCase->execute($id, $esParcial);

        return view('entregas_talleres.detalle', [
            'recibo' => $data['recibo'],
            'numeroRecibo' => $data['numeroRecibo'],
            'prendaNombre' => $data['prendaNombre'],
            'encargado' => $data['encargado'],
            'tallasAgrupadas' => $data['tallasAgrupadas'],
            'entregasPorLlave' => $data['entregasPorLlave'],
            'esParcial' => $data['esParcial']
        ]);
    }

    public function store(Request $request, \App\Application\EntregasTalleres\UseCases\RegistrarEntregaTallerUseCase $useCase)
    {
        $request->validate([
            'recibo_id' => 'required',
            'talla' => 'required|string',
            'cantidad' => 'required|integer|min:1',
            'genero' => 'required|string',
            'color' => 'required|string',
            'es_parcial' => 'required'
        ]);

        $result = $useCase->execute($request->all());

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function historial(Request $request, $id, \App\Application\EntregasTalleres\UseCases\ObtenerHistorialEntregasTallerUseCase $useCase)
    {
        $esParcial = $request->query('es_parcial') == '1';
        $formateadas = $useCase->execute($id, $esParcial);

        return response()->json($formateadas);
    }

    public function apiSearch(Request $request, \App\Application\EntregasTalleres\UseCases\BuscarRecibosTallerUseCase $useCase)
    {
        $term = $request->get('term');
        $recibos = $useCase->execute($term, 10);

        return response()->json($recibos);
    }

    public function destroy($id, \App\Application\EntregasTalleres\UseCases\EliminarEntregaTallerUseCase $useCase)
    {
        $result = $useCase->execute($id);
        return response()->json($result);
    }
}

