<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Application\Services\Asesores\ClientesAsesorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ClientesController extends Controller
{
    public function __construct(
        private readonly ClientesAsesorService $clientesAsesorService
    ) {
    }

    // Listar clientes
    public function index()
    {
        $clientes = $this->clientesAsesorService->listarPorUsuario((int) Auth::id(), 15);
        
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

        $this->clientesAsesorService->crear((int) Auth::id(), $request->only([
            'nombre',
            'email',
            'telefono',
            'ciudad',
            'notas',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado correctamente'
        ]);
    }

    // Actualizar cliente
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|unique:clientes,nombre,' . $id,
            'email' => 'nullable|email',
            'telefono' => 'nullable',
            'ciudad' => 'nullable',
            'notas' => 'nullable'
        ]);

        try {
            $this->clientesAsesorService->actualizar((int) Auth::id(), (int) $id, $request->only([
                'nombre',
                'email',
                'telefono',
                'ciudad',
                'notas',
            ]));
        } catch (AccessDeniedHttpException $e) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado'
        ]);
    }

    // Eliminar cliente
    public function destroy($id)
    {
        try {
            $this->clientesAsesorService->eliminar((int) Auth::id(), (int) $id);
        } catch (AccessDeniedHttpException $e) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado'
        ]);
    }
}
