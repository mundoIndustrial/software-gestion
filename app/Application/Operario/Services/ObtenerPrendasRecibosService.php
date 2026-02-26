<?php

namespace App\Application\Operario\Services;

use App\Models\User;
use App\Models\PrendaPedido;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Support\Collection;

/**
 * Service: ObtenerPrendasRecibosService
 * 
 * Obtiene las prendas con sus recibos de costura para un operario
 */
class ObtenerPrendasRecibosService
{
    /**
     * Obtener prendas con recibos de costura del operario
     */
    public function obtenerPrendasConRecibos(User $usuario): Collection
    {
        // Obtener tipo de operario
        $tipoOperario = $this->obtenerTipoOperario($usuario);

        \Log::info('🔍 [obtenerPrendasConRecibos] TIPO OPERARIO DETECTADO', [
            'usuario' => $usuario->name,
            'usuario_id' => $usuario->id,
            'tipo_operario' => $tipoOperario,
            'es_costura_reflectivo' => $tipoOperario === 'costura-reflectivo' ? 'SI' : 'NO'
        ]);

        // Determinar tipos de recibo según el rol
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];
        if ($tipoOperario === 'costura-reflectivo') {
            // Para costura-reflectivo, SOLO mostrar COSTURA y REFLECTIVO (sin COSTURA-BODEGA)
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }

        // Obtener todos los recibos de costura activos con relaciones (incluyendo procesos)
        $query = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->whereIn('area', ['Corte', 'Costura', 'Control de Calidad'])
            ->with(['prenda', 'prenda.pedidoProduccion', 'prenda.procesosPrenda', 'pedido', 'pedido.prendas']);
        
        // Para cortadores: excluir PENDIENTE_INSUMOS (misma lógica que /recibos-costura)
        if ($tipoOperario === 'cortador') {
            $query->where('estado', '!=', 'PENDIENTE_INSUMOS');
        }
        
        $recibos = $query->orderBy('created_at', 'desc')->get();

        // Para costura-reflectivo: AGREGAR REFLECTIVO aprobados SIN validar encargado
        if ($tipoOperario === 'costura-reflectivo') {
            \Log::info('🔍 [REFLECTIVO APROBADOS] BUSCANDO prendas con estado APROBADO en pedidos_procesos_prenda_detalles', [
                'usuario' => $usuario->name,
                'recibos_costura_actuales' => $recibos->count()
            ]);
            
            // Buscar TODAS las prendas con detalles en estado APROBADO
            $prendasAprobadas = PedidosProcesosPrendaDetalle::where('estado', 'APROBADO')
                ->with(['prenda', 'prenda.pedidoProduccion'])
                ->get()
                ->pluck('prenda')
                ->unique('id');
            
            \Log::info('🔍 [REFLECTIVO APROBADOS] Prendas aprobadas encontradas', [
                'total_prendas_aprobadas' => count($prendasAprobadas)
            ]);
            
            // Para cada prenda aprobada, buscar si tiene recibo REFLECTIVO
            foreach ($prendasAprobadas as $prendaAprobada) {
                if (!$prendaAprobada || !$prendaAprobada->pedidoProduccion) {
                    \Log::info('🔍 [REFLECTIVO] Prenda o pedido sin relación', [
                        'prenda_id' => $prendaAprobada->id ?? 'null'
                    ]);
                    continue;
                }

                $reciboReflectivo = ConsecutivoReciboPedido::where('prenda_id', $prendaAprobada->id)
                    ->where('tipo_recibo', 'REFLECTIVO')
                    ->where('activo', 1)
                    ->first();

                // Si existe recibo REFLECTIVO, agregarlo SIN validar encargado
                if ($reciboReflectivo) {
                    $recibos->push($reciboReflectivo);
                    
                    \Log::info('✅ [REFLECTIVO APROBADO AGREGADO]', [
                        'numero_pedido' => $prendaAprobada->pedidoProduccion->numero_pedido,
                        'prenda_id' => $prendaAprobada->id,
                        'recibo_id' => $reciboReflectivo->id,
                        'consecutivo' => $reciboReflectivo->consecutivo_actual,
                        'estado_aprobado' => 'YES'
                    ]);
                } else {
                    \Log::info('❌ [REFLECTIVO NO ENCONTRADO] Prenda aprobada sin recibo REFLECTIVO', [
                        'numero_pedido' => $prendaAprobada->pedidoProduccion->numero_pedido,
                        'prenda_id' => $prendaAprobada->id
                    ]);
                }
            }
            
            // Re-ordenar por fecha
            $recibos = $recibos->sortByDesc('created_at');
        }

