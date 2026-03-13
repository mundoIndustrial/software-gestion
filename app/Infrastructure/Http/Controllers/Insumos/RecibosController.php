<?php

namespace App\Infrastructure\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivosRecibosPedidos;
use Illuminate\Http\Request;

/**
 * Controller para acciones sobre recibos en el módulo Insumos
 * Ubicado en Infrastructure para separar responsabilidades
 */
class RecibosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('insumos-access');
    }

    /**
     * Alterar el estado de marcado de un recibo (marcar_plooter)
     */
    public function toggleMarcado(Request $request, $reciboId)
    {
        try {
            $request->validate([
                'marcado' => 'required|boolean',
            ]);

            $recibo = ConsecutivosRecibosPedidos::findOrFail($reciboId);
            $recibo->update(['marcar_plooter' => $request->boolean('marcado')]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de marcado actualizado',
                'data' => $recibo,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar marcado: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado de marcado'
            ], 500);
        }
    }

    /**
     * Pasar recibo a revisar (cambiar estado a DEVUELTO_ASESOR)
     */
    public function pasarRevisar(Request $request, $reciboId)
    {
        try {
            $request->validate([
                'motivo' => 'required|string|min:10|max:500',
            ]);

            $recibo = ConsecutivosRecibosPedidos::findOrFail($reciboId);
            
            // Cambiar estado a DEVUELTO_ASESOR
            $recibo->update([
                'estado' => 'DEVUELTO_ASESOR',
                'notas' => $request->input('motivo'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recibo pasado a revisar correctamente',
                'data' => $recibo,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al pasar a revisar: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al pasar a revisar: ' . $e->getMessage()
            ], 500);
        }
    }
}
