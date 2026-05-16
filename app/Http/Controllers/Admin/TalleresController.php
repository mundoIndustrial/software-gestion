<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class TalleresController extends Controller
{
    public function index(Request $request, \App\Application\Talleres\UseCases\ObtenerListadoTalleresUseCase $useCase)
    {
        $search = $request->input('search');
        $view = $request->input('view', 'talleres');
        
        // Solo cargar talleres si estamos en la vista de talleres
        if ($view === 'talleres') {
            $talleres = $useCase->execute($search);
        } else {
            $talleres = collect(); // Colección vacía
        }
        
        return view('admin.talleres.index', compact('talleres', 'search', 'view'));
    }

    public function showRecibos($id, \App\Application\Talleres\UseCases\ObtenerDashboardTallerUseCase $useCase)
    {
        $data = $useCase->execute($id);

        return view('admin.talleres.show', [
            'taller' => $data['taller'],
            'recibos' => $data['recibos'],
            'totalCarga' => $data['total'],
            'completados' => $data['completados']
        ]);
    }

    public function showEntregas($taller_id, $recibo_id, $es_parcial, \App\Application\Talleres\UseCases\ObtenerDetalleEntregasUseCase $useCase)
    {
        $isParcial = $es_parcial == '1';
        $data = $useCase->execute($taller_id, $recibo_id, $isParcial);

        if (!$data) {
            abort(404, 'Recibo no encontrado');
        }

        return view('admin.talleres.entregas', [
            'taller' => $data['taller'],
            'recibo' => $data['recibo'],
            'entregasAgrupadas' => $data['entregasAgrupadas'],
            'totalGeneral' => $data['totalGeneral']
        ]);
    }

    // API endpoints para SPA
    public function apiRecibos($id, \App\Application\Talleres\UseCases\ObtenerDashboardTallerUseCase $useCase)
    {
        $data = $useCase->execute($id);

        return response()->json([
            'taller_id' => $id,
            'taller_name' => $data['taller']->name,
            'recibos' => $data['recibos'],
            'total' => $data['total'],
            'completados' => $data['completados'],
            'pendientes' => $data['pendientes']
        ]);
    }

    public function apiEntregas($taller_id, $recibo_id, $es_parcial, \App\Application\Talleres\UseCases\ObtenerDetalleEntregasUseCase $useCase)
    {
        $isParcial = $es_parcial == '1';
        $data = $useCase->execute($taller_id, $recibo_id, $isParcial);

        if (!$data) {
            return response()->json(['error' => 'Recibo no encontrado'], 404);
        }

        // Transformar a array para JSON (quitar objetos Carbon)
        $entregasFormateadas = $data['entregasAgrupadas']->map(function ($grupo) {
            return $grupo->map(function ($item) {
                unset($item['fecha_obj']);
                return $item;
            })->values();
        })->values();

        return response()->json([
            'recibo' => $data['recibo'],
            'entregas' => $entregasFormateadas,
            'total' => $data['totalGeneral']
        ]);
    }

    public function apiOrdenes(
        Request $request,
        \App\Application\Talleres\UseCases\ObtenerOrdenesAsignadasUseCase $useCase
    ) {
        try {
            $search = $request->input('search', '');
            $page = $request->input('page', 1);

            $resultado = $useCase->execute($search, $page);

            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error en apiOrdenes: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'error' => 'Error al cargar las órdenes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($id, \App\Application\Talleres\UseCases\ToggleEstadoTallerUseCase $useCase)
    {
        $result = $useCase->execute($id);
        return response()->json($result);
    }

    public function actualizarPrecio(Request $request, $id)
    {
        $request->validate([
            'precio' => 'required|numeric|min:0'
        ]);

        $entrega = \App\Models\EntregaReciboCostura::findOrFail($id);
        $entrega->precio = $request->precio;
        $entrega->save();

        return response()->json(['success' => true]);
    }

    public function store(Request $request, \App\Application\Talleres\UseCases\CrearTallerUseCase $useCase)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $result = $useCase->execute($request->all());

        return response()->json($result);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $user = \App\Models\User::findOrFail($id);
        $user->name = $request->name;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Taller actualizado correctamente.']);
    }
}

