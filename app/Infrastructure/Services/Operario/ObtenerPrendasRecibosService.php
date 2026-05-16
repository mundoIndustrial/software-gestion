<?php

namespace App\Infrastructure\Services\Operario;

use App\Domain\Operario\Services\OperarioPrendasRecibosReadService;
use App\Models\User;
use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosService implements OperarioPrendasRecibosReadService
{

    public function obtenerPrendasConRecibosTodosCostura(): \Illuminate\Support\Collection
    {
        // Vista global: listar todos los recibos COSTURA activos (incluye modulos 1/2/3)
        // Filtra por:
        // - Area: "Costura" y "Corte"
        // - Encargado: Debe estar asignado (no vaci­o/null)
        // - EXCLUYE: Recibos asignados a usuarios con rol costura-reflectivo
        $usuarioFake = new User();
        $usuarioFake->name = 'VISTA GLOBAL';

        // Reutilizar la logica marcando el rol como vista-costura a nivel de tipos.
        // Aqui no usamos hasRole; simplemente construimos el query base para COSTURA.
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];

        // Obtener usuarios con rol costura-reflectivo para excluirlos
        $rolCosturaReflectivoId = \App\Models\Role::where('name', 'costura-reflectivo')->value('id');
        $usuariosCosturaReflectivo = collect();

        if ($rolCosturaReflectivoId) {
            $usuariosCosturaReflectivo = \App\Models\User::query()
                ->where(function ($q) use ($rolCosturaReflectivoId) {
                    $q->whereJsonContains('roles_ids', (int) $rolCosturaReflectivoId)
                        ->orWhere('role_id', (int) $rolCosturaReflectivoId);
                })
                ->pluck('name')
                ->map(fn($n) => strtolower(trim((string) $n)))
                ->filter()
                ->unique()
                ->values();
        }

        $recibos = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->whereIn('area', ['Costura', 'Corte'])
            ->with(['prenda', 'prenda.pedidoProduccion', 'prenda.procesosPrenda', 'prenda.tallas', 'pedido', 'pedido.prendas', 'pedido.prendas.tallas'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Deduplicar: Si hay multiples recibos con misma prenda_id + tipo_recibo, quedar con el mas reciente
        // Se anade el ID de parcial si existe para permitir multiples parciales de una misma prenda
        $recibos = $recibos->unique(function ($recibo) {
            $notas = (string) ($recibo->notas ?? '');
            $parcialSuffix = '';
            if (preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                $parcialSuffix = '_p' . $matches[1];
            }
            return ($recibo->prenda_id ?: ('pedido_' . $recibo->pedido_produccion_id)) . '_' . $recibo->tipo_recibo . $parcialSuffix;
        })->values();

        // Agrupar por prenda (o por pedido si es sin prenda_id)
        $prendasAgrupadas = $recibos->groupBy(function ($recibo) {
            if ($recibo->prenda_id) {
                return 'prenda_' . $recibo->prenda_id;
            }
            return 'pedido_' . $recibo->pedido_produccion_id;
        })->flatMap(function ($recibosDelaPrenda) use ($usuariosCosturaReflectivo) {
            $primeRecibo = $recibosDelaPrenda->first();

            if ($primeRecibo->prenda_id && $primeRecibo->prenda) {
                $prenda = $primeRecibo->prenda;
                $pedido = $prenda->pedidoProduccion;
            } else if ($primeRecibo->pedido_produccion_id && $primeRecibo->pedido) {
                $pedido = $primeRecibo->pedido;
                $prenda = $pedido->prendas->first();
            } else {
                return [];
            }

            if (!$prenda || !$pedido) {
                return [];
            }

            $recibosPorTipo = $recibosDelaPrenda->groupBy('tipo_recibo');
            $resultados = [];

            foreach ($recibosPorTipo as $tipoRecibo => $recibosDelTipo) {
                // Solo COSTURA/COSTURA-BODEGA aqui­
                if (!in_array(strtoupper((string) $tipoRecibo), ['COSTURA', 'COSTURA-BODEGA'], true)) {
                    continue;
                }

                // Filtrar recibos que tengan encargado asignado segun el Area
                // Y EXCLUIR los asignados a usuarios con rol costura-reflectivo
                $recibosConEncargado = $recibosDelTipo->filter(function ($recibo) use ($usuariosCosturaReflectivo) {
                    $procesos = $recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda')
                        ? $recibo->prenda->procesosPrenda
                        : collect();

                    $areaRecibo = strtolower(trim((string) ($recibo->area ?? '')));
                    $numeroRecibo = $recibo->consecutivo_actual;

                    // Buscar el proceso segun el Area
                    if ($areaRecibo === 'costura') {
                        $proceso = $this->buscarProcesoCosturaOriginal($procesos, $numeroRecibo);
                    } elseif ($areaRecibo === 'corte') {
                        $proceso = $procesos
                            ->filter(fn($p) => is_string($p->proceso ?? null) && strtolower(trim((string) $p->proceso)) === 'corte')
                            ->sortByDesc(fn($p) => $p->created_at)
                            ->first();
                    } else {
                        return false; // Area no valida
                    }

                    // Solo incluir si tiene encargado asignado
                    if (!$proceso || empty($proceso->encargado)) {
                        return false;
                    }

                    // EXCLUIR si el encargado tiene rol costura-reflectivo
                    $encargadoNormalizado = strtolower(trim((string) $proceso->encargado));
                    if ($usuariosCosturaReflectivo->contains($encargadoNormalizado)) {
                        \Log::info(' [administrador-costura] Excluyendo recibo asignado a costura-reflectivo', [
                            'recibo_id' => $recibo->id,
                            'area' => $areaRecibo,
                            'encargado' => $proceso->encargado,
                            'prenda_id' => $recibo->prenda_id
                        ]);
                        return false;
                    }

                    return true;
                })->values();

                // Si no hay recibos con encargado, saltar esta prenda
                if ($recibosConEncargado->count() === 0) {
                    continue;
                }

                $recibosConEncargado = $recibosConEncargado
                    ->sortBy(function ($recibo) {
                        $fechaCorte = $this->obtenerFechaLlegadaACorte($recibo);
                        return $this->normalizarFechaAOrdenable($fechaCorte ?? $recibo->created_at);
                    })
                    ->values();
                $fechaOrdenPrincipal = optional($recibosConEncargado->first())?->created_at;
                if ($recibosConEncargado->isNotEmpty()) {
                    $fechaCortePrincipal = $this->obtenerFechaLlegadaACorte($recibosConEncargado->first());
                    if (!empty($fechaCortePrincipal)) {
                        $fechaOrdenPrincipal = $fechaCortePrincipal;
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
                    'recibos' => (function () use ($recibosConEncargado) {
                        $reciboIds = $recibosConEncargado->pluck('id')->filter()->values()->all();

                        $completadosRows = empty($reciboIds)
                            ? collect()
                            : \DB::table('prenda_recibo_completado')
                                ->whereIn('id_recibo', $reciboIds)
                                ->select(['id_recibo', 'area'])
                                ->get();

                        $completadosCorte = $completadosRows
                            ->filter(fn($r) => strtolower(trim((string) ($r->area ?? ''))) === 'corte')
                            ->pluck('id_recibo')
                            ->map(fn($id) => (int) $id)
                            ->flip();

                        $completadosCostura = $completadosRows
                            ->filter(fn($r) => strtolower(trim((string) ($r->area ?? ''))) === 'costura')
                            ->pluck('id_recibo')
                            ->map(fn($id) => (int) $id)
                            ->flip();

                        $completadosControlCalidad = $completadosRows
                            ->filter(function ($r) {
                                $a = strtolower(trim((string) ($r->area ?? '')));
                                return $a === 'control calidad' || $a === 'control de calidad';
                            })
                            ->pluck('id_recibo')
                            ->map(fn($id) => (int) $id)
                            ->flip();

                        return $recibosConEncargado
                            ->filter(function ($recibo) {
                                // Si el usuario es vista-costura, solo mostramos el padre en la lista de recibos
                                // de la card para evitar duplicidad de acciones.
                                if (auth()->check() && auth()->user()->hasRole('vista-costura')) {
                                    $notas = (string) ($recibo->notas ?? '');
                                    return !preg_match('/parcial_id:(\d+)/i', $notas);
                                }
                                return true;
                            })
                            ->map(function ($recibo) use ($completadosCorte, $completadosCostura, $completadosControlCalidad) {
                        $procesos = $recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda')
                            ? $recibo->prenda->procesosPrenda
                            : collect();

                        $numeroRecibo = $recibo->consecutivo_actual;
                        $procesoCostura = $this->buscarProcesoCosturaOriginal($procesos, $numeroRecibo);

                        $procesoCorte = $procesos
                            ->filter(fn($p) => is_string($p->proceso ?? null) && strtolower(trim((string) $p->proceso)) === 'corte')
                            ->sortByDesc(fn($p) => $p->created_at)
                            ->first();

                        $procesoControlCalidad = $procesos
                            ->filter(function ($p) {
                                $proc = strtolower(trim((string) ($p->proceso ?? '')));
                                return in_array($proc, ['control de calidad', 'control calidad'], true);
                            })
                            ->sortByDesc(fn($p) => $p->created_at)
                            ->first();

                        $rid = (int) $recibo->id;
                        $completadoCorte = $completadosCorte->has($rid);
                        $completadoCostura = $completadosCostura->has($rid);
                        $completadoControlCalidad = $completadosControlCalidad->has($rid);

                        // Verificar si hay parciales para este recibo
                        $tieneParciales = \App\Models\ReciboPorPartes::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                            ->where('tipo_recibo', $recibo->tipo_recibo)
                            ->where('consecutivo_original', $recibo->consecutivo_actual)
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
                            'fecha_inicio_proceso' => $procesoCostura?->fecha_inicio
                                ?? $procesoCorte?->fecha_inicio
                                ?? $procesoControlCalidad?->fecha_inicio
                                ?? null,
                            'fecha_asignacion_costura' => $procesoCostura?->fecha_de_asignacion_encargado ?? null,
                            'fecha_proceso_costura_created_at' => $procesoCostura?->created_at ?? null,
                            'fecha_asignacion_corte' => $procesoCorte?->fecha_de_asignacion_encargado ?? null,
                            'fecha_proceso_corte_created_at' => $procesoCorte?->created_at ?? null,
                            'fecha_proceso_created_at' => $procesoCostura?->created_at
                                ?? $procesoCorte?->created_at
                                ?? $procesoControlCalidad?->created_at
                                ?? null,
                            'fecha_asignacion_proceso' => $procesoCostura?->fecha_de_asignacion_encargado
                                ?? $procesoCorte?->fecha_de_asignacion_encargado
                                ?? $procesoControlCalidad?->fecha_de_asignacion_encargado
                                ?? null,
                            'area' => $recibo->area,
                            'proceso_id_costura' => $procesoCostura ? $procesoCostura->id : null,
                            'encargado_costura' => $procesoCostura ? $procesoCostura->encargado : null,
                            'encargado_corte' => $procesoCorte ? $procesoCorte->encargado : null,
                            'encargado_control_calidad' => $procesoControlCalidad ? $procesoControlCalidad->encargado : null,
                            'completado_corte' => $completadoCorte,
                            'completado_costura' => $completadoCostura,
                            'completado_control_calidad' => $completadoControlCalidad,
                            'es_parcial' => $esParcial,
                            'pedido_parcial_id' => $parcialId,
                            'tiene_parciales' => $tieneParciales,
                        ];
                    })->toArray();
                    })(),
                    'total_recibos' => $recibosConEncargado->count(),
                    'fecha_creacion' => $fechaOrdenPrincipal ?? $prenda->created_at,
                ];
            }

            return $resultados;
        })->values();

        $resultadoFinal = $prendasAgrupadas;

        // Para vista-costura no agregamos los parciales como tarjetas independientes
        if (!(auth()->check() && auth()->user()->hasRole('vista-costura'))) {
            $resultadoFinal = $resultadoFinal->concat($this->obtenerPrendasParcialesCostura(null, true));
        }

        return $resultadoFinal
            ->sortBy(function ($item) {
                return $item['fecha_creacion'] ?? null;
            })
            ->values();
    }

    public function obtenerPrendasConRecibos(\App\Models\User $usuario): \Illuminate\Support\Collection
    {

        $tipoOperario = $this->obtenerTipoOperario($usuario);

        \Log::info(' [obtenerPrendasConRecibos] TIPO OPERARIO DETECTADO', [
            'usuario' => $usuario->name,
            'usuario_id' => $usuario->id,
            'tipo_operario' => $tipoOperario,
            'es_costura_reflectivo' => $tipoOperario === 'costura-reflectivo' ? 'SI' : 'NO',
            'es_vista_costura' => $tipoOperario === 'vista-costura' ? 'SI' : 'NO'
        ]);

        // Determinar tipos de recibo segun el rol
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];
        if ($tipoOperario === 'vista-costura') {
            // Para vista-costura: mostrar COSTURA y REFLECTIVO (sin COSTURA-BODEGA)
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }
        if ($tipoOperario === 'costura-reflectivo') {
            // Para costura-reflectivo: mostrar COSTURA y REFLECTIVO
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }
        if ($tipoOperario === 'visualizador_plooter') {
            // Para visualizador_plooter: mostrar COSTURA solo del Area Corte (como cortador)
            $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];
        }

        // Obtener todos los recibos de costura activos con relaciones (incluyendo procesos)
        $query = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo);

        // Optimización para CORTADORES: Filtrar por prendas asignadas en SQL antes de cargar relaciones
        if ($tipoOperario === 'cortador') {
            $usuarioNombre = strtolower(trim($usuario->name));
            $prendasDelCortador = \App\Models\ProcesoPrenda::whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                ->pluck('prenda_pedido_id')
                ->unique()
                ->values()
                ->all();

            if (empty($prendasDelCortador)) {
                return collect();
            }

            $query->whereIn('prenda_id', $prendasDelCortador);
        }

        $query->whereIn('area', $tipoOperario === 'visualizador_plooter'
                ? ['Corte']
                : ($tipoOperario === 'vista-costura'
                    ? ['Corte', 'Costura', 'Control de Calidad', 'Control Calidad']
                    : ['Corte', 'Costura', 'Control de Calidad', 'Control Calidad']))
            ->with(['prenda', 'prenda.pedidoProduccion', 'prenda.procesosPrenda', 'prenda.tallas', 'prenda.coloresTelas', 'prenda.variantes', 'pedido']);

        // Para cortadores: excluir PENDIENTE_INSUMOS (misma logica que /recibos-costura)
        // y permitir ver recibos en Costura solo si aun NO hay encargado en proceso Costura
        if ($tipoOperario === 'cortador') {
            $query->where('estado', '!=', 'PENDIENTE_INSUMOS');
        }

        // Restricciones por rol sobre Areas visibles
        if ($tipoOperario === 'cortador') {
            $query->whereIn('area', ['Corte']);
        }

        if ($tipoOperario === 'costurero') {
            $query->whereIn('area', ['Costura']);
        }

        if ($tipoOperario === 'confeccion-sobremedida') {
            $query->whereIn('area', ['Costura']);
        }

        if ($tipoOperario === 'costura-reflectivo') {
            $query->whereIn('area', ['Costura']);
        }

        $recibos = $query->orderBy('created_at', 'asc')->get();

        if ($tipoOperario === 'cortador') {
            \Log::info(' [Filtro CORTADOR SQL] Recibos filtrados por prendas asignadas en Corte', [
                'usuario' => $usuario->name,
                'total' => $recibos->count(),
            ]);
        }

        // Para visualizador_plooter: mostrar solo recibos del Area Corte que estén asignados a él
        if ($tipoOperario === 'visualizador_plooter') {
            $usuarioNombre = strtolower(trim($usuario->name));

            // Obtener TODAS las prendas donde el usuario es encargado de Corte
            $prendasDelUsuario = \App\Models\ProcesoPrenda::whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                ->pluck('prenda_pedido_id')
                ->unique()
                ->values();

            $recibos = $recibos->filter(function ($recibo) use ($prendasDelUsuario) {
                // Solo mostrar recibos del Area Corte
                $area = strtolower(trim((string) ($recibo->area ?? '')));
                if ($area !== 'corte') {
                    return false;
                }

                // Validar que tenga prenda_id
                if (empty($recibo->prenda_id)) {
                    return false;
                }

                // Solo mostrar prendas donde el usuario es encargado de Corte
                return $prendasDelUsuario->contains($recibo->prenda_id);
            })->values();
        }

        // Para costura-reflectivo y vista-costura: AGREGAR REFLECTIVO aprobados SIN validar encargado
        if ($tipoOperario === 'costura-reflectivo' || $tipoOperario === 'vista-costura') {
            \Log::info(' [REFLECTIVO APROBADOS] BUSCANDO prendas con PROCESO REFLECTIVO APROBADO en pedidos_procesos_prenda_detalles', [
                'usuario' => $usuario->name,
                'recibos_costura_actuales' => $recibos->count()
            ]);

            // Buscar PRENDAS con proceso REFLECTIVO (tipo_proceso_id = 1) en estado APROBADO
            // tipo_proceso_id = 1 es Reflectivo segun tipos_procesos tabla
            $prendasReflectivoAprobadas = PedidosProcesosPrendaDetalle::where('tipo_proceso_id', 1)
                ->where('estado', 'APROBADO')
                ->with(['prenda', 'prenda.pedidoProduccion'])
                ->get()
                ->pluck('prenda')
                ->unique('id');

            \Log::info(' [REFLECTIVO APROBADOS] Prendas con PROCESO REFLECTIVO aprobado encontradas', [
                'total_prendas_reflectivo_aprobadas' => count($prendasReflectivoAprobadas)
            ]);

            // OPTIMIZACIÓN: Traer TODOS los recibos reflectivos de una sola query
            $prendasIds = $prendasReflectivoAprobadas->pluck('id')->all();
            $recibosReflectivosMap = [];
            if (!empty($prendasIds)) {
                $recibosReflectivos = ConsecutivoReciboPedido::query()
                    ->whereIn('prenda_id', $prendasIds)
                    ->where('tipo_recibo', 'REFLECTIVO')
                    ->where('activo', 1)
                    ->get();

                foreach ($recibosReflectivos as $recibo) {
                    $recibosReflectivosMap[$recibo->prenda_id] = $recibo;
                }
            }

            // Para cada prenda con reflectivo aprobado, buscar si tiene recibo REFLECTIVO (desde el mapa, sin query)
            foreach ($prendasReflectivoAprobadas as $prendaAprobada) {
                if (!$prendaAprobada || !$prendaAprobada->pedidoProduccion) {
                    \Log::info(' [REFLECTIVO] Prenda o pedido sin relacion', [
                        'prenda_id' => $prendaAprobada->id ?? 'null'
                    ]);
                    continue;
                }

                $reciboReflectivo = $recibosReflectivosMap[$prendaAprobada->id] ?? null;

                // Si existe recibo REFLECTIVO con proceso aprobado, agregarlo solo si esta en Area Costura (para costura-reflectivo)
                if ($reciboReflectivo) {
                    // Para costura-reflectivo, validar que esta en Area Costura
                    if ($tipoOperario === 'costura-reflectivo') {
                        $area = strtolower(trim((string) ($reciboReflectivo->area ?? '')));
                        if ($area !== 'costura') {
                            \Log::info(' [REFLECTIVO APROBADO EXCLUIDO] No esta en Area Costura', [
                                'numero_pedido' => $prendaAprobada->pedidoProduccion->numero_pedido,
                                'prenda_id' => $prendaAprobada->id,
                                'recibo_id' => $reciboReflectivo->id,
                                'area' => $reciboReflectivo->area
                            ]);
                            continue;
                        }
                    }

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

            // Agregar recibos REFLECTIVO de anexos (pedidos_parciales)
            // Para costura-reflectivo: solo si estan en Area Costura
            // Para vista-costura: aunque estén en Area Insumos
            $recibosReflectivoAnexos = ConsecutivoReciboPedido::query()
                ->where('activo', 1)
                ->where('tipo_recibo', 'REFLECTIVO')
                ->whereNotNull('notas')
                ->whereRaw('LOWER(notas) LIKE ?', ['%parcial_id:%']);
            
            // Para costura-reflectivo, filtrar por Area Costura
            if ($tipoOperario === 'costura-reflectivo') {
                $recibosReflectivoAnexos = $recibosReflectivoAnexos->whereRaw('LOWER(TRIM(area)) = ?', ['costura']);
            }
            
            $recibosReflectivoAnexos = $recibosReflectivoAnexos->get();

            if ($recibosReflectivoAnexos->isNotEmpty()) {
                $recibos = $recibos->concat($recibosReflectivoAnexos);

                \Log::info(' [REFLECTIVO ANEXOS] Recibos reflectivo parciales agregados al dashboard operario', [
                    'total_agregados' => $recibosReflectivoAnexos->count(),
                    'ids' => $recibosReflectivoAnexos->pluck('id')->values()->all(),
                ]);
            }

            // Re-ordenar por fecha y eliminar duplicados (misma prenda_id + tipo_recibo)
            $recibos = $recibos->sortByDesc('created_at')
                ->unique(function ($recibo) {
                    $notas = (string) ($recibo->notas ?? '');
                    $parcialSuffix = '';
                    if (preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                        $parcialSuffix = '_p' . $matches[1];
                    }
                    return $recibo->prenda_id . '_' . $recibo->tipo_recibo . $parcialSuffix;
                })
                ->sortBy('created_at')
                ->values();
        }

        // Deduplicar: Si hay multiples recibos con misma prenda_id + tipo_recibo,
        // mantener el mas reciente y luego presentar de mas viejo a mas nuevo.
        $recibos = $recibos->sortByDesc('created_at')->unique(function ($recibo) {
            $notas = (string) ($recibo->notas ?? '');
            $parcialSuffix = '';
            if (preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                $parcialSuffix = '_p' . $matches[1];
            }
            return ($recibo->prenda_id ?: ('pedido_' . $recibo->pedido_produccion_id)) . '_' . $recibo->tipo_recibo . $parcialSuffix;
        })->sortBy('created_at')->values();

        // vista-costura: ver recibos COSTURA en Area Costura, pero REFLECTIVO en cualquier Area
        if ($tipoOperario === 'vista-costura') {
            $recibos = $recibos
                ->filter(function ($recibo) {
                    $area = strtolower(trim((string) ($recibo->area ?? '')));
                    $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));

                    // Mostrar COSTURA solo si esta en Area costura
                    if ($tipoRecibo === 'COSTURA' || $tipoRecibo === 'COSTURA-BODEGA') {
                        return in_array($area, ['costura', 'corte'], true);
                    }

                    // Mostrar REFLECTIVO sin importar el Area
                    if ($tipoRecibo === 'REFLECTIVO') {
                        return true;
                    }

                    return false;
                })
                ->values();
        }

        // costura-reflectivo: ver COSTURA solo si Area es Costura, REFLECTIVO solo si Area es Costura
        if ($tipoOperario === 'costura-reflectivo') {
            $recibos = $recibos
                ->filter(function ($recibo) {
                    $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));
                    $area = strtolower(trim((string) ($recibo->area ?? '')));

                    // COSTURA: solo si Area es Costura
                    if ($tipoRecibo === 'COSTURA' || $tipoRecibo === 'COSTURA-BODEGA') {
                        return $area === 'costura';
                    }

                    // REFLECTIVO: solo si Area es Costura
                    if ($tipoRecibo === 'REFLECTIVO') {
                        return $area === 'costura';
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

        // Agrupar por prenda (o por pedido si es REFLECTIVO sin prenda_id) e incluir ID de parcial si existe
        $prendasAgrupadas = $recibos->groupBy(function ($recibo) use ($tipoOperario) {
            $parcialId = '';
            // Para vista-costura, agrupamos todos los parciales con su padre (no incluimos parcialId en el key)
            // para que no se generen tarjetas independientes por cada parte.
            if ($tipoOperario !== 'vista-costura') {
                $notas = (string) ($recibo->notas ?? '');
                if (preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                    $parcialId = '_' . $matches[1];
                }
            }
            
            // Si tiene prenda_id, agrupar por prenda
            if ($recibo->prenda_id) {
                return 'prenda_' . $recibo->prenda_id . $parcialId;
            }
            // Si no tiene prenda_id (REFLECTIVO), agrupar por pedido
            return 'pedido_' . $recibo->pedido_produccion_id . $parcialId;
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

            // Logging removido para mejorar performance en bucles grandes
            /*
            \Log::info(' [Prenda Validada]', [
                'numero_pedido' => $pedido->numero_pedido,
                'nombre_prenda' => $prenda->nombre_prenda,
                'area' => $pedido->area
            ]);
            */

            // Separar recibos por tipo - crear una entrada para cada tipo
            $recibosPorTipo = $recibosDelaPrenda->groupBy('tipo_recibo');

            $resultados = [];
            foreach ($recibosPorTipo as $tipoRecibo => $recibosDelTipo) {
                // Validaciones especi­ficas por tipo de recibo
                if (strtoupper($tipoRecibo) === 'REFLECTIVO') {
                    // REFLECTIVO: Si esta aprobado, se muestra sin validar encargado
                    // Los recibos REFLECTIVO ya vienen filtrados desde pedidos_procesos_prenda_detalles
                    \Log::info(' [REFLECTIVO VALIDO]', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_id' => $prenda->id,
                        'tipo_recibo' => strtoupper($tipoRecibo),
                        'estado' => $primeRecibo->estado,
                        'sin_validacion_encargado' => true
                    ]);
                } else if (strtoupper($tipoRecibo) === 'COSTURA' || strtoupper($tipoRecibo) === 'COSTURA-BODEGA') {
                    if ($tipoOperario === 'cortador') {
                        // Para cortadores: verificar que exista proceso "Corte" con encargado = usuario ESPECIFICAMENTE PARA ESTA PRENDA
                        $usuarioNombre = strtolower(trim($usuario->name));
                        $tieneProcesoCorte = $prenda->procesosPrenda
                            ->filter(function ($p) use ($usuarioNombre) {
                                return strtolower(trim((string)($p->proceso ?? ''))) === 'corte' &&
                                       strtolower(trim((string)($p->encargado ?? ''))) === $usuarioNombre;
                            })
                            ->isNotEmpty();

                       
                    } else {
                        // COSTURA/COSTURA-BODEGA: Validar que el usuario sea el encargado del proceso Costura para esta PRENDA
                        // (Sin restriccion de estado, permite: Pendiente, En Ejecución, etc)
                        // EXCEPCION: vista-costura ve TODOS sin restriccion de encargado
                        if ($tipoOperario === 'vista-costura') {
                            // vista-costura ve todos los recibos sin validacion de encargado
                            \Log::info(' [Filtro VISTA-COSTURA] âœ“ Usuario con rol vista-costura ve todos los recibos', [
                                'prenda_id' => $prenda->id,
                                'numero_pedido' => $pedido->numero_pedido,
                                'usuario' => $usuario->name
                            ]);
                        } else if ($tipoOperario === 'costurero' || $tipoOperario === 'confeccion-sobremedida') {
                            $usuarioNombre = strtolower(trim($usuario->name));

                            // Buscar proceso Costura especi­ficamente para esta prenda (desde la relacion cargada)
                            $procesoCosturaDelaPrenda = $prenda->procesosPrenda
                                ->filter(function ($p) {
                                    return strtolower(trim((string)($p->proceso ?? ''))) === 'costura';
                                })
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

                            \Log::info(' [Filtro COSTURERO] âœ“ Usuario es encargado de esta prenda', [
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

                            // Buscar encargado en el proceso Costura (desde relacion cargada)
                            $procesoCosturaDelaPrenda = $prenda->procesosPrenda
                                ->filter(function ($p) {
                                    return strtolower(trim((string)($p->proceso ?? ''))) === 'costura';
                                })
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
                                $encargadoUsuario = \App\Models\User::whereRaw('LOWER(TRIM(name)) = ?', [$encargadoAsignado])->first();
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
                                // costura-reflectivo: solo ve los que le fueron asignados a 
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
                        // FILTRO DE AREA: Solo mostrar REFLECTIVO en Area "Costura"
                        $area = strtolower(trim((string) ($recibo->area ?? '')));
                        if ($area !== 'costura') {
                            \Log::info(' [REFLECTIVO LIDER-REFLECTIVO] Recibo excluido por Area', [
                                'recibo_id' => $recibo->id,
                                'consecutivo' => $recibo->consecutivo_actual,
                                'area' => $recibo->area,
                                'tipo_recibo' => $recibo->tipo_recibo
                            ]);
                            return false;
                        }

                        $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                        $esParcial = $notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas) === 1;

                        if ($esParcial) {
                            return true;
                        }

                        $tieneParciales = \App\Models\ReciboPorPartes::where('pedido_produccion_id', $recibo->pedido_produccion_id)
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

                // Para costura-reflectivo: filtrar REFLECTIVO por Area Costura
                if ($tipoOperario === 'costura-reflectivo' && strtoupper((string) $tipoRecibo) === 'REFLECTIVO') {
                    $recibosDelTipo = $recibosDelTipo->filter(function ($recibo) {
                        // FILTRO DE AREA: Solo mostrar REFLECTIVO en Area "Costura"
                        $area = strtolower(trim((string) ($recibo->area ?? '')));
                        if ($area !== 'costura') {
                            \Log::info(' [REFLECTIVO COSTURA-REFLECTIVO] Recibo excluido por Area', [
                                'recibo_id' => $recibo->id,
                                'consecutivo' => $recibo->consecutivo_actual,
                                'area' => $recibo->area,
                                'tipo_recibo' => $recibo->tipo_recibo
                            ]);
                            return false;
                        }
                        return true;
                    })->values();

                    if ($recibosDelTipo->isEmpty()) {
                        continue;
                    }
                }

                // Identificar si alguno es parcial
                $primerRecibo = $recibosDelTipo->first();
                $parcialIdPadre = null;
                $notasPadre = (string) ($primerRecibo->notas ?? '');
                if (preg_match('/parcial_id:(\d+)/i', $notasPadre, $matches)) {
                    $parcialIdPadre = (int) $matches[1];
                }

                // Ordenar por la fecha de llegada a Corte (o Costura para vista-costura)
                $recibosDelTipo = $recibosDelTipo
                    ->sortBy(function ($recibo) use ($tipoOperario) {
                        if ($tipoOperario === 'vista-costura') {
                            $fechaCostura = $this->obtenerFechaCreacionProcesoCostura($recibo);
                            return $this->normalizarFechaAOrdenable($fechaCostura ?? $recibo->created_at);
                        }
                        $fechaCorte = $this->obtenerFechaLlegadaACorte($recibo);
                        return $this->normalizarFechaAOrdenable($fechaCorte ?? $recibo->created_at);
                    })
                    ->values();

                $fechaOrdenPrincipal = optional($recibosDelTipo->first())?->created_at;
                if ($recibosDelTipo->isNotEmpty()) {
                    if ($tipoOperario === 'vista-costura') {
                        $fechaCosturaPrincipal = $this->obtenerFechaCreacionProcesoCostura($recibosDelTipo->first());
                        if (!empty($fechaCosturaPrincipal)) {
                            $fechaOrdenPrincipal = $fechaCosturaPrincipal;
                        }
                    } else {
                        $fechaCortePrincipal = $this->obtenerFechaLlegadaACorte($recibosDelTipo->first());
                        if (!empty($fechaCortePrincipal)) {
                            $fechaOrdenPrincipal = $fechaCortePrincipal;
                        }
                    }
                }


                $resultados[] = [
                    'prenda_id' => $prenda->id,
                    'pedido_id' => $pedido->id,
                    'pedido_parcial_id' => $parcialIdPadre,
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
                    'recibos' => $recibosDelTipo
                        ->filter(function ($recibo) use ($tipoOperario, $recibosDelTipo) {
                            if ($tipoOperario !== 'vista-costura') {
                                return true;
                            }
                            // Para vista-costura, solo mostramos el recibo padre en la lista de recibos de la card
                            // para evitar que aparezcan múltiples conjuntos de botones. Las partes se gestionan
                            // a través del botón "Ver Distribución".
                            $notas = (string) ($recibo->notas ?? '');
                            $esParcial = preg_match('/parcial_id:(\d+)/i', $notas) === 1;
                            if (!$esParcial) {
                                return true;
                            }

                            $hayPadre = $recibosDelTipo->contains(function ($r) {
                                $notasR = (string) ($r->notas ?? '');
                                return preg_match('/parcial_id:(\d+)/i', $notasR) !== 1;
                            });

                            return !$hayPadre;
                        })
                        ->map(function ($recibo) use ($pedido) {
                        // Buscar el proceso de Control Calidad mas reciente para este recibo (desde relacion cargada)
                        $procesoCC = ($recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda'))
                            ? $recibo->prenda->procesosPrenda
                                ->filter(function ($p) {
                                    $proc = strtolower(trim((string)($p->proceso ?? '')));
                                    return in_array($proc, ['control calidad', 'control de calidad'], true);
                                })
                                ->sortByDesc('created_at')
                                ->first()
                            : null;

                        $parcialId = null;
                        $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                        if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                            $parcialId = (int) $matches[1];
                        }
                        $esParcial = $parcialId !== null;

                        // Buscar el proceso mas relevante para este tipo de recibo
                        $procesosParaFiltrar = ($recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda'))
                            ? $recibo->prenda->procesosPrenda
                                ->filter(function ($p) {
                                    return strtolower(trim((string)($p->proceso ?? ''))) === 'costura';
                                })
                            : collect();

                        if ($esParcial) {
                            $esAnexo = stripos($notas, 'anexo') !== false;

                            if ($esAnexo) {
                                // Para anexos, el proceso usa numero_recibo exacto y no tiene parcial
                                $procesoCostura = $procesosParaFiltrar
                                    ->filter(function ($p) use ($recibo) {
                                        $nrp = $p->numero_recibo_parcial ?? null;
                                        return (string) $p->numero_recibo === (string) $recibo->consecutivo_actual &&
                                               ($nrp === null || trim((string)$nrp) === '' || (float) $nrp === 0.0);
                                    })
                                    ->sortByDesc('created_at')
                                    ->first();
                            } else {
                                // Si es parcial, buscar por numero_recibo_parcial
                                $procesoCostura = $procesosParaFiltrar
                                    ->where('numero_recibo_parcial', $recibo->consecutivo_actual)
                                    ->sortByDesc('created_at')
                                    ->first();
                            }
                        } else {
                            // Si NO es parcial, buscar primero por numero_recibo exacto (proceso padre)
                            $procesoCostura = $procesosParaFiltrar
                                ->filter(function ($p) use ($recibo) {
                                    $nrp = $p->numero_recibo_parcial ?? null;
                                    return (string) $p->numero_recibo === (string) $recibo->consecutivo_actual &&
                                           ($nrp === null || trim((string)$nrp) === '' || (float) $nrp === 0.0);
                                })
                                ->sortByDesc('created_at')
                                ->first();
                        }

                        if (!$procesoCostura) {
                            $procesoCostura = $procesosParaFiltrar
                                ->sortByDesc('created_at')
                                ->first();
                        }

                        $procesoCorte = ($recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda'))
                            ? $recibo->prenda->procesosPrenda
                                ->filter(function ($p) {
                                    return strtolower(trim((string)($p->proceso ?? ''))) === 'corte';
                                })
                                ->sortByDesc('created_at')
                                ->first()
                            : null;

                        $completadoCorte = \DB::table('prenda_recibo_completado')
                            ->where('id_recibo', $recibo->id)
                            ->whereRaw('LOWER(TRIM(area)) = ?', ['corte'])
                            ->exists();

                        $registroCompletadoCostura = \DB::table('prenda_recibo_completado')
                            ->where('id_recibo', $recibo->id)
                            ->whereRaw('LOWER(TRIM(area)) = ?', ['costura'])
                            ->first();
                        $completadoCostura = !empty($registroCompletadoCostura);
                        $fechaCompletadoCostura = $registroCompletadoCostura->fecha_completado ?? null;


                        $completadoControlCalidad = \DB::table('prenda_recibo_completado')
                            ->where('id_recibo', $recibo->id)
                            ->where(function($query) {
                                $query->whereRaw('LOWER(TRIM(area)) = ?', ['control calidad'])
                                      ->orWhereRaw('LOWER(TRIM(area)) = ?', ['control de calidad']);
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
                            'fecha_inicio_proceso' => $procesoCostura?->fecha_inicio
                                ?? $procesoCorte?->fecha_inicio
                                ?? $procesoCC?->fecha_inicio
                                ?? null,
                            'fecha_asignacion_costura' => $procesoCostura?->fecha_de_asignacion_encargado ?? null,
                            'fecha_proceso_costura_created_at' => $procesoCostura?->created_at ?? null,
                            'fecha_asignacion_corte' => $procesoCorte?->fecha_de_asignacion_encargado ?? null,
                            'fecha_proceso_corte_created_at' => $procesoCorte?->created_at ?? null,
                            'fecha_proceso_created_at' => $procesoCostura?->created_at
                                ?? $procesoCorte?->created_at
                                ?? $procesoCC?->created_at
                                ?? null,
                            'fecha_asignacion_proceso' => $procesoCostura?->fecha_de_asignacion_encargado
                                ?? $procesoCorte?->fecha_de_asignacion_encargado
                                ?? $procesoCC?->fecha_de_asignacion_encargado
                                ?? null,
                            'area' => $recibo->area,
                            'proceso_id' => $procesoCC ? $procesoCC->id : null,
                            'proceso_id_costura' => $procesoCostura ? $procesoCostura->id : null,
                            'encargado_costura' => $procesoCostura ? $procesoCostura->encargado : null,
                            'encargado_corte' => $procesoCorte ? $procesoCorte->encargado : null,
                            'completado_corte' => $completadoCorte,
                            'completado_costura' => $completadoCostura,
                            'fecha_completado_costura' => $fechaCompletadoCostura,
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
                    'fecha_creacion' => $fechaOrdenPrincipal ?? $prenda->created_at,
                ];
            }

            return $resultados;
        })->values();

        $resultadoFinal = $prendasAgrupadas;

        // Para vista-costura no agregamos los parciales como tarjetas independientes al final,
        // ya que deben ser visibles únicamente dentro de su recibo padre.
        if ($tipoOperario !== 'vista-costura') {
            $resultadoFinal = $resultadoFinal->concat($this->obtenerPrendasParcialesCostura($usuario, false));
        }

        $resultadoFinal = $resultadoFinal->unique(function ($item) {
                $parcialId = $item['pedido_parcial_id'] ?? null;
                $isReciboPorPartes = $item['es_recibo_por_partes'] ?? false;
                $prefix = $isReciboPorPartes ? 'rx_' : 'an_';
                return $item['prenda_id'] . ($parcialId ? '_' . $prefix . $parcialId : '');
            });

        if ($tipoOperario === 'vista-costura') {
            return $resultadoFinal
                ->sortByDesc(function ($item) {
                    return $item['fecha_creacion'] ?? null;
                })
                ->values();
        }

        if ($tipoOperario === 'cortador') {
            return $resultadoFinal
                ->map(function ($item) {
                    $recibosCorte = collect($item['recibos'] ?? [])
                        ->filter(function ($recibo) {
                            $area = strtolower(trim((string) ($recibo['area'] ?? '')));
                            return $area === 'corte';
                        })
                        ->values()
                        ->all();

                    $item['recibos'] = $recibosCorte;
                    $item['total_recibos'] = count($recibosCorte);

                    if (!empty($recibosCorte)) {
                        $item['fecha_creacion'] = $recibosCorte[0]['fecha_asignacion_corte']
                            ?? $recibosCorte[0]['fecha_proceso_corte_created_at']
                            ?? ($item['fecha_creacion'] ?? null);
                    }

                    return $item;
                })
                ->filter(function ($item) {
                    return !empty($item['recibos']);
                })
                ->sortBy(function ($item) {
                    return $item['fecha_creacion'] ?? null;
                })
                ->values();
        }

        return $resultadoFinal
            ->sortBy(function ($item) {
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

        // OPTIMIZACIÓN: Usar SQL para filtrar pedidos en lugar de PHP
        // Obtener solo los numeros_pedido donde este usuario es encargado
        $pedidosAsignados = ProcesoPrenda::query()
            ->select('numero_pedido')
            ->whereNotNull('encargado')
            ->whereRaw("LOWER(TRIM(encargado)) = ?", [$usuarioNormalizado])
            ->distinct()
            ->pluck('numero_pedido')
            ->all();

        // Si no hay pedidos asignados al usuario, retornar colección vacía
        if (empty($pedidosAsignados)) {
            \Log::info('[ObtenerPrendasRecibosService] Cortador - Sin pedidos asignados', [
                'usuario' => $usuario->name,
            ]);
            return collect();
        }

        // Obtener solo los pedidos asignados al usuario (en SQL, no en PHP)
        $pedidos = PedidoProduccion::with(['prendas', 'prendas.tallas'])
            ->whereIn('numero_pedido', $pedidosAsignados)
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('[ObtenerPrendasRecibosService] Cortador - Pedidos encontrados', [
            'usuario' => $usuario->name,
            'total_pedidos' => $pedidos->count(),
        ]);

        // Convertir pedidos a formato de prendas para compatibilidad con la vista
        $prendas = $pedidos->flatMap(function ($pedido) {
            if (!$pedido->prendas || $pedido->prendas->isEmpty()) {
                // Si no hay prendas, crear una entrada generica
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

        // Obtener ReciboPorPartes (parciales de costura)
        $parciales = ReciboPorPartes::query()
            ->with(['pedido', 'prenda.tallas', 'tallas'])
            ->whereIn('tipo_recibo', $tiposParcial)
            ->orderBy('created_at', 'asc')
            ->get();

        // Obtener tambien pedidos_parciales (anexos) que estan activos
        $anexos = \DB::table('pedidos_parciales as pp')
            ->join('pedidos_produccion as p', 'pp.pedido_produccion_id', '=', 'p.id')
            ->join('prendas_pedido as pr', 'pp.prenda_pedido_id', '=', 'pr.id')
            ->where('pp.activo', 1)
            ->whereIn('pp.tipo_recibo', $tiposParcial)
            ->whereNull('pp.deleted_at')
            ->select('pp.*', 'p.numero_pedido', 'pr.nombre_prenda', 'pr.descripcion')
            ->orderBy('pp.created_at', 'asc')
            ->get();

        // OPTIMIZACIÓN: Traer TODOS los procesos de una sola query (en lugar de por cada parcial)
        $parcialIds = $parciales->pluck('prenda_pedido_id')->unique()->all();
        $procesosMap = [];
        if (!empty($parcialIds)) {
            $procesos = ProcesoPrenda::query()
                ->whereIn('prenda_pedido_id', $parcialIds)
                ->whereNull('deleted_at')
                ->get();

            foreach ($procesos as $proc) {
                $key = $proc->prenda_pedido_id . '_' . $proc->numero_recibo_parcial;
                if (!isset($procesosMap[$key])) {
                    $procesosMap[$key] = [];
                }
                $procesosMap[$key][] = $proc;
            }
        }

        // OPTIMIZACIÓN: Traer completados de una sola query
        $parcialeIds = $parciales->pluck('id')->all();
        $completadosMap = [];
        if (!empty($parcialeIds)) {
            $completados = \DB::table('prenda_recibo_completado')
                ->whereIn('id_parcial', $parcialeIds)
                ->where('area', 'Corte')
                ->pluck('id_parcial')
                ->all();
            $completadosMap = array_flip($completados);
        }

        // Combinar ambas colecciones
        $todasLasParciales = collect();

        // Procesar ReciboPorPartes
        $parciales->each(function (ReciboPorPartes $parcial) use (&$todasLasParciales, $procesosMap, $completadosMap) {
            $pedido = $parcial->pedido;
            $prenda = $parcial->prenda;

            if (!$pedido || !$prenda) {
                return;
            }

            // Buscar procesos desde el mapa precargado (sin queries)
            $key = $parcial->prenda_pedido_id . '_' . $parcial->consecutivo_parcial;
            $procesosCostura = $procesosMap[$key] ?? [];

            $procesoCostura = collect($procesosCostura)
                ->filter(fn($p) => strtolower(trim((string) $p->proceso)) === 'costura')
                ->sortByDesc('created_at')
                ->first();

            $procesoReciente = collect($procesosCostura)
                ->sortByDesc('created_at')
                ->first();

            // Verificar completado desde el mapa precargado
            $completadoCorte = isset($completadosMap[$parcial->id]);

            $areaActual = 'Corte';
            if ($completadoCorte) {
                $areaActual = 'Costura';
            } elseif ($procesoReciente && strtolower(trim($procesoReciente->proceso)) === 'costura') {
                $areaActual = 'Costura';
            }

            $todasLasParciales->push([
                'parcial' => $parcial,
                'pedido' => $pedido,
                'prenda' => $prenda,
                'proceso' => $procesoCostura,
                'proceso_reciente' => $procesoReciente,
                'encargado_normalizado' => strtolower(trim((string) ($procesoCostura?->encargado ?? $parcial->encargado ?? ''))),
                'es_anexo' => false,
                'area_detectada' => $areaActual,
            ]);
        });

        // Procesar pedidos_parciales (anexos)
        $anexos->each(function ($anexo) use (&$todasLasParciales) {
            $pedido = PedidoProduccion::find($anexo->pedido_produccion_id);
            $prenda = PrendaPedido::find($anexo->prenda_pedido_id);

            if (!$pedido || !$prenda) {
                return;
            }

            $tipoParcial = strtoupper(trim((string) ($anexo->tipo_recibo ?? '')));
            $procesoObjetivo = 'costura';

            // Buscar el proceso de costura para obtener el encargado
            $procesoCostura = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $anexo->prenda_pedido_id)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->where('numero_recibo', $anexo->consecutivo_actual)
                ->where(function ($query) {
                    $query->whereNull('numero_recibo_parcial')
                          ->orWhere('numero_recibo_parcial', '')
                          ->orWhere('numero_recibo_parcial', 0);
                })
                ->whereNull('deleted_at')
                ->latest('created_at')
                ->first();

            // Buscar el proceso mas reciente para determinar el Area actual
            $procesoReciente = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $anexo->prenda_pedido_id)
                ->where('numero_recibo', $anexo->consecutivo_actual)
                ->where(function ($query) {
                    $query->whereNull('numero_recibo_parcial')
                          ->orWhere('numero_recibo_parcial', '')
                          ->orWhere('numero_recibo_parcial', 0);
                })
                ->whereNull('deleted_at')
                ->latest('created_at')
                ->first();

            // Buscar si ya fue completado en Corte
            $completadoCorte = \DB::table('prenda_recibo_completado')
                ->where('id_parcial', $anexo->id)
                ->where('area', 'Corte')
                ->exists();

            $areaActual = 'Corte'; // Default para anexos es Corte hasta que se complete o pase a Costura
            if ($completadoCorte) {
                $areaActual = 'Costura';
            } elseif ($procesoReciente && strtolower(trim($procesoReciente->proceso)) === 'costura') {
                $areaActual = 'Costura';
            }

            $todasLasParciales->push([
                'parcial' => $anexo,
                'pedido' => $pedido,
                'prenda' => $prenda,
                'proceso' => $procesoCostura,
                'proceso_reciente' => $procesoReciente,
                'encargado_normalizado' => strtolower(trim((string) ($procesoCostura->encargado ?? $anexo->encargado ?? ''))),
                'es_anexo' => true,
                'area_detectada' => $areaActual,
            ]);
        });

        return $todasLasParciales->map(function (array $item) {
            return $item;
        })
            ->filter()
            ->filter(function (array $item) use ($modoTodosCostura, $tipoOperario, $encargadoNormalizado, $esLiderReflectivo) {
                $encargado = $item['encargado_normalizado'];
                $tipoParcial = strtoupper(trim((string) ($item['parcial']->tipo_recibo ?? '')));
                $area = strtolower(trim((string) ($item['area_detectada'] ?? $item['parcial']->area ?? '')));
                
                // Para costura-reflectivo, solo mostrar parciales del Area Costura
                if ($tipoOperario === 'costura-reflectivo' && $area !== 'costura') {
                    return false;
                }
                
                if ($encargado === '') {
                    // Para vista-costura o modo todos: mostrar siempre
                    if ($modoTodosCostura || $tipoOperario === 'vista-costura') {
                        return true;
                    }

                    // Para reflectivo en dashboard costura-reflectivo/lider-reflectivo:
                    // mostrar tambien anexos sin encargado para que sean visibles.
                    if ($tipoOperario === 'costura-reflectivo' && $tipoParcial === 'REFLECTIVO') {
                        return true;
                    }
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
                $parcial = $item['parcial'];
                $pedido = $item['pedido'];
                $prenda = $item['prenda'];
                $proceso = $item['proceso'];
                $procesoReciente = $item['proceso_reciente'] ?? null;
                $esAnexo = $item['es_anexo'] ?? false;

                // Obtener tallas segun el tipo
                $tallas = [];
                if ($esAnexo) {
                    // Para anexos, obtener tallas de pedidos_parciales_tallas
                    $tallaRows = \DB::table('pedidos_parciales_tallas')
                        ->where('pedido_parcial_id', $parcial->id)
                        ->get();
                    
                    $tallas = $tallaRows->map(function ($talla) {
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
                    })->toArray();
                } else {
                    // Para ReciboPorPartes, obtener tallas de recibos_por_partes_tallas
                    $tallas = $parcial->tallas->map(function ($talla) {
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
                    })->toArray();
                }

                $consecutivoParcial = $this->formatearConsecutivoParcial($parcial->consecutivo_parcial ?? $parcial->consecutivo_actual);
                $registroCompletadoCostura = \DB::table('prenda_recibo_completado')
                    ->where('area', 'Costura')
                    ->where('id_parcial', $parcial->id)
                    ->first();
                $completadoCostura = !empty($registroCompletadoCostura);
                $fechaCompletadoCostura = $registroCompletadoCostura->fecha_completado ?? null;


                // Para anexos, el encargado SIEMPRE viene de procesos_prenda
                // Para parciales (ReciboPorPartes), puede venir de procesos_prenda o del campo encargado del parcial
                $encargadoCostura = $proceso?->encargado;
                if (!$encargadoCostura && !$esAnexo) {
                    $encargadoCostura = $parcial->encargado;
                }

                // Si esta en Corte, buscar el encargado de Corte
                $encargadoCorte = null;
                if ($item['area_detectada'] === 'Corte') {
                    $encargadoCorte = $procesoReciente?->encargado;
                }

                return [
                    'prenda_id' => $prenda->id,
                    'pedido_id' => $pedido->id,
                    'pedido_parcial_id' => $parcial->id,
                    'es_recibo_por_partes' => !$esAnexo,
                    'es_anexo' => $esAnexo,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => $prenda->de_bodega ?? false,
                    'tallas' => $tallas,
                    'recibos' => [[
                        'id' => null,
                        'tipo_recibo' => (string) ($parcial->tipo_recibo ?: 'PARCIAL'),
                        'consecutivo_actual' => $consecutivoParcial,
                        'consecutivo_inicial' => $this->formatearConsecutivoParcial($parcial->consecutivo_inicial ?? $parcial->consecutivo_original),
                        'consecutivo_parcial' => $consecutivoParcial,
                        'notas' => $esAnexo ? 'anexo_id:' . $parcial->id : 'parcial_id:' . $parcial->id,
                        'creado_en' => $parcial->created_at,
                        'fecha_inicio_proceso' => $proceso?->fecha_inicio ?? null,
                        'fecha_asignacion_costura' => $proceso?->fecha_de_asignacion_encargado ?? null,
                        'fecha_proceso_costura_created_at' => $proceso?->created_at ?? null,
                        'area' => $item['area_detectada'] ?? 'Costura',
                        'proceso_id' => $proceso?->id,
                        'proceso_id_costura' => $proceso?->id,
                        'encargado_costura' => $encargadoCostura,
                        'encargado_corte' => $encargadoCorte,
                        'encargado_control_calidad' => null,
                        'completado_area' => $completadoCostura,
                        'completado_corte' => false,
                        'completado_costura' => $completadoCostura,
                        'fecha_completado_costura' => $fechaCompletadoCostura,
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
                return $numeroReciboParcial === null || trim((string)$numeroReciboParcial) === '' || (float) $numeroReciboParcial === 0.0;
            })
            ->sortByDesc(fn($proceso) => $proceso->created_at)
            ->first();
    }

    /**
     * Obtener la fecha de creacion del proceso de Costura asociado a un recibo
     */
    private function obtenerFechaCreacionProcesoCostura($recibo): mixed
    {
        if (!$recibo) return null;

        // OPTIMIZACIÓN: Detectar parcial una sola vez
        $notas = (string) ($recibo->notas ?? '');
        $esParcial = str_contains($notas, 'parcial_id:');
        $consecutivo = $recibo->consecutivo_actual;

        // Si la relacion ya esta cargada en la prenda, usarla
        if ($recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda')) {
            // Búsqueda directa sin filtro (más rápida)
            if ($esParcial) {
                $procesoCostura = $recibo->prenda->procesosPrenda
                    ->where('numero_recibo_parcial', $consecutivo)
                    ->where('proceso', 'COSTURA')
                    ->sortByDesc('created_at')
                    ->first();
            } else {
                $procesoCostura = $recibo->prenda->procesosPrenda
                    ->where('numero_recibo', $consecutivo)
                    ->where('proceso', 'COSTURA')
                    ->whereIn('numero_recibo_parcial', [null, '', 0])
                    ->sortByDesc('created_at')
                    ->first();
            }

            if ($procesoCostura) {
                return $procesoCostura->created_at;
            }
        }

        // Fallback a consulta SQL si no esta cargada
        $query = ProcesoPrenda::query()
            ->where('numero_pedido', $recibo->pedido_produccion_id)
            ->where('prenda_pedido_id', $recibo->prenda_id)
            ->where('proceso', 'COSTURA')
            ->whereNull('deleted_at');

        if ($esParcial) {
            $query->where('numero_recibo_parcial', $consecutivo);
        } else {
            $query->where('numero_recibo', $consecutivo)
                  ->where(function($q) {
                      $q->whereNull('numero_recibo_parcial')
                        ->orWhere('numero_recibo_parcial', '')
                        ->orWhere('numero_recibo_parcial', 0);
                  });
        }

        $proc = $query->latest('created_at')->first();
        return $proc ? $proc->created_at : null;
    }

    private function obtenerFechaLlegadaACorte($recibo): mixed
    {
        // OPTIMIZACIÓN: Detectar parcial una sola vez
        $notas = (string) ($recibo->notas ?? '');
        $esParcial = str_contains($notas, 'parcial_id:');
        $consecutivo = $recibo->consecutivo_actual;

        // Si la relacion ya esta cargada, usarla para evitar consulta SQL
        if ($recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda')) {
            // Búsqueda directa sin filtro (más rápida)
            if ($esParcial) {
                $procesoCorte = $recibo->prenda->procesosPrenda
                    ->where('numero_recibo_parcial', $consecutivo)
                    ->where('proceso', 'CORTE')
                    ->sortByDesc('created_at')
                    ->first();
            } else {
                $procesoCorte = $recibo->prenda->procesosPrenda
                    ->where('numero_recibo', $consecutivo)
                    ->where('proceso', 'CORTE')
                    ->whereIn('numero_recibo_parcial', [null, '', 0])
                    ->sortByDesc('created_at')
                    ->first();
            }

            if ($procesoCorte) {
                // Priorizar fecha de asignacion del encargado
                return $procesoCorte->fecha_de_asignacion_encargado ?? $procesoCorte->created_at;
            }
        }

        // Fallback a consulta SQL si no esta cargada
        $query = ProcesoPrenda::query()
            ->where('numero_pedido', $recibo->pedido_produccion_id)
            ->where('prenda_pedido_id', $recibo->prenda_id)
            ->where('proceso', 'CORTE')
            ->whereNull('deleted_at');

        if ($esParcial) {
            $query->where('numero_recibo_parcial', $consecutivo);
        } else {
            $query->where('numero_recibo', $consecutivo)
                ->where(function ($subQuery) {
                    $subQuery->whereNull('numero_recibo_parcial')
                        ->orWhere('numero_recibo_parcial', 0);
                });
        }

        $proc = $query->latest('created_at')->first();
        return $proc ? ($proc->fecha_de_asignacion_encargado ?? $proc->created_at) : null;
    }



    private function normalizarFechaAOrdenable($fecha): int
    {
        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->getTimestamp();
        }

        if (is_numeric($fecha)) {
            return (int) $fecha;
        }

        if (is_string($fecha) && trim($fecha) !== '') {
            $timestamp = strtotime($fecha);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        return 0;
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

        if ($usuario->hasRole('visualizador_plooter')) {
            return 'visualizador_plooter';
        }

        return 'desconocido';
    }
    public function obtenerPrendasConRecibosBodegaCortador(\App\Models\User $usuario): \Illuminate\Support\Collection
    {
        $usuarioNombre = strtolower(trim((string) $usuario->name));

        $recibos = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->whereRaw('LOWER(TRIM(area)) = ?', ['corte'])
            ->whereNotNull('prenda_bodega_id')
            ->with(['prendaBodega'])
            ->get();

        if ($recibos->isEmpty()) {
            return collect();
        }

        $prendaBodegaIds = $recibos->pluck('prenda_bodega_id')->filter()->unique()->values()->all();

        $procesos = ProcesoPrenda::query()
            ->whereIn('prenda_bodega_id', $prendaBodegaIds)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
            ->whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('prenda_bodega_id');

        $resultado = collect();

        foreach ($procesos as $prendaBodegaId => $procesosPrenda) {
            $recibo = $recibos->firstWhere('prenda_bodega_id', $prendaBodegaId);
            if (!$recibo) {
                continue;
            }

            $resultado->push([
                'id' => $recibo->id,
                'numero_pedido' => '',
                'prenda_id' => null,
                'nombre_prenda' => $recibo->prendaBodega?->nombre ?? 'N/A',
                'prenda_bodega_id' => $prendaBodegaId,
                'tipo_recibo' => $recibo->tipo_recibo,
                'total_recibos' => 1,
                'recibos' => [
                    [
                        'id' => $recibo->id,
                        'tipo_recibo' => $recibo->tipo_recibo,
                        'consecutivo_actual' => $recibo->consecutivo_actual,
                        'area' => $recibo->area,
                    ]
                ]
            ]);
        }

        return $resultado;
    }

    public function obtenerConteoPrendasConRecibosBodegaCortador(\App\Models\User $usuario): int
    {
        $usuarioNombre = strtolower(trim((string) $usuario->name));

        $recibos = ConsecutivoReciboPedido::query()
            ->where('activo', 1)
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->whereRaw('LOWER(TRIM(area)) = ?', ['corte'])
            ->whereNotNull('prenda_bodega_id')
            ->pluck('prenda_bodega_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($recibos)) {
            return 0;
        }

        return ProcesoPrenda::query()
            ->whereIn('prenda_bodega_id', $recibos)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
            ->whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
            ->whereNull('deleted_at')
            ->distinct('prenda_bodega_id')
            ->count();
    }
}
