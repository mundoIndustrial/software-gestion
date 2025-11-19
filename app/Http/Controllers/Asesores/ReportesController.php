<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportesController extends Controller
{
    // Listar reportes
    public function index()
    {
        $reportes = Reporte::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('asesores.reportes.index', compact('reportes'));
    }

    // Crear reporte
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required',
            'descripcion' => 'nullable',
            'tipo' => 'required|in:ventas,produccion,clientes,general',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date'
        ]);

        Reporte::create([
            'user_id' => Auth::id(),
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reporte creado correctamente'
        ]);
    }

    // Actualizar reporte
    public function update(Request $request, $id)
    {
        $reporte = Reporte::findOrFail($id);
        
        if ($reporte->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'titulo' => 'required',
            'descripcion' => 'nullable',
            'tipo' => 'required|in:ventas,produccion,clientes,general',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date'
        ]);

        $reporte->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Reporte actualizado'
        ]);
    }

    // Eliminar reporte
    public function destroy($id)
    {
        $reporte = Reporte::findOrFail($id);
        
        if ($reporte->user_id !== Auth::id()) {
            abort(403);
        }

        $reporte->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reporte eliminado'
        ]);
    }
}