        \Log::info(' [ObtenerPrendasRecibosService] Recibos encontrados', [
            'total_recibos' => $recibos->count(),
            'tipos_buscados' => $tiposRecibo,
            'areas_permitidas' => ['Corte', 'Costura', 'Control de Calidad'],
            'prenda_ids' => $recibos->pluck('prenda_id')->toArray()
        ]);

        // Agrupar por prenda (o por pedido si es REFLECTIVO sin prenda_id)
        $prendasAgrupadas = $recibos->groupBy(function ($recibo) {
            // Si tiene prenda_id, agrupar por prenda
            if ($recibo->prenda_id) {
                return 'prenda_' . $recibo->prenda_id;
            }
            // Si no tiene prenda_id (REFLECTIVO), agrupar por pedido
            return 'pedido_' . $recibo->pedido_produccion_id;
        })->flatMap(function ($recibosDelaPrenda) use ($tipoOperario, $usuario) {
            $primeRecibo = $recibosDelaPrenda->first();
            
            // Obtener la prenda
            if ($primeRecibo->prenda_id && $primeRecibo->prenda) {
                $prenda = $primeRecibo->prenda;
                $pedido = $prenda->pedidoProduccion;
            } else if ($primeRecibo->pedido_produccion_id && $primeRecibo->pedido) {
                // Para REFLECTIVO sin prenda_id, obtener la primera prenda del pedido
                $pedido = $primeRecibo->pedido;
                $prenda = $pedido->prendas->first();
            } else {
                \Log::info(' [Filtro 0] No se pudo obtener prenda o pedido');
                return [];
            }
            
            // Validar que prenda y pedido existan
            if (!$prenda || !$pedido) {
                \Log::info(' [Filtro 1] Prenda o pedido no existe');
                return [];
            }
            
            \Log::info(' [Prenda Validada]', [
                'numero_pedido' => $pedido->numero_pedido,
                'nombre_prenda' => $prenda->nombre_prenda,
                'area' => $pedido->area
            ]);

            // Separar recibos por tipo - crear una entrada para cada tipo
            $recibosPorTipo = $recibosDelaPrenda->groupBy('tipo_recibo');
            
            $resultados = [];
            foreach ($recibosPorTipo as $tipoRecibo => $recibosDelTipo) {
                // Validaciones específicas por tipo de recibo
                if (strtoupper($tipoRecibo) === 'REFLECTIVO') {
                    // REFLECTIVO: Si está aprobado, se muestra sin validar encargado
                    // Los recibos REFLECTIVO ya vienen filtrados desde pedidos_procesos_prenda_detalles
                    \Log::info(' [REFLECTIVO VÁLIDO]', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_id' => $prenda->id,
                        'tipo_recibo' => strtoupper($tipoRecibo),
                        'estado' => $primeRecibo->estado,
                        'sin_validacion_encargado' => true
                    ]);
                } else if (strtoupper($tipoRecibo) === 'COSTURA' || strtoupper($tipoRecibo) === 'COSTURA-BODEGA') {
                    if ($tipoOperario === 'cortador') {
                        // Para cortadores: verificar que exista proceso "Corte" con encargado = usuario
                        $usuarioNombre = strtolower(trim($usuario->name));
                        $tieneProcesoCorte = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                            ->whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
                            ->exists();
                        
                        if (!$tieneProcesoCorte) {
                            \Log::info(' [Filtro CORTADOR] No tiene proceso Corte asignado', [
                                'prenda_id' => $prenda->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario' => $usuario->name
                            ]);
                            continue;
                        }
                    } else {
                        // COSTURA/COSTURA-BODEGA: Validar que el usuario sea el encargado del proceso Costura para esta PRENDA
                        // (Sin restricción de estado, permite: Pendiente, En Ejecución, etc)
                        // EXCEPCIÓN: vista-costura ve TODOS sin restricción de encargado
                        if ($tipoOperario === 'vista-costura') {
                            // vista-costura ve todos los recibos sin validación de encargado
                            \Log::info(' [Filtro VISTA-COSTURA] ✓ Usuario con rol vista-costura ve todos los recibos', [
                                'prenda_id' => $prenda->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario' => $usuario->name
                            ]);
                        } else if ($tipoOperario === 'costurero') {
                            $usuarioNombre = strtolower(trim($usuario->name));
                            
                            // Buscar proceso Costura específicamente para esta prenda
                            $procesoCosturaDelaPrenda = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                                ->where('prenda_pedido_id', $prenda->id)
                                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                                ->first();
                            
                            if (!$procesoCosturaDelaPrenda || !$procesoCosturaDelaPrenda->encargado) {
                                \Log::info(' [Filtro COSTURERO] Prenda sin proceso Costura asignado', [
                                    'prenda_id' => $prenda->id,
                                    'numero_pedido' => $pedido->numero_pedido,
                                    'usuario' => $usuario->name
                                ]);
                                continue;
                            }
                            
                            $encargadoDelProceso = strtolower(trim($procesoCosturaDelaPrenda->encargado));
                            
                            if ($encargadoDelProceso !== $usuarioNombre) {
                                \Log::info(' [Filtro COSTURERO] Usuario no es encargado de la prenda', [
                                    'prenda_id' => $prenda->id,
                                    'numero_pedido' => $pedido->numero_pedido,
                                    'usuario_actual' => $usuario->name,
                                    'encargado_prenda' => $procesoCosturaDelaPrenda->encargado
                                ]);
                                continue;
                            }
                            
                            \Log::info(' [Filtro COSTURERO] ✓ Usuario es encargado de esta prenda', [
                                'prenda_id' => $prenda->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario' => $usuario->name
                            ]);
                        } else if ($tipoOperario === 'costura-reflectivo') {
                            $usuarioNombre = strtolower(trim($usuario->name));
                            
                            // Buscar proceso Costura específicamente para esta prenda
                            $procesoCosturaDelaPrenda = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                                ->where('prenda_pedido_id', $prenda->id)
                                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                                ->first();
                            
                            if (!$procesoCosturaDelaPrenda || !$procesoCosturaDelaPrenda->encargado) {
                                \Log::info(' [Filtro COSTURA-REFLECTIVO] Prenda sin proceso Costura asignado', [
                                    'prenda_id' => $prenda->id,
                                    'numero_pedido' => $pedido->numero_pedido,
                                    'usuario' => $usuario->name
                                ]);
                                continue;
                            }
                            
                            $encargadoDelProceso = strtolower(trim($procesoCosturaDelaPrenda->encargado));
                            
                            if ($encargadoDelProceso !== $usuarioNombre) {
                                \Log::info(' [Filtro COSTURA-REFLECTIVO] Usuario no es encargado de la prenda', [
                                    'prenda_id' => $prenda->id,
                                    'numero_pedido' => $pedido->numero_pedido,
                                    'usuario_actual' => $usuario->name,
                                    'encargado_prenda' => $procesoCosturaDelaPrenda->encargado
                                ]);
                                continue;
                            }
                            
                            \Log::info(' [Filtro COSTURA-REFLECTIVO] ✓ Usuario es encargado de esta prenda', [
                                'prenda_id' => $prenda->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario' => $usuario->name
                            ]);
                        }
                    }
                }
                
                $resultados[] = [
                    'prenda_id' => $prenda->id,
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => $prenda->de_bodega,
                    'recibos' => $recibosDelTipo->map(function ($recibo) {
                        return [
                            'id' => $recibo->id,
                            'tipo_recibo' => $recibo->tipo_recibo,
                            'consecutivo_actual' => $recibo->consecutivo_actual,
                            'consecutivo_inicial' => $recibo->consecutivo_inicial,
                            'notas' => $recibo->notas,
                            'creado_en' => $recibo->created_at,
                        ];
                    })->toArray(),
                    'total_recibos' => $recibosDelTipo->count(),
                    'fecha_creacion' => $prenda->created_at,
                ];
            }
            
            return $resultados;
        })->values();

        return $prendasAgrupadas;
    }

    /**
     * Obtener prendas para cortador (basado en procesos/encargado, no recibos)
     * Los cortadores no tienen tipo de recibo propio, se filtran por 
     * procesos de "Corte" donde el usuario sea el encargado
     */
    private function obtenerPrendasParaCortador(User $usuario): Collection
    {
        $usuarioNormalizado = strtolower(trim($usuario->name));

        // Obtener todos los pedidos que tengan proceso de Corte asignado al usuario
        $pedidos = \App\Models\PedidoProduccion::with(['prendas'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($pedido) use ($usuarioNormalizado) {
                $procesos = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)->get();
                
                if ($procesos->isEmpty()) {
                    return false;
                }

                return $procesos->contains(function ($proceso) use ($usuarioNormalizado) {
                    if (!$proceso->encargado) {
                        return false;
                    }
                    $encargadoNormalizado = strtolower(trim($proceso->encargado));
                    
                    // Buscar procesos asignados al usuario
                    return $encargadoNormalizado === $usuarioNormalizado;
                });
            });

        \Log::info('[ObtenerPrendasRecibosService] Cortador - Pedidos encontrados', [
            'usuario' => $usuario->name,
            'total_pedidos' => $pedidos->count(),
        ]);

        // Convertir pedidos a formato de prendas para compatibilidad con la vista
        $prendas = $pedidos->flatMap(function ($pedido) {
            if (!$pedido->prendas || $pedido->prendas->isEmpty()) {
                // Si no hay prendas, crear una entrada genérica
                return [[
                    'prenda_id' => 0,
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'nombre_prenda' => 'Pedido completo',
                    'descripcion' => $pedido->descripcion,
                    'de_bodega' => false,
                    'recibos' => [[
                        'id' => $pedido->id,
                        'tipo_recibo' => 'CORTE',
                        'consecutivo_actual' => $pedido->numero_pedido,
                        'consecutivo_inicial' => $pedido->numero_pedido,
                        'notas' => null,
                        'creado_en' => $pedido->created_at,
                    ]],
                    'total_recibos' => 1,
                    'fecha_creacion' => $pedido->created_at,
                ]];
            }

            return $pedido->prendas->map(function ($prenda) use ($pedido) {
                return [
                    'prenda_id' => $prenda->id,
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => $prenda->de_bodega ?? false,
                    'recibos' => [[
                        'id' => $pedido->id,
                        'tipo_recibo' => 'CORTE',
                        'consecutivo_actual' => $pedido->numero_pedido,
                        'consecutivo_inicial' => $pedido->numero_pedido,
                        'notas' => null,
                        'creado_en' => $pedido->created_at,
                    ]],
                    'total_recibos' => 1,
                    'fecha_creacion' => $prenda->created_at,
                ];
            });
        })->values();

        return $prendas;
    }

    /**
     * Obtener tipo de operario del usuario
     */
    private function obtenerTipoOperario(User $usuario): string
    {
        if ($usuario->hasRole('cortador')) {
            return 'cortador';
        }

        if ($usuario->hasRole('vista-costura')) {
            return 'vista-costura';
        }

        if ($usuario->hasRole('costurero')) {
            return 'costurero';
        }

        if ($usuario->hasRole('bodeguero')) {
            return 'bodeguero';
        }

        if ($usuario->hasRole('costura-reflectivo')) {
            return 'costura-reflectivo';
        }

        return 'desconocido';
    }
}
