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

        // Determinar tipos de recibo según el rol
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];
        if ($tipoOperario === 'costura-reflectivo') {
            // Para costura-reflectivo, SOLO mostrar COSTURA y REFLECTIVO (sin COSTURA-BODEGA)
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }

        // Obtener todos los recibos de costura activos con relaciones (incluyendo procesos)
        $query = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->with(['prenda', 'prenda.pedidoProduccion', 'prenda.procesosPrenda', 'pedido', 'pedido.prendas']);
        
        // Para cortadores: excluir PENDIENTE_INSUMOS (misma lógica que /recibos-costura)
        if ($tipoOperario === 'cortador') {
            $query->where('estado', '!=', 'PENDIENTE_INSUMOS');
        }
        
        $recibos = $query->orderBy('created_at', 'desc')->get();

        \Log::info(' [ObtenerPrendasRecibosService] Recibos encontrados', [
            'total_recibos' => $recibos->count(),
            'tipos_buscados' => $tiposRecibo,
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
                    // REFLECTIVO: Área debe ser "insumos" y estado PENDIENTE_INSUMOS
                    if (strtolower($pedido->area) !== 'insumos') {
                        \Log::info(' [Filtro REFLECTIVO] Área no es "insumos"', [
                            'prenda_id' => $prenda->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'area_actual' => $pedido->area
                        ]);
                        continue; // Skip REFLECTIVO si área no es insumos
                    }
                    
                    // Validar que el estado del pedido sea PENDIENTE_INSUMOS
                    if ($pedido->estado !== 'PENDIENTE_INSUMOS') {
                        \Log::info(' [Filtro REFLECTIVO] Estado del pedido no es PENDIENTE_INSUMOS', [
                            'prenda_id' => $prenda->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'estado_actual' => $pedido->estado
                        ]);
                        continue; // Skip REFLECTIVO si el pedido no está en PENDIENTE_INSUMOS
                    }
                    
                    // Validar que el detalle de proceso esté APROBADO
                    $detalleAprobado = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prenda->id)
                        ->where('estado', 'APROBADO')
                        ->first();
                    
                    if (!$detalleAprobado) {
                        \Log::info(' [Filtro REFLECTIVO] Detalle no está APROBADO', [
                            'prenda_id' => $prenda->id,
                            'numero_pedido' => $pedido->numero_pedido
                        ]);
                        continue; // Skip REFLECTIVO si no tiene detalle APROBADO
                    }
                    
                    \Log::info(' [REFLECTIVO VÁLIDO]', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_id' => $prenda->id,
                        'area' => $pedido->area,
                        'estado' => $pedido->estado,
                        'detalle_aprobado' => true
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
                        if ($tipoOperario === 'costurero') {
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
