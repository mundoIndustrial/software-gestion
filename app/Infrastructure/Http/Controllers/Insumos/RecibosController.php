<?php

namespace App\Infrastructure\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivosRecibosPedidos;
use App\Models\Plooter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Si marcado es true, crea un registro en la tabla plooter
     * Si marcado es false, elimina el registro en la tabla plooter
     */
    public function toggleMarcado(Request $request, $reciboId)
    {
        try {
            $request->validate([
                'marcado' => 'required|boolean',
            ]);

            $recibo = ConsecutivosRecibosPedidos::findOrFail($reciboId);
            $marcado = $request->boolean('marcado');
            
            // Actualizar marcación en consecutivos_recibos_pedidos
            $recibo->update(['marcar_plooter' => $marcado]);

            // Crear o eliminar registro en tabla plooter
            if ($marcado) {
                // Crear registro en plooter si no existe
                $plooter = Plooter::firstOrCreate(
                    ['consecutivo_recibo_pedido_id' => $reciboId],
                    ['fecha_envio' => now()]
                );
                
                Log::info('Recibo marcado para plooter', ['recibo_id' => $reciboId, 'fecha_envio' => $plooter->fecha_envio]);
            } else {
                // Eliminar registro en plooter si existe
                Plooter::where('consecutivo_recibo_pedido_id', $reciboId)->delete();
                
                Log::info('Recibo desmarcado de plooter', ['recibo_id' => $reciboId]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estado de marcado actualizado',
                'data' => $recibo,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar marcado: ' . $e->getMessage());
            
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
            $validated = $request->validate([
                'motivo' => 'required|string|min:10|max:500',
            ]);

            $resultado = DB::transaction(function () use ($reciboId, $validated) {
                // Bloqueo de fila para evitar condiciones de carrera.
                $recibo = ConsecutivosRecibosPedidos::query()
                    ->lockForUpdate()
                    ->findOrFail($reciboId);

                // Solo se modifica el RECIBO (consecutivos_recibos_pedidos).
                // NO se altera el estado del pedido principal.
                $recibo->update([
                    'estado' => 'DEVUELTO_ASESOR',
                    'notas' => $validated['motivo'],
                ]);

                Log::info('[Insumos][pasarRevisar] Recibo pasado a revisar', [
                    'recibo_id' => (int) $recibo->id,
                    'pedido_produccion_id' => (int) $recibo->pedido_produccion_id,
                    'nuevo_estado_recibo' => $recibo->estado,
                ]);

                return $recibo->fresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Recibo pasado a revisar correctamente',
                'data' => $resultado,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al pasar a revisar: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al pasar a revisar: ' . $e->getMessage()
            ], 500);
        }
    }
}
