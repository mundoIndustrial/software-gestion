<?php

namespace App\Infrastructure\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoReciboPedido;
use App\Models\Plooter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PlooterController extends Controller
{
    /**
     * Mostrar página de gestión de Plooter
     */
    public function index()
    {
        $user = Auth::user();
        
        // Obtener registros de plooter con sus relaciones
        // Solo costura con fecha_envio registrada
        $recibosPlooter = Plooter::with('recibo.pedido', 'recibo.prenda')
            ->join('consecutivos_recibos_pedidos', 'plooter.consecutivo_recibo_pedido_id', '=', 'consecutivos_recibos_pedidos.id')
            ->whereNotNull('plooter.fecha_envio')
            ->where('consecutivos_recibos_pedidos.tipo_recibo', 'COSTURA')
            ->select('plooter.*')
            ->orderBy('plooter.updated_at', 'desc')
            ->paginate(50);
        
        // Estadísticas
        $totalMarcados = Plooter::count();
        $conFechaEnvio = Plooter::conFechaEnvio()->count();
        $conFechaLlegada = Plooter::conFechaLlegada()->count();
        $pendienteLlegada = Plooter::pendienteLlegada()->count();
        
        return view('insumos.plooter.index', [
            'user' => $user,
            'recibosPlooter' => $recibosPlooter,
            'totalMarcados' => $totalMarcados,
            'conFechaEnvio' => $conFechaEnvio,
            'conFechaLlegada' => $conFechaLlegada,
            'pendienteLlegada' => $pendienteLlegada,
        ]);
    }

    /**
     * Obtener datos de plooter en JSON
     */
    public function obtenerDatos()
    {
        try {
            $recibos = Plooter::with('recibo.pedido', 'recibo.prenda')
                ->orderBy('updated_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $recibos,
                'count' => $recibos->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('PlooterController - Error obteniendo datos plooter: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de plooter',
            ], 500);
        }
    }

    /**
     * Filtrar registros por estado del recibo
     */
    public function filtrarPorEstado($estado)
    {
        try {
            $recibos = Plooter::with('recibo.pedido', 'recibo.prenda')
                ->whereHas('recibo', function($query) use ($estado) {
                    $query->where('estado', $estado);
                })
                ->orderBy('updated_at', 'desc')
                ->paginate(50);
            
            return view('insumos.plooter.index', [
                'recibosPlooter' => $recibos,
                'filtroEstado' => $estado,
            ]);
        } catch (\Exception $e) {
            Log::error('PlooterController - Error filtrando por estado: ' . $e->getMessage());
            return back()->with('error', 'Error al filtrar recibos');
        }
    }

    /**
     * Remover un registro de plooter
     */
    public function remover($id)
    {
        try {
            $plooter = Plooter::findOrFail($id);
            $plooter->delete();
            
            Log::info('PlooterController - Registro de plooter removido', [
                'plooter_id' => $id,
                'user_id' => Auth::id(),
            ]);
            
            return back()->with('success', 'Registro removido de plooter correctamente');
        } catch (\Exception $e) {
            Log::error('PlooterController - Error removiendo registro: ' . $e->getMessage());
            return back()->with('error', 'Error al remover registro');
        }
    }

    /**
     * Registrar fecha de envío
     */
    public function registrarFechaEnvio($reciboId)
    {
        try {
            $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
            
            // Crear o actualizar registro en plooter
            $plooter = Plooter::firstOrCreate(
                ['consecutivo_recibo_pedido_id' => $reciboId],
                ['fecha_envio' => now()]
            );
            
            // Si ya existe, solo actualizar fecha_envio
            if ($plooter->wasRecentlyCreated === false) {
                $plooter->update(['fecha_envio' => now()]);
            }
            
            Log::info('PlooterController - Fecha de envío registrada', [
                'recibo_id' => $reciboId,
                'plooter_id' => $plooter->id,
                'fecha_envio' => now(),
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Fecha de envío registrada',
                'fecha_envio' => $plooter->fecha_envio->format('d/m/Y h:i A'),
            ]);
        } catch (\Exception $e) {
            Log::error('PlooterController - Error registrando fecha de envío: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar fecha de envío',
            ], 500);
        }
    }

    /**
     * Registrar fecha de llegada
     */
    public function registrarFechaLlegada(Request $request, $reciboId)
    {
        try {
            $request->validate([
                'fecha_llegada' => 'nullable|date',
            ]);
            
            $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
            $fechaLlegada = $request->input('fecha_llegada');
            
            // Si se envía una fecha, agregar la hora actual del servidor
            if ($fechaLlegada) {
                $fechaLlegada = Carbon::createFromFormat('Y-m-d', $fechaLlegada)
                    ->setTime(now()->hour, now()->minute, now()->second);
            }
            
            // Obtener o crear registro en plooter
            $plooter = Plooter::firstOrCreate(
                ['consecutivo_recibo_pedido_id' => $reciboId],
                ['fecha_llegada' => $fechaLlegada]
            );
            
            // Actualizar fecha_llegada (puede ser null para eliminarla)
            $plooter->update(['fecha_llegada' => $fechaLlegada]);
            
            Log::info('PlooterController - Fecha de llegada actualizada', [
                'recibo_id' => $reciboId,
                'plooter_id' => $plooter->id,
                'fecha_llegada' => $fechaLlegada,
                'user_id' => Auth::id(),
            ]);
            
            $respuesta = [
                'success' => true,
                'message' => $fechaLlegada ? 'Fecha de llegada registrada' : 'Fecha de llegada eliminada',
            ];
            
            if ($fechaLlegada) {
                $respuesta['fecha_llegada'] = $plooter->fecha_llegada->format('d/m/Y h:i A');
            }
            
            return response()->json($respuesta);
        } catch (\Exception $e) {
            Log::error('PlooterController - Error actualizando fecha de llegada: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar fecha de llegada',
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de plooter
     */
    public function obtenerEstadisticas()
    {
        try {
            $estadisticas = [
                'total_registros' => Plooter::count(),
                'con_fecha_envio' => Plooter::conFechaEnvio()->count(),
                'con_fecha_llegada' => Plooter::conFechaLlegada()->count(),
                'pendiente_llegada' => Plooter::pendienteLlegada()->count(),
                'por_estado' => Plooter::with('recibo')
                    ->get()
                    ->groupBy('recibo.estado')
                    ->map(function($group) {
                        return $group->count();
                    }),
                'por_area' => Plooter::with('recibo')
                    ->get()
                    ->groupBy('recibo.area')
                    ->map(function($group) {
                        return $group->count();
                    }),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $estadisticas,
            ]);
        } catch (\Exception $e) {
            Log::error('PlooterController - Error obteniendo estadísticas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
            ], 500);
        }
    }
}

