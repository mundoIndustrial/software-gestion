<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientesController extends Controller
{
    // Listar clientes
    public function index()
    {
        $clientes = Cliente::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('asesores.clientes.index', compact('clientes'));
    }

    // Crear cliente
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|unique:clientes,nombre',
            'email' => 'nullable|email',
            'telefono' => 'nullable',
            'ciudad' => 'nullable',
            'notas' => 'nullable'
        ]);

        Cliente::create([
            'user_id' => Auth::id(),
            'nombre' => $request->nombre,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'ciudad' => $request->ciudad,
            'notas' => $request->notas
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado correctamente'
        ]);
    }

    // Actualizar cliente
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        
        if ($cliente->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'nombre' => 'required|unique:clientes,nombre,' . $id,
            'email' => 'nullable|email',
            'telefono' => 'nullable',
            'ciudad' => 'nullable',
            'notas' => 'nullable'
        ]);

        $cliente->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado'
        ]);
    }

    // Eliminar cliente
    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        
        if ($cliente->user_id !== Auth::id()) {
            abort(403);
        }

        $cliente->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado'
        ]);
    }
}
