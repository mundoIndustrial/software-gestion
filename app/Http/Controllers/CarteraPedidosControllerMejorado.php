<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Application\Services\Asesores\ObtenerDatosFacturaService;
use App\Domain\Pedidos\Services\PedidoSequenceService;
use App\Events\OrdenUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarteraPedidosController extends Controller
{
    /**
     * Aprobar pedido con manejo mejorado de errores y broadcasting
     */
    public function aprobarPedido($id, Request $request)
    {
        $inicio = microtime(true);
        
        try {
            Log::info('[CARTERA] Iniciando aprobación de pedido', [
                'pedido_id' => $id,
                'usuario_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString()
            ]);

            $resultado = DB::transaction(function () use ($id, $request, &$inicio) {
                $pedido = PedidoProduccion::find($id);
                
                if (!$pedido) {
                    Log::warning('[CARTERA] Pedido no encontrado', ['pedido_id' => $id]);
                    return [
                        'success' => false,
                        'message' => 'Pedido no encontrado',
                        'pedido' => null,
                        'numero_pedido' => null
                    ];
                }
                
                // Validar que el pedido esté en estado pendiente de cartera
                if ($pedido->estado !== 'pendiente_cartera') {
                    Log::warning('[CARTERA] Pedido no está en estado pendiente de cartera', [
                        'pedido_id' => $id,
                        'estado_actual' => $pedido->estado
                    ]);
                    return [
                        'success' => false,
                        'message' => 'El pedido no está en estado pendiente de cartera',
                        'pedido' => null,
                        'numero_pedido' => null
                    ];
                }
                
                // Generar número de pedido correlativo solo al aprobar usando servicio centralizado
                try {
                    $pedidoSequenceService = app(PedidoSequenceService::class);
                    $siguienteNumero = $pedidoSequenceService->generarNumeroPedido();
                    
                    Log::info('[CARTERA] Número de pedido generado', [
                        'pedido_id' => $id,
                        'numero_pedido' => $siguienteNumero,
                        'tiempo_secuencia' => round((microtime(true) - $inicio) * 1000, 2) . 'ms'
                    ]);
                } catch (\Exception $e) {
                    Log::error('[CARTERA] Error al generar número de pedido', [
                        'pedido_id' => $id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'Error al generar número de pedido: ' . $e->getMessage(),
                        'pedido' => null,
                        'numero_pedido' => null
                    ];
                }
                
                // Obtener ID de usuario autenticado o null para evitar foreign key issues
                $usuarioId = auth()->check() ? auth()->user()->id : null;
                
                // Actualizar pedido
                $pedido->update([
                    'numero_pedido' => $siguienteNumero,
                    'estado' => 'PENDIENTE_SUPERVISOR',
                    'aprobado_por_usuario_cartera' => $usuarioId,
                    'aprobado_por_cartera_en' => now(),
                ]);
                
                // Generar consecutivo COSTURA-BODEGA cuando CARTERA aprueba
                try {
                    $this->generarConsecutivoCosturaBodega($pedido);
                } catch (\Exception $e) {
                    Log::warning('[CARTERA] Error al generar consecutivo COSTURA-BODEGA (no crítico)', [
                        'pedido_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                    // No fallar la transacción por esto
                }
                
                Log::info('[CARTERA] Pedido aprobado exitosamente', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido_generado' => $siguienteNumero,
                    'aprobado_por' => $usuarioId,
                    'tiempo_total' => round((microtime(true) - $inicio) * 1000, 2) . 'ms'
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Pedido aprobado correctamente',
                    'pedido' => $pedido->fresh(),
                    'numero_pedido' => $siguienteNumero
                ];
            });
            
            // Si la transacción fue exitosa, intentar enviar broadcast (separado de la transacción)
            if ($resultado['success'] && $resultado['pedido']) {
                $broadcastInicio = microtime(true);
                
                try {
                    // Intentar broadcast con timeout corto para no bloquear
                    broadcast(new OrdenUpdated($resultado['pedido'], 'created', ['numero_pedido', 'estado']));
                    
                    Log::info('[CARTERA] Broadcast enviado exitosamente', [
                        'pedido_id' => $resultado['pedido']->id,
                        'numero_pedido' => $resultado['numero_pedido'],
                        'tiempo_broadcast' => round((microtime(true) - $broadcastInicio) * 1000, 2) . 'ms'
                    ]);
                    
                } catch (\Exception $e) {
                    // El broadcast falló pero la aprobación fue exitosa
                    Log::warning('[CARTERA] Broadcast falló (no crítico)', [
                        'pedido_id' => $resultado['pedido']->id,
                        'numero_pedido' => $resultado['numero_pedido'],
                        'error' => $e->getMessage(),
                        'tiempo_broadcast' => round((microtime(true) - $broadcastInicio) * 1000, 2) . 'ms'
                    ]);
                    
                    // No afectar el resultado principal
                }
            }
            
            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'numero_pedido' => $resultado['numero_pedido'],
                'tiempo_total' => round((microtime(true) - $inicio) * 1000, 2) . 'ms'
            ]);
            
        } catch (\Exception $e) {
            $tiempoError = round((microtime(true) - $inicio) * 1000, 2);
            
            Log::error('[CARTERA] Error crítico en aprobarPedido', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tiempo' => $tiempoError . 'ms',
                'usuario_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar pedido: ' . $e->getMessage(),
                'debug_info' => [
                    'pedido_id' => $id,
                    'tiempo' => $tiempoError . 'ms',
                    'error_code' => $e->getCode()
                ]
            ], 500);
        }
    }
    
    // ... (mantener los otros métodos existentes)
    
    /**
     * Generar consecutivo COSTURA-BODEGA cuando CARTERA aprueba un pedido
     * Versión mejorada con mejor manejo de errores
     */
    private function generarConsecutivoCosturaBodega(PedidoProduccion $pedido): void
    {
        try {
            // Obtener el consecutivo actual de COSTURA-BODEGA con lock
            $consecutivoRecibo = \DB::table('consecutivos_recibos')
                ->where('tipo_recibo', 'COSTURA-BODEGA')
                ->lockForUpdate()
                ->first();
            
            if (!$consecutivoRecibo) {
                Log::warning('[CARTERA] No existe consecutivo COSTURA-BODEGA', [
                    'pedido_id' => $pedido->id
                ]);
                return;
            }
            
            // Incrementar el consecutivo
            $nuevoConsecutivo = $consecutivoRecibo->consecutivo_actual + 1;
            
            // Actualizar el consecutivo en consecutivos_recibos
            \DB::table('consecutivos_recibos')
                ->where('tipo_recibo', 'COSTURA-BODEGA')
                ->update([
                    'consecutivo_actual' => $nuevoConsecutivo,
                    'updated_at' => now()
                ]);
            
            // Verificar si ya existe registro para este pedido
            $existeRegistro = \DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedido->id)
                ->where('tipo_recibo', 'COSTURA-BODEGA')
                ->first();
            
            if ($existeRegistro) {
                // Actualizar registro existente
                \DB::table('consecutivos_recibos_pedidos')
                    ->where('id', $existeRegistro->id)
                    ->update([
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'updated_at' => now()
                    ]);
                
                Log::info('[CARTERA] Consecutivo COSTURA-BODEGA actualizado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'consecutivo_anterior' => $existeRegistro->consecutivo_actual,
                    'consecutivo_nuevo' => $nuevoConsecutivo
                ]);
            } else {
                // Crear nuevo registro
                \DB::table('consecutivos_recibos_pedidos')->insert([
                    'pedido_produccion_id' => $pedido->id,
                    'tipo_recibo' => 'COSTURA-BODEGA',
                    'consecutivo_actual' => $nuevoConsecutivo,
                    'consecutivo_inicial' => $nuevoConsecutivo,
                    'prenda_id' => null,
                    'activo' => 1,
                    'notas' => 'Generado automáticamente cuando CARTERA aprobó el pedido',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info('[CARTERA] Consecutivo COSTURA-BODEGA creado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'consecutivo' => $nuevoConsecutivo
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('[CARTERA] Error al generar consecutivo COSTURA-BODEGA', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Relanzar para que se maneje en el método principal
        }
    }
}
