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
        $recibos = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->with(['prenda', 'prenda.pedidoProduccion', 'prenda.procesosPrenda', 'pedido', 'pedido.prendas'])
            ->orderBy('created_at', 'desc')
            ->get();

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
        })->flatMap(function ($recibosDelaPrenda) use ($tipoOperario) {
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
                    // COSTURA: Área debe ser "costura" y estado "En Ejecución"
                    if (strtolower($pedido->area) !== 'costura') {
                        \Log::info(' [Filtro COSTURA] Área no es "costura"', [
                            'prenda_id' => $prenda->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'area_actual' => $pedido->area,
                            'tipo_recibo' => $tipoRecibo
                        ]);
                        continue; // Skip COSTURA si área no es costura
                    }
                    
                    if ($pedido->estado !== 'En Ejecución') {
                        \Log::info(' [Filtro COSTURA] Estado del pedido no es "En Ejecución"', [
                            'prenda_id' => $prenda->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'estado_actual' => $pedido->estado,
                            'tipo_recibo' => $tipoRecibo
                        ]);
                        continue; // Skip COSTURA si no está en Ejecución
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
