<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ConsecutivoReciboPedido;
use App\Models\EntregaReciboCostura;
use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;
use App\Models\SeguimientoPedidosPorPrenda;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EntregasTalleresController extends Controller
{
    public function index(Request $request, \App\Application\Talleres\UseCases\ObtenerListadoTalleresUseCase $useCase)
    {
        $search = $request->input('search', '');
        $talleres = $useCase->execute($search, 9, 1);

        return view('entregas_talleres.index', compact('talleres', 'search'));
    }

    public function buscar(Request $request, \App\Application\EntregasTalleres\UseCases\BuscarRecibosTallerUseCase $useCase)
    {
        $busqueda = trim((string) $request->input('busqueda', ''));
        $tallerId = $request->input('taller_id');
        $estado = strtolower(trim((string) $request->input('estado', 'pendientes')));
        $taller = null;

        if ($tallerId) {
            $taller = User::findOrFail($tallerId);
        }

        if (!$busqueda && !$tallerId) {
            return redirect()->route('entregas-talleres.index');
        }

        $recibos = $useCase->execute($busqueda !== '' ? $busqueda : null, 0, $tallerId ? (int) $tallerId : null, $estado);

        return view('entregas_talleres.resultados', compact('recibos', 'busqueda', 'taller', 'estado'));
    }

    public function showRecibo(Request $request, $id, \App\Application\EntregasTalleres\UseCases\ObtenerDetalleReciboTallerUseCase $useCase)
    {
        $esParcial = $request->query('es_parcial') == '1';
        $esBodega = $request->query('es_bodega') == '1';
        $prendaBodegaId = (int) $request->query('prenda_bodega_id', 0);
        $data = $useCase->execute($id, $esParcial, $esBodega, $prendaBodegaId);

        return view('entregas_talleres.detalle', [
            'recibo' => $data['recibo'],
            'numeroRecibo' => $data['numeroRecibo'],
            'prendaNombre' => $data['prendaNombre'],
            'encargado' => $data['encargado'],
            'tallasAgrupadas' => $data['tallasAgrupadas'],
            'entregasPorLlave' => $data['entregasPorLlave'],
            'esParcial' => $data['esParcial'],
            'esBodega' => $data['esBodega'] ?? $esBodega,
            'prendaBodegaId' => $data['prendaBodegaId'] ?? $prendaBodegaId
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
        try {
            $search = $request->input('search', '');
            $perPage = (int) $request->input('per_page', 9);

            // En este módulo la búsqueda en tiempo real del index trabaja sobre talleres activos.
            $talleres = app(\App\Application\Talleres\UseCases\ObtenerListadoTalleresUseCase::class)
                ->execute($search, $perPage, 1);

            return response()->json([
                'success' => true,
                'data' => $talleres->items(),
                'pagination' => [
                    'current_page' => $talleres->currentPage(),
                    'last_page' => $talleres->lastPage(),
                    'per_page' => $talleres->perPage(),
                    'total' => $talleres->total(),
                    'from' => $talleres->firstItem(),
                    'to' => $talleres->lastItem(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en entregas-talleres apiSearch: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al buscar talleres',
            ], 500);
        }
    }

    public function destroy($id, \App\Application\EntregasTalleres\UseCases\EliminarEntregaTallerUseCase $useCase)
    {
        $result = $useCase->execute($id);
        return response()->json($result);
    }
}
