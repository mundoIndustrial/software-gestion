<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prenda;
use App\Models\Balanceo;
use App\Models\OperacionBalanceo;
use Illuminate\Support\Facades\Storage;

class BalanceoController extends Controller
{
    /**
     * Display the balanceo index page with all prendas.
     */
    public function index()
    {
        $prendas = Prenda::with('balanceoActivo')->where('activo', true)->get();
        return view('balanceo.index', compact('prendas'));
    }

    /**
     * Show the form for creating a new prenda.
     */
    public function createPrenda()
    {
        return view('balanceo.create-prenda');
    }

    /**
     * Store a newly created prenda.
     */
    public function storePrenda(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'referencia' => 'nullable|string|unique:prendas,referencia',
            'tipo' => 'required|in:camisa,pantalon,polo,chaqueta,vestido,otro',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
            $imagen->move(public_path('images/prendas'), $nombreImagen);
            $validated['imagen'] = 'images/prendas/' . $nombreImagen;
        }

        $prenda = Prenda::create($validated);

        return redirect()->route('balanceo.index')->with('success', 'Prenda creada exitosamente');
    }

    /**
     * Show the form for editing a prenda.
     */
    public function editPrenda($id)
    {
        $prenda = Prenda::findOrFail($id);
        return view('balanceo.edit-prenda', compact('prenda'));
    }

    /**
     * Update the specified prenda.
     */
    public function updatePrenda(Request $request, $id)
    {
        $prenda = Prenda::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'referencia' => 'nullable|string|unique:prendas,referencia,' . $id,
            'tipo' => 'required|in:camisa,pantalon,polo,chaqueta,vestido,otro',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($prenda->imagen && file_exists(public_path($prenda->imagen))) {
                unlink(public_path($prenda->imagen));
            }
            
            $imagen = $request->file('imagen');
            $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
            $imagen->move(public_path('images/prendas'), $nombreImagen);
            $validated['imagen'] = 'images/prendas/' . $nombreImagen;
        }

        $prenda->update($validated);

        return redirect()->route('balanceo.show', $id)->with('success', 'Prenda actualizada exitosamente');
    }

    /**
     * Show the balanceo detail for a specific prenda.
     */
    public function show($id)
    {
        $prenda = Prenda::with(['balanceoActivo.operaciones'])->findOrFail($id);
        $balanceo = $prenda->balanceoActivo;

        return view('balanceo.show', compact('prenda', 'balanceo'));
    }

    /**
     * Create a new balanceo for a prenda.
     */
    public function createBalanceo($prendaId)
    {
        $prenda = Prenda::findOrFail($prendaId);
        
        // Desactivar balanceos anteriores
        Balanceo::where('prenda_id', $prendaId)->update(['activo' => false]);
        
        // Crear nuevo balanceo
        $balanceo = Balanceo::create([
            'prenda_id' => $prendaId,
            'version' => '1.0',
            'activo' => true,
        ]);

        return redirect()->route('balanceo.show', $prendaId)->with('success', 'Nuevo balanceo creado');
    }

    /**
     * Update balanceo parameters.
     */
    public function updateBalanceo(Request $request, $id)
    {
        $validated = $request->validate([
            'total_operarios' => 'required|integer|min:1',
            'turnos' => 'required|integer|min:1',
            'horas_por_turno' => 'required|numeric|min:0.1',
        ]);

        $balanceo = Balanceo::findOrFail($id);
        $balanceo->update($validated);
        $balanceo->calcularMetricas();

        return response()->json([
            'success' => true,
            'balanceo' => $balanceo->fresh(),
        ]);
    }

    /**
     * Store a new operation in the balanceo.
     */
    public function storeOperacion(Request $request, $balanceoId)
    {
        $validated = $request->validate([
            'letra' => 'required|string|max:10',
            'operacion' => 'required|string',
            'precedencia' => 'nullable|string|max:10',
            'maquina' => 'nullable|string|max:50',
            'sam' => 'required|numeric|min:0',
            'operario' => 'nullable|string|max:255',
            'op' => 'nullable|string|max:50',
            'seccion' => 'required|in:DEL,TRAS,ENS,OTRO',
            'operario_a' => 'nullable|string|max:255',
            'orden' => 'required|integer|min:0',
        ]);

        $validated['balanceo_id'] = $balanceoId;
        $operacion = OperacionBalanceo::create($validated);

        // Recalcular métricas del balanceo
        $balanceo = Balanceo::findOrFail($balanceoId);
        $balanceo->calcularMetricas();

        return response()->json([
            'success' => true,
            'operacion' => $operacion,
            'balanceo' => $balanceo->fresh(),
        ]);
    }

    /**
     * Update an existing operation.
     */
    public function updateOperacion(Request $request, $id)
    {
        $validated = $request->validate([
            'letra' => 'sometimes|string|max:10',
            'operacion' => 'sometimes|string',
            'precedencia' => 'nullable|string|max:10',
            'maquina' => 'nullable|string|max:50',
            'sam' => 'sometimes|numeric|min:0',
            'operario' => 'nullable|string|max:255',
            'op' => 'nullable|string|max:50',
            'seccion' => 'sometimes|in:DEL,TRAS,ENS,OTRO',
            'operario_a' => 'nullable|string|max:255',
            'orden' => 'sometimes|integer|min:0',
        ]);

        $operacion = OperacionBalanceo::findOrFail($id);
        $operacion->update($validated);

        // Recalcular métricas del balanceo
        $operacion->balanceo->calcularMetricas();

        return response()->json([
            'success' => true,
            'operacion' => $operacion->fresh(),
            'balanceo' => $operacion->balanceo->fresh(),
        ]);
    }

    /**
     * Delete an operation.
     */
    public function destroyOperacion($id)
    {
        $operacion = OperacionBalanceo::findOrFail($id);
        $balanceo = $operacion->balanceo;
        $operacion->delete();

        // Recalcular métricas del balanceo
        $balanceo->calcularMetricas();

        return response()->json([
            'success' => true,
            'balanceo' => $balanceo->fresh(),
        ]);
    }

    /**
     * Get balanceo data as JSON.
     */
    public function getBalanceoData($id)
    {
        $balanceo = Balanceo::with('operaciones')->findOrFail($id);
        return response()->json($balanceo);
    }
}
