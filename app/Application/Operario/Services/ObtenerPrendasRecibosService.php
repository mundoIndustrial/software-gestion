<?php

namespace App\Application\Operario\Services;

use App\Domain\Operario\Services\OperarioPrendasRecibosReadService;
use App\Models\User;
use App\Models\PrendaPedido;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosService
{
    public function __construct(private readonly OperarioPrendasRecibosReadService $service)
    {
    }

    public function obtenerPrendasConRecibos(User $usuario): Collection
    {
        
        $tipoOperario = $this->obtenerTipoOperario($usuario);

        \Log::info(' [obtenerPrendasConRecibos] TIPO OPERARIO DETECTADO', [
            'usuario' => $usuario->name,
            'usuario_id' => $usuario->id,
            'tipo_operario' => $tipoOperario,
            'es_costura_reflectivo' => $tipoOperario === 'costura-reflectivo' ? 'SI' : 'NO',
            'es_vista_costura' => $tipoOperario === 'vista-costura' ? 'SI' : 'NO'
        ]);

        // Determinar tipos de recibo según el rol
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];
        if ($tipoOperario === 'vista-costura') {
            // Para vista-costura: mostrar COSTURA y REFLECTIVO (sin COSTURA-BODEGA)
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }
        if ($tipoOperario === 'costura-reflectivo') {
            // Para costura-reflectivo: mostrar COSTURA y REFLECTIVO
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }

        // Obtener todos los recibos de costura activos con relaciones (incluyendo procesos)
        $query = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->whereIn('area', $tipoOperario === 'vista-costura'
                ? ['Costura', 'Control de Calidad', 'Control Calidad']
                : ['Corte', 'Costura', 'Control de Calidad', 'Control Calidad'])
            ->with(['prenda', 'prenda.pedidoProduccion', 'prenda.procesosPrenda', 'prenda.tallas', 'pedido', 'pedido.prendas', 'pedido.prendas.tallas']);
        
        // Para cortadores: excluir PENDIENTE_INSUMOS (misma lógica que /recibos-costura)
        // y permitir ver recibos en Costura solo si aún NO hay encargado en proceso Costura
        if ($tipoOperario === 'cortador') {
            $query->where('estado', '!=', 'PENDIENTE_INSUMOS');
        }

        // Restricciones por rol sobre áreas visibles
        if ($tipoOperario === 'cortador') {
            $query->whereIn('area', ['Corte', 'Costura']);
        }

        if ($tipoOperario === 'costurero') {
            $query->whereIn('area', ['Costura']);
        }

        if ($tipoOperario === 'confeccion-sobremedida') {
            $query->whereIn('area', ['Costura']);
        }
        
        $recibos = $query->orderBy('created_at', 'desc')->get();

        if ($tipoOperario === 'cortador') {
            $usuarioNombre = strtolower(trim($usuario->name));
            
            // Obtener TODAS las prendas donde el usuario es encargado de Corte
            $prendasDelCortador = ProcesoPrenda::whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                ->pluck('prenda_pedido_id')
                ->unique()
                ->values();

            $recibos = $recibos->filter(function ($recibo) use ($prendasDelCortador) {
                $area = strtolower(trim((string) ($recibo->area ?? '')));
                
                // Si está en área Costura, verificar que sea prenda asignada al cortador
                if ($area === 'costura') {
                    if (empty($recibo->prenda_id)) {
                        return false;
                    }
                    
                    //  CRÍTICO: Solo mostrar prendas donde el cortador es encargado de Corte
                    if (!$prendasDelCortador->contains($recibo->prenda_id)) {
                        return false;
                    }
                    
                    // Además, solo si aún no hay encargado de Costura asignado
                    $procesoCostura = ProcesoPrenda::where('prenda_pedido_id', $recibo->prenda_id)
                        ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                        ->whereNull('deleted_at')
                        ->first();

                    return !$procesoCostura || empty($procesoCostura->encargado);
                }
                
                // Área Corte: incluir siempre
                return true;
            })->values();
        }

        // Para costura-reflectivo y vista-costura: AGREGAR REFLECTIVO aprobados SIN validar encargado
        if ($tipoOperario === 'costura-reflectivo' || $tipoOperario === 'vista-costura') {
            \Log::info(' [REFLECTIVO APROBADOS] BUSCANDO prendas con PROCESO REFLECTIVO APROBADO en pedidos_procesos_prenda_detalles', [
                'usuario' => $usuario->name,
                'recibos_costura_actuales' => $recibos->count()
            ]);
            
            // Buscar PRENDAS con proceso REFLECTIVO (tipo_proceso_id = 1) en estado APROBADO
            // tipo_proceso_id = 1 es Reflectivo según tipos_procesos tabla
            $prendasReflectivoAprobadas = PedidosProcesosPrendaDetalle::where('tipo_proceso_id', 1)
                ->where('estado', 'APROBADO')
                ->with(['prenda', 'prenda.pedidoProduccion'])
                ->get()
                ->pluck('prenda')
                ->unique('id');
            
            \Log::info(' [REFLECTIVO APROBADOS] Prendas con PROCESO REFLECTIVO aprobado encontradas', [
                'total_prendas_reflectivo_aprobadas' => count($prendasReflectivoAprobadas)
            ]);
            
            // Para cada prenda con reflectivo aprobado, buscar si tiene recibo REFLECTIVO
            foreach ($prendasReflectivoAprobadas as $prendaAprobada) {
                if (!$prendaAprobada || !$prendaAprobada->pedidoProduccion) {
                    \Log::info(' [REFLECTIVO] Prenda o pedido sin relación', [
                        'prenda_id' => $prendaAprobada->id ?? 'null'
                    ]);
                    continue;
                }

                $reciboReflectivo = ConsecutivoReciboPedido::where('prenda_id', $prendaAprobada->id)
                    ->where('tipo_recibo', 'REFLECTIVO')
                    ->where('activo', 1)
                    ->first();

                // Si existe recibo REFLECTIVO con proceso aprobado, agregarlo SIN validar encargado ni área
                if ($reciboReflectivo) {
                    $recibos->push($reciboReflectivo);
                    
                    \Log::info(' [REFLECTIVO APROBADO AGREGADO]', [
                        'numero_pedido' => $prendaAprobada->pedidoProduccion->numero_pedido,
                        'prenda_id' => $prendaAprobada->id,
                        'recibo_id' => $reciboReflectivo->id,
                        'consecutivo' => $reciboReflectivo->consecutivo_actual,
                        'tiene_proceso_reflectivo_aprobado' => 'SI'
                    ]);
                } else {
                    \Log::info(' [REFLECTIVO PROCESO APROBADO PERO SIN RECIBO] Prenda con proceso reflectivo aprobado pero sin recibo REFLECTIVO', [
                        'numero_pedido' => $prendaAprobada->pedidoProduccion->numero_pedido,
                        'prenda_id' => $prendaAprobada->id
                    ]);
                }
            }
            
            // Re-ordenar por fecha y eliminar duplicados (misma prenda_id + tipo_recibo)
            $recibos = $recibos->sortByDesc('created_at')
                ->unique(function ($recibo) {
                    return $recibo->prenda_id . '_' . $recibo->tipo_recibo;
                })
                ->values();
        }

        // Deduplicar: Si hay múltiples recibos con misma prenda_id + tipo_recibo, quedar con el más reciente
        $recibos = $recibos->unique(function ($recibo) {
            return ($recibo->prenda_id ?: ('pedido_' . $recibo->pedido_produccion_id)) . '_' . $recibo->tipo_recibo;
        })->values();

        // vista-costura: ver recibos COSTURA en área Costura, pero REFLECTIVO en cualquier área
        if ($tipoOperario === 'vista-costura') {
            $recibos = $recibos
                ->filter(function ($recibo) {
                    $area = strtolower(trim((string) ($recibo->area ?? '')));
                    $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));

                    if ($tipoRecibo === 'COSTURA' || $tipoRecibo === 'COSTURA-BODEGA') {
                        $estadoParcialesCc = $this->obtenerEstadoParcialesControlCalidad(
                            (int) $recibo->pedido_produccion_id,
                            (int) $recibo->prenda_id,
                            $tipoRecibo,
                            $this->obtenerConsecutivoOriginalDesdeRecibo($recibo)
                        );

                        if ($estadoParcialesCc['tiene_parciales']) {
                            return !$estadoParcialesCc['todos_parciales_en_cc'];
                        }

                        return $area === 'costura';
                    }

                    if ($tipoRecibo === 'REFLECTIVO') {
                        return true;
                    }

                    return false;
                })
                ->values();
        }

        // costura-reflectivo: ver COSTURA solo si área es Costura, REFLECTIVO sin importar área (pero no si pasó a Control Calidad)
        if ($tipoOperario === 'costura-reflectivo') {
            $recibos = $recibos
                ->filter(function ($recibo) {
                    $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));
                    $area = strtolower(trim((string) ($recibo->area ?? '')));
                    
                    // COSTURA: solo si área es Costura
                    if ($tipoRecibo === 'COSTURA' || $tipoRecibo === 'COSTURA-BODEGA') {
                        return $area === 'costura';
                    }
                    
                    // REFLECTIVO: mostrar sin importar el área, PERO no mostrar si ya pasó a Control de Calidad
                    if ($tipoRecibo === 'REFLECTIVO') {
                        // Verificar si el área es Control de Calidad (ya pasó)
                        if (in_array($area, ['control calidad', 'control de calidad'])) {
                            \Log::info(' [Filtro COSTURA-REFLECTIVO] REFLECTIVO excluido - ya pasó a Control de Calidad', [
                                'recibo_id' => $recibo->id,
                                'area' => $recibo->area
                            ]);
                            return false;
                        }
                        return true;
                    }
                    
                    return false;
                })
                ->values();
        }

        \Log::info(' [ObtenerPrendasRecibosService] Recibos encontrados', [
            'total_recibos' => $recibos->count(),
            'tipos_buscados' => $tiposRecibo,
            'areas_permitidas' => ['Corte', 'Costura', 'Control de Calidad', 'Control Calidad'],
            'prenda_ids' => $recibos->pluck('prenda_id')->toArray(),
            'tipo_operario' => $tipoOperario,
            'incluye_reflectivos_aprobados' => ($tipoOperario === 'costura-reflectivo' || $tipoOperario === 'vista-costura') ? 'SI' : 'NO'
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
                        // Para cortadores: verificar que exista proceso "Corte" con encargado = usuario ESPECÍFICAMENTE PARA ESTA PRENDA
                        $usuarioNombre = strtolower(trim($usuario->name));
                        $tieneProcesoCorte = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                            ->where('prenda_pedido_id', $prenda->id)  //  CRÍTICO: Filtrar por PRENDA ESPECÍFICA
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                            ->whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
                            ->exists();
                        
                        if (!$tieneProcesoCorte) {
                            \Log::info(' [Filtro CORTADOR] No tiene proceso Corte asignado a ESTA PRENDA', [
                                'prenda_id' => $prenda->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario' => $usuario->name,
                                'filtro_por_prenda' => 'SÍ'
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
                        } else if ($tipoOperario === 'costurero' || $tipoOperario === 'confeccion-sobremedida') {
                            $usuarioNombre = strtolower(trim($usuario->name));
                            
                            // Buscar proceso Costura específicamente para esta prenda
                            $procesoCosturaDelaPrenda = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
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
                                'pedido_id' => $pedido->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario' => $usuario->name
                            ]);
                        } else if ($tipoOperario === 'costura-reflectivo') {
                            // Para costura-reflectivo: el encargado debe ser el usuario logueado
                            // Para lider-reflectivo: el encargado debe tener rol costura-reflectivo
                            $usuarioNombre = strtolower(trim($usuario->name));
                            $usuarioLiderReflectivo = $usuario->hasRole('lider-reflectivo');
                            
                            // Buscar encargado en el proceso Costura
                            $procesoCosturaDelaPrenda = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                                ->where('prenda_pedido_id', $prenda->id)
                                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                                ->first();
                                
                            $encargadoAsignado = $procesoCosturaDelaPrenda ? strtolower(trim((string)$procesoCosturaDelaPrenda->encargado)) : null;
                            
                            if (!$encargadoAsignado) {
                                \Log::info(' [Filtro COSTURA-REFLECTIVO] Sin encargado asignado', [
                                    'prenda_id' => $prenda->id,
                                    'numero_pedido' => $pedido->numero_pedido,
                                ]);
                                continue;
                            }
                            
                            if ($usuarioLiderReflectivo) {
                                // lider-reflectivo: verificar que el encargado tenga rol costura-reflectivo
                                $encargadoUsuario = User::whereRaw('LOWER(TRIM(name)) = ?', [$encargadoAsignado])->first();
                                if (!$encargadoUsuario || !$encargadoUsuario->hasRole('costura-reflectivo')) {
                                    \Log::info(' [Filtro LIDER-REFLECTIVO] Encargado no tiene rol costura-reflectivo', [
                                        'prenda_id' => $prenda->id,
                                        'numero_pedido' => $pedido->numero_pedido,
                                        'encargado' => $encargadoAsignado,
                                        'tiene_rol_costura_reflectivo' => $encargadoUsuario ? ($encargadoUsuario->hasRole('costura-reflectivo') ? 'SI' : 'NO') : 'USUARIO_NO_ENCONTRADO'
                                    ]);
                                    continue;
                                }
                            } else {
                                // costura-reflectivo: solo ve los que le fueron asignados a él
                                if ($encargadoAsignado !== $usuarioNombre) {
                                    \Log::info(' [Filtro COSTURA-REFLECTIVO] Usuario no es el encargado asignado', [
                                        'prenda_id' => $prenda->id,
                                        'numero_pedido' => $pedido->numero_pedido,
                                        'usuario_actual' => $usuario->name,
                                        'encargado_asignado' => $encargadoAsignado
                                    ]);
                                    continue;
                                }
                            }
                            
                            \Log::info(' [Filtro COSTURA-REFLECTIVO/LIDER-REFLECTIVO] Recibo de COSTURA visible', [
                                'prenda_id' => $prenda->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario_actual' => $usuario->name,
                                'encargado_asignado' => $encargadoAsignado,
                                'es_lider_reflectivo' => $usuarioLiderReflectivo ? 'SI' : 'NO'
                            ]);
                        }
                    }
                }

                if (in_array($tipoOperario, ['costurero', 'confeccion-sobremedida'], true)
                    && in_array(strtoupper((string) $tipoRecibo), ['COSTURA', 'COSTURA-BODEGA'], true)) {
                    $usuarioNombre = strtolower(trim($usuario->name));

                    $recibosDelTipo = $recibosDelTipo->filter(function ($recibo) use ($prenda, $pedido, $usuarioNombre) {
                        $procesoCostura = ProcesoPrenda::query()
                            ->where('numero_pedido', $pedido->numero_pedido)
                            ->where('prenda_pedido_id', $prenda->id)
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                            ->where('numero_recibo', $recibo->consecutivo_actual)
                            ->where(function ($query) {
                                $query->whereNull('numero_recibo_parcial')
                                    ->orWhere('numero_recibo_parcial', 0);
                            })
                            ->whereNull('deleted_at')
                            ->latest('created_at')
                            ->first();

                        if (!$procesoCostura || empty($procesoCostura->encargado)) {
                            return false;
                        }

                        return strtolower(trim((string) $procesoCostura->encargado)) === $usuarioNombre;
                    })->values();

                    if ($recibosDelTipo->isEmpty()) {
                        continue;
                    }
                }

                if ($tipoOperario === 'costura-reflectivo'
                    && in_array(strtoupper((string) $tipoRecibo), ['COSTURA', 'COSTURA-BODEGA'], true)) {
                    $usuarioNombre = strtolower(trim($usuario->name));
                    $usuarioLiderReflectivo = $usuario->hasRole('lider-reflectivo');

                    $recibosDelTipo = $recibosDelTipo->filter(function ($recibo) use ($prenda, $pedido, $usuarioNombre, $usuarioLiderReflectivo) {
                        $procesoCostura = ProcesoPrenda::query()
                            ->where('numero_pedido', $pedido->numero_pedido)
                            ->where('prenda_pedido_id', $prenda->id)
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                            ->where('numero_recibo', $recibo->consecutivo_actual)
                            ->where(function ($query) {
                                $query->whereNull('numero_recibo_parcial')
                                    ->orWhere('numero_recibo_parcial', 0);
                            })
                            ->whereNull('deleted_at')
                            ->latest('created_at')
                            ->first();

                        if (!$procesoCostura || empty($procesoCostura->encargado)) {
                            return false;
                        }

                        $encargadoNormalizado = strtolower(trim((string) $procesoCostura->encargado));

                        if ($usuarioLiderReflectivo) {
                            $encargadoUsuario = \App\Models\User::query()
                                ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
                                ->first();

                            return $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo');
                        }

                        return $encargadoNormalizado === $usuarioNombre;
                    })->values();

                    if ($recibosDelTipo->isEmpty()) {
                        continue;
                    }
                }

                if (($usuario?->hasRole('lider-reflectivo') ?? false) && strtoupper((string) $tipoRecibo) === 'REFLECTIVO') {
                    $recibosDelTipo = $recibosDelTipo->filter(function ($recibo) {
                        $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                        $esParcial = $notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas) === 1;

                        if ($esParcial) {
                            return true;
                        }

                        $tieneParciales = ReciboPorPartes::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                            ->where('prenda_pedido_id', $recibo->prenda_id)
                            ->where('tipo_recibo', 'REFLECTIVO')
                            ->where('consecutivo_original', $recibo->consecutivo_actual)
                            ->exists();

                        return !$tieneParciales;
                    })->values();

                    if ($recibosDelTipo->isEmpty()) {
                        continue;
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
                    'tallas' => $prenda->tallas ? $prenda->tallas->map(function ($talla) {
                        return [
                            'id' => $talla->id,
                            'genero' => $talla->genero,
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                            'tipo_talla' => $talla->tipo_talla,
                            'es_sobremedida' => $talla->es_sobremedida,
                            'tela' => $talla->tela,
                            'colores' => $talla->colores,
                        ];
                    })->toArray() : [],
                    'recibos' => $recibosDelTipo->map(function ($recibo) use ($pedido) {
                        // Buscar el proceso de Control Calidad más reciente para este recibo
                        $procesoCC = ProcesoPrenda::where('prenda_pedido_id', $recibo->prenda_id)
                            ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                            ->whereNull('deleted_at')
                            ->latest('created_at')
                            ->first();

                        $procesoCostura = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                            ->where('prenda_pedido_id', $recibo->prenda_id)
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                            ->where('numero_recibo', $recibo->consecutivo_actual)
                            ->where(function ($query) {
                                $query->whereNull('numero_recibo_parcial')
                                      ->orWhere('numero_recibo_parcial', 0);
                            })
                            ->whereNull('deleted_at')
                            ->latest('created_at')
                            ->first();

                        $procesoCorte = ProcesoPrenda::where('prenda_pedido_id', $recibo->prenda_id)
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                            ->whereNull('deleted_at')
                            ->latest('created_at')
                            ->first();

                        // Consultar si el recibo está completado por área
                        $completadoCorte = \DB::table('prenda_recibo_completado')
                            ->where('id_recibo', $recibo->id)
                            ->where('area', 'corte')
                            ->exists();
                        
                        $completadoCostura = \DB::table('prenda_recibo_completado')
                            ->where('id_recibo', $recibo->id)
                            ->where('area', 'costura')
                            ->exists();
                        
                        $completadoControlCalidad = \DB::table('prenda_recibo_completado')
                            ->where('id_recibo', $recibo->id)
                            ->where(function($query) {
                                $query->where('area', 'control calidad')
                                      ->orWhere('area', 'control de calidad');
                            })
                            ->exists();

                        $parcialId = null;
                        $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                        if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                            $parcialId = (int) $matches[1];
                        }
                        $esParcial = $parcialId !== null;

                        $creadoEn = $recibo->created_at;
                        if ($esParcial) {
                            try {
                                $parcialCreatedAt = \DB::table('pedidos_parciales')
                                    ->where('id', $parcialId)
                                    ->whereNull('deleted_at')
                                    ->value('created_at');
                                if (!empty($parcialCreatedAt)) {
                                    $creadoEn = $parcialCreatedAt;
                                }
                            } catch (\Exception $e) {
                                // Mantener created_at del recibo si falla
                            }
                        }
                        
                        return [
                            'id' => $recibo->id,
                            'tipo_recibo' => $recibo->tipo_recibo,
                            'consecutivo_actual' => $recibo->consecutivo_actual,
                            'consecutivo_inicial' => $recibo->consecutivo_inicial,
                            'notas' => $recibo->notas,
                            'creado_en' => $creadoEn,
                            'area' => $recibo->area,
                            'proceso_id' => $procesoCC ? $procesoCC->id : null,
                            'proceso_id_costura' => $procesoCostura ? $procesoCostura->id : null,
                            'encargado_costura' => $procesoCostura ? $procesoCostura->encargado : null,
                            'encargado_corte' => $procesoCorte ? $procesoCorte->encargado : null,
                            'completado_corte' => $completadoCorte,
                            'completado_costura' => $completadoCostura,
                            'completado_control_calidad' => $completadoControlCalidad,
                            'es_parcial' => $esParcial,
                            'pedido_parcial_id' => $parcialId,
                            'tiene_parciales' => \App\Models\ReciboPorPartes::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                                ->where('tipo_recibo', $recibo->tipo_recibo)
                                ->where('consecutivo_original', $recibo->consecutivo_actual)
                                ->exists(),
                        ];
                    })->toArray(),
                    'total_recibos' => $recibosDelTipo->count(),
                    'fecha_creacion' => $prenda->created_at,
                ];
            }
            
            return $resultados;
        })->values();

        if ($tipoOperario === 'vista-costura') {
            return $prendasAgrupadas
                ->sortByDesc(function ($item) {
                    return $item['fecha_creacion'] ?? null;
                })
                ->values();
        }

        return $prendasAgrupadas
            ->concat($this->obtenerPrendasParcialesCostura($usuario, false))
            ->sortByDesc(function ($item) {
                return $item['fecha_creacion'] ?? null;
            })
            ->values();
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
        $pedidos = PedidoProduccion::with(['prendas', 'prendas.tallas'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($pedido) use ($usuarioNormalizado) {
                $procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)->get();
                
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
                    'tallas' => [],
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
                    'tallas' => $prenda->tallas ? $prenda->tallas->map(function ($talla) {
                        return [
                            'id' => $talla->id,
                            'genero' => $talla->genero,
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                            'tipo_talla' => $talla->tipo_talla,
                            'es_sobremedida' => $talla->es_sobremedida,
                            'tela' => $talla->tela,
                            'colores' => $talla->colores,
                        ];
                    })->toArray() : [],
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

    private function obtenerPrendasParcialesCostura(?User $usuario, bool $modoTodosCostura): Collection
    {
        $tipoOperario = $usuario ? $this->obtenerTipoOperario($usuario) : 'administrador-costura';
        $encargadoNormalizado = strtolower(trim((string) ($usuario?->name ?? '')));
        $esLiderReflectivo = (bool) ($usuario?->hasRole('lider-reflectivo'));

        $tiposParcial = ['COSTURA', 'COSTURA-BODEGA'];
        if ($tipoOperario === 'costura-reflectivo') {
            $tiposParcial[] = 'REFLECTIVO';
        }

        $parciales = ReciboPorPartes::query()
            ->with(['pedido', 'prenda.tallas', 'tallas'])
            ->whereIn('tipo_recibo', $tiposParcial)
            ->orderByDesc('created_at')
            ->get();

        return $parciales->map(function (ReciboPorPartes $parcial) {
            $pedido = $parcial->pedido;
            $prenda = $parcial->prenda;

            if (!$pedido || !$prenda) {
                return null;
            }

            $proceso = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                ->whereNull('deleted_at')
                ->latest('created_at')
                ->first();

            return [
                'parcial' => $parcial,
                'pedido' => $pedido,
                'prenda' => $prenda,
                'proceso' => $proceso,
                'encargado_normalizado' => strtolower(trim((string) ($proceso->encargado ?? $parcial->encargado ?? ''))),
            ];
        })
            ->filter()
            ->filter(function (array $item) use ($modoTodosCostura, $tipoOperario, $encargadoNormalizado, $esLiderReflectivo) {
                $encargado = $item['encargado_normalizado'];
                if ($encargado === '') {
                    return false;
                }

                if ($modoTodosCostura || $tipoOperario === 'vista-costura') {
                    $encargadoUsuario = \App\Models\User::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [$encargado])
                        ->first();

                    if ($encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo')) {
                        return false;
                    }

                    return true;
                }

                if (in_array($tipoOperario, ['costurero', 'confeccion-sobremedida'], true)) {
                    return $encargado === $encargadoNormalizado;
                }

                if ($tipoOperario === 'costura-reflectivo') {
                    if ($esLiderReflectivo) {
                        $encargadoUsuario = \App\Models\User::query()
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$encargado])
                            ->first();

                        return $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo');
                    }

                    return $encargado === $encargadoNormalizado;
                }

                return false;
            })
            ->map(function (array $item) {
                /** @var ReciboPorPartes $parcial */
                $parcial = $item['parcial'];
                $pedido = $item['pedido'];
                /** @var PrendaPedido $prenda */
                $prenda = $item['prenda'];
                /** @var ProcesoPrenda|null $proceso */
                $proceso = $item['proceso'];

                $consecutivoParcial = $this->formatearConsecutivoParcial($parcial->consecutivo_parcial);
                $completadoCostura = \DB::table('prenda_recibo_completado')
                    ->where('area', 'Costura')
                    ->where('id_parcial', $parcial->id)
                    ->exists();

                return [
                    'prenda_id' => $prenda->id,
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => $prenda->de_bodega ?? false,
                    'tallas' => $parcial->tallas->map(function ($talla) {
                        return [
                            'id' => $talla->id,
                            'genero' => $talla->genero ?? null,
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                            'tipo_talla' => null,
                            'es_sobremedida' => false,
                            'tela' => null,
                            'colores' => $talla->color_nombre ? [$talla->color_nombre] : [],
                        ];
                    })->toArray(),
                    'recibos' => [[
                        'id' => null,
                        'tipo_recibo' => (string) ($parcial->tipo_recibo ?: 'PARCIAL'),
                        'consecutivo_actual' => $consecutivoParcial,
                        'consecutivo_inicial' => $this->formatearConsecutivoParcial($parcial->consecutivo_original),
                        'consecutivo_parcial' => $consecutivoParcial,
                        'notas' => 'parcial_id:' . $parcial->id,
                        'creado_en' => $parcial->created_at,
                        'area' => 'Costura',
                        'proceso_id' => $proceso?->id,
                        'proceso_id_costura' => $proceso?->id,
                        'encargado_costura' => $proceso?->encargado ?? $parcial->encargado,
                        'encargado_corte' => null,
                        'encargado_control_calidad' => null,
                        'completado_area' => $completadoCostura,
                        'completado_corte' => false,
                        'completado_costura' => $completadoCostura,
                        'completado_control_calidad' => false,
                        'es_parcial' => true,
                        'pedido_parcial_id' => $parcial->id,
                        'tiene_parciales' => false,
                    ]],
                    'total_recibos' => 1,
                    'fecha_creacion' => $parcial->created_at,
                ];
            })
            ->values();
    }

    private function formatearConsecutivoParcial($valor): string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return '';
        }

        if (!str_contains($texto, '.')) {
            return $texto;
        }

        return rtrim(rtrim($texto, '0'), '.');
    }

    private function obtenerConsecutivoOriginalDesdeRecibo($recibo): string
    {
        $notas = trim((string) ($recibo->notas ?? ''));

        if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches) === 1) {
            $parcialId = (int) $matches[1];
            $consecutivoOriginal = ReciboPorPartes::query()
                ->where('id', $parcialId)
                ->value('consecutivo_original');

            if ($consecutivoOriginal !== null && $consecutivoOriginal !== '') {
                return (string) $consecutivoOriginal;
            }
        }

        return (string) ($recibo->consecutivo_inicial ?: $recibo->consecutivo_actual ?: '');
    }

    private function obtenerEstadoParcialesControlCalidad(
        int $pedidoProduccionId,
        int $prendaId,
        string $tipoRecibo,
        string $consecutivoOriginal
    ): array {
        if ($pedidoProduccionId <= 0 || $prendaId <= 0 || $consecutivoOriginal === '') {
            return [
                'tiene_parciales' => false,
                'total_parciales' => 0,
                'parciales_en_cc' => 0,
                'todos_parciales_en_cc' => false,
            ];
        }

        $parciales = ReciboPorPartes::query()
            ->where('pedido_produccion_id', $pedidoProduccionId)
            ->where('prenda_pedido_id', $prendaId)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoRecibo))])
            ->where('consecutivo_original', $consecutivoOriginal)
            ->get(['consecutivo_parcial']);

        $totalParciales = $parciales->count();
        if ($totalParciales === 0) {
            return [
                'tiene_parciales' => false,
                'total_parciales' => 0,
                'parciales_en_cc' => 0,
                'todos_parciales_en_cc' => false,
            ];
        }

        $numeroPedido = PedidoProduccion::query()
            ->where('id', $pedidoProduccionId)
            ->value('numero_pedido');

        if (!$numeroPedido) {
            return [
                'tiene_parciales' => true,
                'total_parciales' => $totalParciales,
                'parciales_en_cc' => 0,
                'todos_parciales_en_cc' => false,
            ];
        }

        $parcialesEnCc = ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $prendaId)
            ->whereIn('numero_recibo_parcial', $parciales->pluck('consecutivo_parcial')->all())
            ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->whereNull('deleted_at')
            ->distinct('numero_recibo_parcial')
            ->count('numero_recibo_parcial');

        return [
            'tiene_parciales' => true,
            'total_parciales' => $totalParciales,
            'parciales_en_cc' => $parcialesEnCc,
            'todos_parciales_en_cc' => $parcialesEnCc >= $totalParciales,
        ];
    }

    private function buscarProcesoCosturaOriginal(Collection $procesos, $numeroRecibo): ?ProcesoPrenda
    {
        return $procesos
            ->filter(function ($proceso) use ($numeroRecibo) {
                if (!is_string($proceso->proceso ?? null) || strtolower(trim((string) $proceso->proceso)) !== 'costura') {
                    return false;
                }

                if ((string) ($proceso->numero_recibo ?? '') !== (string) $numeroRecibo) {
                    return false;
                }

                $numeroReciboParcial = $proceso->numero_recibo_parcial ?? null;
                return $numeroReciboParcial === null || (float) $numeroReciboParcial === 0.0;
            })
            ->sortByDesc(fn($proceso) => $proceso->created_at)
            ->first();
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

        if ($usuario->hasRole('confeccion-sobremedida')) {
            return 'confeccion-sobremedida';
        }

        if ($usuario->hasRole('bodeguero')) {
            return 'bodeguero';
        }

        if ($usuario->hasRole('costura-reflectivo')) {
            return 'costura-reflectivo';
        }

        if ($usuario->hasRole('lider-reflectivo')) {
            return 'costura-reflectivo'; // Mismo comportamiento que costura-reflectivo
        }

        return 'desconocido';
    }
}
