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
     * Display the balanceo index page with paginated prendas.
     * Optimized with eager loading and caching to reduce N+1 queries.
     */
    public function index(Request $request)
    {
        $startTime = microtime(true);
        
        // Optimized query with eager loading and selective columns
        $query = Prenda::with([
            'balanceoActivo' => function($query) {
                $query->select([
                    'id', 
                    'prenda_id', 
                    'sam_total', 
                    'meta_real', 
                    'total_operarios',
                    'activo'
                ])->withCount('operaciones');
            }
        ])
        ->where('activo', true)
        ->select(['id', 'nombre', 'referencia', 'tipo', 'descripcion', 'imagen', 'created_at']);
        
        // Aplicar búsqueda si existe
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('referencia', 'like', '%' . $search . '%')
                  ->orWhere('tipo', 'like', '%' . $search . '%');
            });
        }
        
        // Paginación (12 prendas por página)
        $prendas = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();
        
        // Si es petición AJAX, devolver JSON con HTML de las tarjetas
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            // Renderizar HTML de las tarjetas de prendas
            $cardsHtml = view('balanceo.partials.prenda-cards', compact('prendas'))->render();
            
            return response()->json([
                'cards_html' => $cardsHtml,
                'pagination' => [
                    'current_page' => $prendas->currentPage(),
                    'last_page' => $prendas->lastPage(),
                    'per_page' => $prendas->perPage(),
                    'total' => $prendas->total(),
                    'first_item' => $prendas->firstItem(),
                    'last_item' => $prendas->lastItem(),
                    'links_html' => $prendas->appends(request()->query())->links('vendor.pagination.custom')->render()
                ],
                'debug' => [
                    'server_time_ms' => round($duration, 2)
                ]
            ]);
        }
        
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
            'tipo' => 'required|in:camisa,pantalon,polo,chaqueta,vestido,jean,otro',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // Subir imagen localmente
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $nombreImagen = time() . '_' . uniqid() . '.' . $imagen->getClientOriginalExtension();
            
            // Guardar en public/images/prendas
            $imagen->move(public_path('images/prendas'), $nombreImagen);
            
            // Guardar ruta relativa en DB
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
            'tipo' => 'required|in:camisa,pantalon,polo,chaqueta,vestido,jean,otro',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($prenda->imagen && file_exists(public_path($prenda->imagen))) {
                unlink(public_path($prenda->imagen));
            }
            
            $imagen = $request->file('imagen');
            $nombreImagen = time() . '_' . uniqid() . '.' . $imagen->getClientOriginalExtension();
            
            // Guardar en public/images/prendas
            $imagen->move(public_path('images/prendas'), $nombreImagen);
            
            // Guardar ruta relativa en DB
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
            'porcentaje_eficiencia' => 'nullable|numeric|min:0|max:100',
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
            'letra' => 'nullable|string|max:10',
            'operacion' => 'nullable|string',
            'precedencia' => 'nullable|string|max:10',
            'maquina' => 'nullable|string|max:50',
            'sam' => 'nullable|numeric|min:0',
            'operario' => 'nullable|string|max:255',
            'op' => 'nullable|string|max:50',
            'seccion' => 'nullable|in:DEL,TRAS,ENS,OTRO',
            'orden' => 'nullable|integer|min:0',
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
     * Delete a balanceo.
     */
    public function destroyBalanceo($id)
    {
        $balanceo = Balanceo::findOrFail($id);
        $prendaId = $balanceo->prenda_id;
        
        // Eliminar todas las operaciones asociadas
        $balanceo->operaciones()->delete();
        
        // Eliminar el balanceo
        $balanceo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Balanceo eliminado exitosamente',
            'prenda_id' => $prendaId,
        ]);
    }

    /**
     * Delete a prenda and its associated balanceo and operations.
     */
    public function destroyPrenda($id)
    {
        $prenda = Prenda::findOrFail($id);
        
        // Obtener el balanceo asociado si existe
        $balanceo = $prenda->balanceoActivo;
        
        if ($balanceo) {
            // Eliminar todas las operaciones asociadas al balanceo
            $balanceo->operaciones()->delete();
            
            // Eliminar el balanceo
            $balanceo->delete();
        }
        
        // Eliminar la imagen local si existe
        if ($prenda->imagen && file_exists(public_path($prenda->imagen))) {
            unlink(public_path($prenda->imagen));
        }
        
        // Eliminar la prenda
        $prenda->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prenda eliminada exitosamente',
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

    /**
     * Toggle estado completo del balanceo.
     * Ciclo: null (sin marcar) → true (completo) → false (incompleto) → null
     */
    public function toggleEstadoCompleto(Request $request, $id)
    {
        $balanceo = Balanceo::findOrFail($id);
        
        // Recibir el nuevo estado desde el frontend
        $nuevoEstado = $request->input('estado');
        
        // Convertir string a boolean o null
        if ($nuevoEstado === 'null' || $nuevoEstado === null) {
            $balanceo->estado_completo = null;
        } else {
            $balanceo->estado_completo = filter_var($nuevoEstado, FILTER_VALIDATE_BOOLEAN);
        }
        
        $balanceo->save();

        // Mensaje según el estado
        $mensaje = 'Estado actualizado';
        if ($balanceo->estado_completo === true) {
            $mensaje = 'Balanceo marcado como completo';
        } elseif ($balanceo->estado_completo === false) {
            $mensaje = 'Balanceo marcado como incompleto';
        } else {
            $mensaje = 'Estado desmarcado';
        }

        return response()->json([
            'success' => true,
            'estado_completo' => $balanceo->estado_completo,
            'message' => $mensaje,
        ]);
    }

}
