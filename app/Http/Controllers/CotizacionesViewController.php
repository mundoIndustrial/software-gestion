<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CotizacionesViewController extends Controller
{
    /**
     * Mostrar la vista de gestión de cotizaciones
     * Solo accesible para usuarios con rol 'supervisor-admin'
     * Filtra solo cotizaciones con estado 'entregar'
     */
    public function index(): View
    {
        $cotizaciones = Cotizacion::where('estado', 'entregar')
            ->where('es_borrador', false)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('cotizaciones.index', compact('cotizaciones'));
    }

    /**
     * Obtener detalle de una cotización para mostrar en modal
     */
    public function getCotizacionDetail(Cotizacion $cotizacion)
    {
        try {
            $cotizacion->load([
                'prendasCotizaciones',
                'logoCotizacion'
            ]);
            
            // Convertir fotos y telas a URLs públicas
            if ($cotizacion->prendasCotizaciones) {
                foreach ($cotizacion->prendasCotizaciones as $prenda) {
                    // Convertir fotos a URLs
                    if ($prenda->fotos && is_array($prenda->fotos)) {
                        $prenda->fotos = array_map(function($foto) {
                            return is_string($foto) ? asset($foto) : $foto;
                        }, $prenda->fotos);
                    }
                    
                    // Convertir telas a URLs
                    if ($prenda->telas && is_array($prenda->telas)) {
                        $prenda->telas = array_map(function($tela) {
                            return is_string($tela) ? asset($tela) : $tela;
                        }, $prenda->telas);
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $cotizacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos de cotización para modales (Comparar y Detalles)
     */
    public function getDatosForModal(Cotizacion $cotizacion)
    {
        try {
            $cotizacion->load([
                'prendasCotizaciones',
                'usuario'
            ]);

            // Obtener prendas cotizadas con detalles
            $prendasCotizaciones = $cotizacion->prendasCotizaciones->map(function($prenda) {
                return [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_producto ?? $prenda->nombre_prenda ?? 'Prenda sin nombre',
                    'cantidad' => count($prenda->tallas ?? []) > 0 ? array_sum(array_map(function($t) { return $t['cantidad'] ?? 0; }, $prenda->tallas)) : 0,
                    'detalles_proceso' => $prenda->descripcion ?? 'Sin procesos especificados'
                ];
            })->toArray();

            // Obtener órdenes de producción relacionadas (pedidos creados desde esta cotización)
            $ordenesRelacionadas = [];
            try {
                $ordenesRelacionadas = \DB::table('pedidos_produccion')
                    ->where('cotizacion_id', $id)
                    ->select('id', 'numero_orden', 'estado', 'created_at')
                    ->get()
                    ->map(function($orden) {
                        return [
                            'id' => $orden->id,
                            'numero_orden' => $orden->numero_orden ?? 'N/A',
                            'estado' => $orden->estado ?? 'Sin estado',
                            'created_at' => $orden->created_at
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                // Si falla, continuar sin órdenes
                $ordenesRelacionadas = [];
            }

            // Preparar nombre de asesora
            $nombreAsesora = $cotizacion->asesora;
            if (!$nombreAsesora && $cotizacion->usuario) {
                $nombreAsesora = $cotizacion->usuario->name;
            }

            // Preparar nombre del cliente
            $nombreCliente = $cotizacion->cliente ?? 'Cliente sin nombre';

            // Preparar datos de cotización
            $datoCotizacion = [
                'id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion ?? '#' . $cotizacion->id,
                'estado' => $cotizacion->estado,
                'asesora_nombre' => $nombreAsesora ?? 'Desconocida',
                'empresa' => $cotizacion->cliente ?? 'Sin empresa',
                'nombre_cliente' => $nombreCliente,
                'email_cliente' => 'N/A',
                'telefono_cliente' => 'N/A',
                'ciudad_cliente' => 'N/A',
                'created_at' => $cotizacion->created_at
            ];

            return response()->json([
                'success' => true,
                'cotizacion' => $datoCotizacion,
                'prendas_cotizaciones' => $prendasCotizaciones,
                'ordenes_relacionadas' => $ordenesRelacionadas
            ]);
        } catch (\Exception $e) {
            \Log::error('getDatosForModal error: ' . $e->getMessage(), [
                'cotizacion_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar cotización (por aprobador_cotizaciones)
     */
    public function aprobarAprobador(Cotizacion $cotizacion)
    {
        try {
            $user = auth()->user();
            if (!$user || (!$user->hasRole('aprobador_cotizaciones') && !$user->hasRole('admin'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para realizar esta acción'
                ], 403);
            }

            // Cambiar estado a APROBADA_COTIZACIONES
            $cotizacion->estado = 'APROBADA_COTIZACIONES';
            $cotizacion->save();

            // Registrar en historial si existe la tabla
            try {
                \DB::table('historial_cambios_cotizaciones')->insert([
                    'cotizacion_id' => $cotizacion->id,
                    'estado_anterior' => 'APROBADA_CONTADOR',
                    'estado_nuevo' => 'APROBADA_COTIZACIONES',
                    'usuario_id' => $user->id,
                    'motivo' => 'Aprobada por aprobador de cotizaciones',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } catch (\Exception $e) {
                // Continuar si falla historial
            }

            // Intentar enviar notificación
            try {
                $asesora = $cotizacion->usuario;
                if ($asesora) {
                    $asesora->notify(new \App\Notifications\CotizacionAprobadaNotification($cotizacion));
                }
            } catch (\Exception $e) {
                // Continuar si falla notificación
            }

            return response()->json([
                'success' => true,
                'message' => 'Cotización aprobada correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en aprobarAprobador: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver cotizaciones que necesitan corrección (para rol contador)
     */
    public function porCorregir(): View
    {
        $cotizaciones = Cotizacion::where('estado', 'EN_CORRECCION')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('cotizaciones.por-corregir', compact('cotizaciones'));
    }

    /**
     * Rechazar cotización y reenviárla a la asesora con observaciones
     */
    public function rechazarCotizacion(Cotizacion $cotizacion)
    {
        try {
            \Log::info('🔵 Iniciando rechazarCotizacion', ['cotizacion_id' => $cotizacion->id]);
            
            $user = auth()->user();
            if (!$user || (!$user->hasRole('aprobador_cotizaciones') && !$user->hasRole('admin'))) {
                \Log::warning('❌ Usuario sin permiso para rechazar cotización', ['user_id' => $user?->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para realizar esta acción'
                ], 403);
            }

            $observaciones = request()->json('observaciones', '');
            
            \Log::info('📋 Datos recibidos', [
                'cotizacion_id' => $cotizacion->id,
                'observaciones_length' => strlen($observaciones),
                'estado_actual' => $cotizacion->estado
            ]);

            // Cambiar estado a EN_CORRECCION para que la contador pueda verlas
            $estadoAnterior = $cotizacion->estado;
            $cotizacion->estado = 'EN_CORRECCION';
            $cotizacion->save();
            
            \Log::info('✅ Estado actualizado en BD', [
                'cotizacion_id' => $cotizacion->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $cotizacion->estado
            ]);

            // Registrar en historial si existe la tabla
            try {
                \DB::table('historial_cambios_cotizaciones')->insert([
                    'cotizacion_id' => $cotizacion->id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'EN_CORRECCION',
                    'usuario_id' => $user->id,
                    'motivo' => 'Requiere correcciones: ' . $observaciones,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                \Log::info('✅ Historial registrado');
            } catch (\Exception $e) {
                \Log::warning('⚠️ Error al registrar historial: ' . $e->getMessage());
            }

            // Intentar enviar notificación
            try {
                $asesora = $cotizacion->usuario;
                if ($asesora) {
                    \Log::info('📧 Enviando notificación a asesora', ['asesora_id' => $asesora->id, 'asesora_name' => $asesora->name]);
                    $asesora->notify(new \App\Notifications\CotizacionRechazadaNotification($cotizacion, $observaciones));
                    \Log::info('✅ Notificación enviada exitosamente');
                } else {
                    \Log::warning('⚠️ No se encontró asesora para la cotización');
                }
            } catch (\Exception $e) {
                \Log::error('❌ Error al enviar notificación: ' . $e->getMessage(), [
                    'exception_class' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            \Log::info('✅ rechazarCotizacion completado exitosamente');
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización reenviada a la asesora con observaciones'
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error en rechazarCotizacion: ' . $e->getMessage(), [
                'cotizacion_id' => $cotizacion->id ?? 'unknown',
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener contador de cotizaciones pendientes para aprobador
     * Endpoint: GET /cotizaciones/pendientes-count
     */
    public function cotizacionesPendientesAprobadorCount()
    {
        try {
            $count = Cotizacion::where('estado', 'ENVIADA_APROBADOR')->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count > 0 ? "Hay $count cotización(es) pendiente(s) de aprobación" : 'No hay cotizaciones pendientes'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener contador de cotizaciones pendientes para aprobador', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
