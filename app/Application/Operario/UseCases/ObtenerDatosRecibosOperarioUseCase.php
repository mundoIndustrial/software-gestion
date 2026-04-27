<?php

namespace App\Application\Operario\UseCases;

use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Domain\Operario\Repositories\ReciboParcialReadRepository;
use App\Models\ReciboPorPartes;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ObtenerDatosRecibosOperarioUseCase
{
    public function __construct(
        private readonly PedidoProduccionOperarioReadRepository $pedidos,
        private readonly ReciboParcialReadRepository $parciales,
        private readonly ObtenerPedidoUseCase $obtenerPedidoUseCase,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(int $numeroPedido, Request $request): array
    {
        $tipoRecibo = (string) $request->query('tipo_recibo', 'COSTURA');
        $parcialId = $request->query('parcial_id');
        $consecutivoParcial = $request->query('consecutivo_parcial');

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] INICIO', [
            'numero_pedido' => $numeroPedido,
            'tipo_recibo' => $tipoRecibo,
            'parcial_id' => $parcialId,
            'consecutivo_parcial' => $consecutivoParcial,
            'query_params_completos' => $request->query(),
            'url_actual' => $request->fullUrl(),
        ]);

        // OJO: este endpoint recibe un NÚMERO de pedido, no un ID. Evitar ambigüedad con findByIdOrNumero.
        $pedido = $this->pedidos->findByNumeroWithPrendas((int) $numeroPedido);

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Búsqueda de pedido', [
            'numero_pedido' => $numeroPedido,
            'encontrado' => !!$pedido,
            'pedido_id' => $pedido->id ?? null,
            'tipo_recibo' => $tipoRecibo,
            'parcial_id' => $parcialId,
        ]);

        if (!$pedido) {
            \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Pedido no encontrado', [
                'numero_pedido' => $numeroPedido,
            ]);

            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'error' => 'not found',
                    'message' => 'Pedido no encontrado',
                ],
            ];
        }

        if ($tipoRecibo === 'PARCIAL' && $parcialId) {
            $prendaId = $request->query('prenda_id');

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Obteniendo datos del parcial', [
                'parcial_id' => $parcialId,
                'parcial_id_type' => gettype($parcialId),
                'consecutivo_parcial' => $consecutivoParcial,
                'prenda_id' => $prendaId,
                'pedido_id' => $pedido->id,
            ]);

            $parcial = $this->parciales->findByIdWithRelationsAndTallas((int) $parcialId);

            if ($parcial) {
                \Log::debug('[ObtenerDatosRecibosOperarioUseCase] Datos crudos del parcial', [
                    'parcial_id' => $parcial->id,
                    'tallas_json' => json_encode($parcial->tallas),
                    'primer_talla' => $parcial->tallas ? json_encode($parcial->tallas->first()) : 'sin tallas',
                ]);
            }

            if (!$parcial) {
                // Fallback defensivo: algunos cards envían parcial_id no alineado con recibo_por_partes.id.
                // Intentar resolver por contexto (pedido + prenda + consecutivo_parcial).
                if ($consecutivoParcial !== null && $consecutivoParcial !== '' && $prendaId !== null && $prendaId !== '') {
                    $parcial = ReciboPorPartes::query()
                        ->with(['tallas', 'pedido', 'prenda'])
                        ->where('pedido_produccion_id', (int) $pedido->id)
                        ->where('prenda_pedido_id', (int) $prendaId)
                        ->where('consecutivo_parcial', (float) $consecutivoParcial)
                        ->latest('id')
                        ->first();
                }
            }

            if (!$parcial) {
                // Compatibilidad legacy: algunos parciales todavía viven en pedidos_parciales.
                $parcialLegacy = DB::table('pedidos_parciales')
                    ->where('id', (int) $parcialId)
                    ->where('pedido_produccion_id', (int) $pedido->id)
                    ->when(
                        $prendaId !== null && $prendaId !== '',
                        fn ($query) => $query->where('prenda_pedido_id', (int) $prendaId)
                    )
                    ->when(
                        $consecutivoParcial !== null && $consecutivoParcial !== '',
                        fn ($query) => $query->where('consecutivo_actual', (float) $consecutivoParcial)
                    )
                    ->whereNull('deleted_at')
                    ->first();

                if (!$parcialLegacy && $consecutivoParcial !== null && $consecutivoParcial !== '' && $prendaId !== null && $prendaId !== '') {
                    $parcialLegacy = DB::table('pedidos_parciales')
                        ->where('pedido_produccion_id', (int) $pedido->id)
                        ->where('prenda_pedido_id', (int) $prendaId)
                        ->where('consecutivo_actual', (float) $consecutivoParcial)
                        ->whereNull('deleted_at')
                        ->orderByDesc('id')
                        ->first();
                }

                if ($parcialLegacy) {
                    $tallasLegacy = DB::table('pedidos_parciales_tallas')
                        ->where('pedido_parcial_id', (int) $parcialLegacy->id)
                        ->get(['talla', 'cantidad', 'color_nombre']);

                    $parcialLegacyNormalizado = new \stdClass();
                    $parcialLegacyNormalizado->id = (int) $parcialLegacy->id;
                    $parcialLegacyNormalizado->pedido_produccion_id = (int) $parcialLegacy->pedido_produccion_id;
                    $parcialLegacyNormalizado->prenda_pedido_id = (int) $parcialLegacy->prenda_pedido_id;
                    $parcialLegacyNormalizado->tipo_recibo = (string) ($parcialLegacy->tipo_recibo ?? 'COSTURA');
                    $parcialLegacyNormalizado->consecutivo_parcial = (float) ($parcialLegacy->consecutivo_actual ?? 0);
                    $parcialLegacyNormalizado->consecutivo_original = (float) ($parcialLegacy->consecutivo_inicial ?? $parcialLegacy->consecutivo_actual ?? 0);
                    $parcialLegacyNormalizado->area = 'Costura';
                    $parcialLegacyNormalizado->encargado = null;
                    $parcialLegacyNormalizado->tallas = $tallasLegacy;

                    $parcial = $parcialLegacyNormalizado;
                }
            }

            if (!$parcial) {
                \Log::error('[ObtenerDatosRecibosOperarioUseCase] Parcial no encontrado', [
                    'parcial_id' => (int) $parcialId,
                    'consecutivo_parcial' => $consecutivoParcial !== null && $consecutivoParcial !== '' ? (float) $consecutivoParcial : null,
                    'prenda_id' => $prendaId !== null && $prendaId !== '' ? (int) $prendaId : null,
                    'pedido_id' => (int) $pedido->id,
                    'tipo_recibo' => $tipoRecibo,
                ]);

                return [
                    'status' => 404,
                    'payload' => [
                        'success' => false,
                        'error' => 'not found',
                        'message' => 'Parcial no encontrado',
                    ],
                ];
            }

            if ((int) $parcial->id !== (int) $parcialId) {
                \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Parcial resuelto por fallback de contexto', [
                    'parcial_id_recibido' => (int) $parcialId,
                    'parcial_id_resuelto' => (int) $parcial->id,
                    'consecutivo_parcial' => $consecutivoParcial !== null && $consecutivoParcial !== '' ? (float) $consecutivoParcial : null,
                    'prenda_id' => $prendaId !== null && $prendaId !== '' ? (int) $prendaId : null,
                    'pedido_id' => (int) $pedido->id,
                ]);
            }

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Parcial encontrado', [
                'parcial_id' => $parcial->id,
                'pedido_id' => $parcial->pedido_produccion_id,
                'prenda_id' => $parcial->prenda_pedido_id,
                'tallas_count' => $parcial->tallas?->count() ?? 0,
            ]);

            $datosPedido = $this->obtenerPedidoUseCase->ejecutar((int) $pedido->id, false);
            $responseData = $datosPedido->toArray();

            if (isset($responseData['prendas']) && $parcial->prenda_pedido_id) {
                $responseData['prendas'] = collect($responseData['prendas'])
                    ->filter(function ($prenda) use ($parcial) {
                        $id = $prenda['id'] ?? $prenda['prenda_id'] ?? $prenda['prenda_pedido_id'] ?? null;
                        return $id !== null && (int) $id === (int) $parcial->prenda_pedido_id;
                    })
                    ->map(function ($prenda) use ($parcial) {
                        // Cargar tallas desde la tabla correcta
                        $isReciboPorPartes = ($parcial instanceof \App\Models\ReciboPorPartes);
                        $tallasTable = $isReciboPorPartes ? 'recibos_por_partes_tallas' : 'pedidos_parciales_tallas';
                        $foreignKey = $isReciboPorPartes ? 'recibo_por_partes_id' : 'pedido_parcial_id';
                        $columns = $isReciboPorPartes ? ['talla', 'cantidad', 'color_nombre'] : ['genero', 'talla', 'cantidad', 'color_nombre'];

                        $tallasRaw = DB::table($tallasTable)
                            ->where($foreignKey, (int) $parcial->id)
                            ->get($columns);
                        
                        \Log::info('[NORMAL DEBUG 1] Tipo tallas raw: ' . gettype($tallasRaw));
                        \Log::info('[NORMAL DEBUG 2] Es Collection: ' . ($tallasRaw instanceof \Illuminate\Support\Collection ? 'si' : 'no'));
                        \Log::info('[NORMAL DEBUG 3] Contenido raw original: ' . json_encode($tallasRaw));
                        
                        // Convertir a array de forma segura (ya es Collection, así que solo normalizamos)
                        if ($tallasRaw instanceof \Illuminate\Support\Collection) {
                            \Log::info('[NORMAL DEBUG 4] Entrando rama Collection');
                            $tallasRaw = $tallasRaw->toArray();
                        } elseif (is_object($tallasRaw)) {
                            \Log::info('[NORMAL DEBUG 4] Entrando rama object');
                            $tallasRaw = json_decode(json_encode($tallasRaw), true) ?: [];
                        } elseif (!is_array($tallasRaw)) {
                            \Log::info('[NORMAL DEBUG 4] Entrando rama no-array');
                            $tallasRaw = [];
                        }
                        
                        \Log::info('[NORMAL DEBUG 5] Contenido después conversión: ' . json_encode($tallasRaw));
                        
                        $tallasParcial = collect($tallasRaw)->map(function ($talla) {
                            \Log::info('[NORMAL DEBUG 6] Procesando talla: ' . json_encode($talla));
                            
                            // Soportar tanto acceso como objeto como array
                            $genero = is_array($talla) ? ($talla['genero'] ?? 'UNISEX') : ($talla->genero ?? 'UNISEX');
                            $tallaNombre = is_array($talla) ? ($talla['talla'] ?? null) : ($talla->talla ?? null);
                            $cantidad = is_array($talla) ? ($talla['cantidad'] ?? 0) : ($talla->cantidad ?? 0);
                            $colorNombre = is_array($talla) ? ($talla['color_nombre'] ?? null) : ($talla->color_nombre ?? null);
                            
                            \Log::info('[NORMAL DEBUG 7] Genero extraído: ' . $genero);
                            
                            return [
                                'genero' => $genero,
                                'talla' => $tallaNombre,
                                'cantidad' => (int) $cantidad,
                                'color_nombre' => $colorNombre,
                            ];
                        })->values()->toArray();

                        // IMPORTANTE: en recibos parciales el front espera `prenda.tallas` como LISTA
                        // (para no mostrar tallas del recibo original).
                        $prenda['tallas'] = $tallasParcial;

                        // IMPORTANTE: algunas vistas/tarjetas usan `variantes` para renderizar la sección de tallas.
                        // En parciales se debe filtrar para NO devolver las tallas del recibo original.
                        $cantidadesPorTalla = [];
                        $cantidadesPorTallaYColor = [];
                        $generosPorTalla = []; // Mapear talla -> género para preservar info
                        foreach ($tallasParcial as $registro) {
                            $t = strtoupper(trim((string) ($registro['talla'] ?? '')));
                            $c = (int) ($registro['cantidad'] ?? 0);
                            $g = strtoupper(trim((string) ($registro['genero'] ?? 'UNISEX')));
                            $color = strtoupper(trim((string) ($registro['color_nombre'] ?? '')));
                            if ($t === '' || $c <= 0) {
                                continue;
                            }
                            $cantidadesPorTalla[$t] = ($cantidadesPorTalla[$t] ?? 0) + $c;
                            $generosPorTalla[$t] = $g; // Guardar género (último valor, normalmente es consistente)

                            if ($color !== '') {
                                if (!isset($cantidadesPorTallaYColor[$t])) {
                                    $cantidadesPorTallaYColor[$t] = [];
                                }
                                $cantidadesPorTallaYColor[$t][$color] = ($cantidadesPorTallaYColor[$t][$color] ?? 0) + $c;
                            }
                        }

                        // IMPORTANTÍSIMO: el front prioriza `prenda.talla_colores` en parciales.
                        // Si dejamos la talla_colores original, se muestran tallas del recibo original.
                        // Por eso, en parciales se sobreescribe con SOLO las tallas del parcial (con color si aplica).
                        if (!empty($cantidadesPorTallaYColor)) {
                            $telaNombre = $prenda['tela_nombre'] ?? $prenda['tela'] ?? null;
                            $tallaColoresParcial = [];
                            foreach ($cantidadesPorTallaYColor as $talla => $porColor) {
                                $generoParaTalla = $generosPorTalla[$talla] ?? 'UNISEX';
                                foreach ($porColor as $colorNombre => $cantidad) {
                                    $tallaColoresParcial[] = [
                                        'genero' => $generoParaTalla,
                                        'talla' => $talla,
                                        'tela_nombre' => $telaNombre,
                                        'color_nombre' => $colorNombre,
                                        'cantidad' => (int) $cantidad,
                                        'referencia' => null,
                                        'observaciones' => null,
                                        'imagen_ruta' => null,
                                    ];
                                }
                            }
                            $prenda['talla_colores'] = $tallaColoresParcial;
                        } else {
                            // Si el parcial no tiene colores, evitar que se use talla_colores del original.
                            $prenda['talla_colores'] = [];
                        }

                        if (isset($prenda['variantes']) && is_array($prenda['variantes'])) {
                            $variantesFiltradas = [];
                            $tallasUsadas = [];

                            foreach ($prenda['variantes'] as $variante) {
                                if (!is_array($variante)) {
                                    continue;
                                }
                                $tallaVariante = strtoupper(trim((string) ($variante['talla'] ?? '')));
                                if ($tallaVariante === '' || !array_key_exists($tallaVariante, $cantidadesPorTalla)) {
                                    continue;
                                }
                                $variante['cantidad'] = $cantidadesPorTalla[$tallaVariante];

                                // Recalcular desglose de colores para que coincida con el parcial.
                                if (isset($cantidadesPorTallaYColor[$tallaVariante]) && !empty($cantidadesPorTallaYColor[$tallaVariante])) {
                                    $detalle = [];
                                    $colorInfoParts = [];
                                    foreach ($cantidadesPorTallaYColor[$tallaVariante] as $colorNombre => $cantidadColor) {
                                        $detalle[] = [
                                            'talla_color_id' => null,
                                            'color' => $colorNombre,
                                            'cantidad' => (int) $cantidadColor,
                                        ];
                                        $colorInfoParts[] = ((int) $cantidadColor) . '-' . $colorNombre;
                                    }
                                    $variante['colores_detalle'] = $detalle;
                                    $variante['color_info'] = implode(', ', $colorInfoParts);
                                } else {
                                    $variante['colores_detalle'] = [];
                                    $variante['color_info'] = '';
                                }

                                $variantesFiltradas[] = $variante;
                                $tallasUsadas[$tallaVariante] = true;
                            }

                            // Si por algún motivo no venía la variante original para una talla del parcial, crear una mínima.
                            foreach ($cantidadesPorTalla as $talla => $cantidad) {
                                if (isset($tallasUsadas[$talla])) {
                                    continue;
                                }
                                $generoParaTalla = $generosPorTalla[$talla] ?? 'UNISEX';
                                $nueva = [
                                    'talla' => $talla,
                                    'genero' => $generoParaTalla,
                                    'cantidad' => $cantidad,
                                ];
                                if (isset($cantidadesPorTallaYColor[$talla]) && !empty($cantidadesPorTallaYColor[$talla])) {
                                    $detalle = [];
                                    $colorInfoParts = [];
                                    foreach ($cantidadesPorTallaYColor[$talla] as $colorNombre => $cantidadColor) {
                                        $detalle[] = [
                                            'talla_color_id' => null,
                                            'color' => $colorNombre,
                                            'cantidad' => (int) $cantidadColor,
                                        ];
                                        $colorInfoParts[] = ((int) $cantidadColor) . '-' . $colorNombre;
                                    }
                                    $nueva['colores_detalle'] = $detalle;
                                    $nueva['color_info'] = implode(', ', $colorInfoParts);
                                }
                                $variantesFiltradas[] = $nueva;
                            }

                            $prenda['variantes'] = array_values($variantesFiltradas);
                        }

                        // Además, muchos renderizadores usan `proceso.tallas` (estructura).
                        // Para parciales, sobreescribir `proceso.tallas` para que NO muestre las tallas del recibo original.
                        // Distribuir correctamente por género (DAMA, CABALLERO, UNISEX)
                        $dama = [];
                        $caballero = [];
                        $unisex = [];
                        foreach ($tallasParcial as $registro) {
                            $tallaNombre = strtoupper(trim((string) ($registro['talla'] ?? '')));
                            $cantidad = (int) ($registro['cantidad'] ?? 0);
                            $genero = strtoupper(trim((string) ($registro['genero'] ?? 'UNISEX')));
                            
                            if ($tallaNombre === '' || $cantidad <= 0) {
                                continue;
                            }
                            
                            if ($genero === 'DAMA') {
                                $dama[$tallaNombre] = ($dama[$tallaNombre] ?? 0) + $cantidad;
                            } elseif ($genero === 'CABALLERO') {
                                $caballero[$tallaNombre] = ($caballero[$tallaNombre] ?? 0) + $cantidad;
                            } else {
                                $unisex[$tallaNombre] = ($unisex[$tallaNombre] ?? 0) + $cantidad;
                            }
                        }

                        $tallasProceso = [
                            'dama' => $dama,
                            'caballero' => $caballero,
                            'unisex' => $unisex,
                        ];

                        if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                            $prenda['procesos'] = array_map(function ($proceso) use ($tallasProceso) {
                                if (is_array($proceso)) {
                                    $proceso['tallas'] = $tallasProceso;
                                }
                                return $proceso;
                            }, $prenda['procesos']);
                        }

                        $reciboKey = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
                        $observacionProceso = (string) (DB::table('observaciones_recibos_procesos')
                            ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                            ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                            ->where('tipo_proceso', $reciboKey)
                            ->value('observacion') ?? '');

                        // Mantener `recibos` como objeto (no array) para que el front lo detecte.
                        $prenda['recibos'] = [
                            $reciboKey => [
                                'id' => $parcial->id,
                                'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                                'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                                'consecutivo_original' => (float) $parcial->consecutivo_original,
                                'tipo_recibo' => $parcial->tipo_recibo,
                                'area' => $parcial->area,
                                'encargado' => $parcial->encargado,
                                'tallas' => $tallasParcial,
                                'observaciones' => $observacionProceso,
                            ],
                        ];

                        return $prenda;
                    })
                    ->values()
                    ->toArray();
            }

            // Fallback defensivo: si por alguna incompatibilidad del transformador la prenda queda vacía,
            // construir una prenda mínima para que el recibo parcial siempre renderice descripción/tallas.
            if (empty($responseData['prendas']) && $parcial->prenda_pedido_id) {
                $prendaEloquent = PrendaPedido::with(['coloresTelas.color', 'coloresTelas.tela', 'variantes.tipoManga', 'variantes.tipoBroche'])
                    ->find((int) $parcial->prenda_pedido_id);
                
                $coloresTelas = [];
                if ($prendaEloquent && $prendaEloquent->coloresTelas) {
                    foreach ($prendaEloquent->coloresTelas as $ct) {
                        $coloresTelas[] = [
                            'tela_nombre' => $ct->tela?->nombre,
                            'color_nombre' => $ct->color?->nombre,
                            'referencia' => $ct->referencia ?? $ct->tela?->referencia,
                        ];
                    }
                }

                $primerColorTela = $coloresTelas[0] ?? null;
                $variante = $prendaEloquent ? $prendaEloquent->variantes->first() : null;

                // Cargar tallas desde la tabla correcta
                $isReciboPorPartes = ($parcial instanceof \App\Models\ReciboPorPartes);
                $tallasTable = $isReciboPorPartes ? 'recibos_por_partes_tallas' : 'pedidos_parciales_tallas';
                $foreignKey = $isReciboPorPartes ? 'recibo_por_partes_id' : 'pedido_parcial_id';
                $columns = $isReciboPorPartes ? ['talla', 'cantidad', 'color_nombre'] : ['genero', 'talla', 'cantidad', 'color_nombre'];

                $tallasRaw = DB::table($tallasTable)
                    ->where($foreignKey, (int) $parcial->id)
                    ->get($columns);
                
                \Log::info('[FALLBACK DEBUG 1] Tipo tallas raw: ' . gettype($tallasRaw));
                \Log::info('[FALLBACK DEBUG 2] Es Collection: ' . ($tallasRaw instanceof \Illuminate\Support\Collection ? 'si' : 'no'));
                \Log::info('[FALLBACK DEBUG 3] Contenido raw original: ' . json_encode($tallasRaw));
                
                // Convertir a array de forma segura (ya es Collection, así que solo normalizamos)
                if ($tallasRaw instanceof \Illuminate\Support\Collection) {
                    \Log::info('[FALLBACK DEBUG 4] Entrando rama Collection');
                    $tallasRaw = $tallasRaw->toArray();
                } elseif (is_object($tallasRaw)) {
                    \Log::info('[FALLBACK DEBUG 4] Entrando rama object');
                    $tallasRaw = json_decode(json_encode($tallasRaw), true) ?: [];
                } elseif (!is_array($tallasRaw)) {
                    \Log::info('[FALLBACK DEBUG 4] Entrando rama no-array');
                    $tallasRaw = [];
                }
                
                \Log::info('[FALLBACK DEBUG 5] Contenido después conversión: ' . json_encode($tallasRaw));

                $tallasParcial = collect($tallasRaw)->map(function ($talla) {
                    \Log::info('[FALLBACK DEBUG 6] Procesando talla: ' . json_encode($talla));
                    
                    // Soportar tanto acceso como objeto como array
                    $genero = is_array($talla) ? ($talla['genero'] ?? 'UNISEX') : ($talla->genero ?? 'UNISEX');
                    $tallaNombre = is_array($talla) ? ($talla['talla'] ?? null) : ($talla->talla ?? null);
                    $cantidad = is_array($talla) ? ($talla['cantidad'] ?? 0) : ($talla->cantidad ?? 0);
                    $colorNombre = is_array($talla) ? ($talla['color_nombre'] ?? null) : ($talla->color_nombre ?? null);
                    
                    \Log::info('[FALLBACK DEBUG 7] Genero extraído: ' . $genero);
                    
                    return [
                        'genero' => $genero,
                        'talla' => $tallaNombre,
                        'cantidad' => $cantidad,
                        'color_nombre' => $colorNombre,
                    ];
                })->filter(fn ($r) => !empty($r['talla']) && (int) $r['cantidad'] > 0)->values()->toArray();

                // Distribuir tallas por género (DAMA, CABALLERO, UNISEX)
                $dama = [];
                $caballero = [];
                $unisex = [];
                foreach ($tallasParcial as $registro) {
                    $tallaNombre = strtoupper(trim((string) ($registro['talla'] ?? '')));
                    $cantidad = (int) ($registro['cantidad'] ?? 0);
                    $genero = strtoupper(trim((string) ($registro['genero'] ?? 'UNISEX')));
                    
                    if ($tallaNombre === '' || $cantidad <= 0) {
                        continue;
                    }
                    
                    if ($genero === 'DAMA') {
                        $dama[$tallaNombre] = ($dama[$tallaNombre] ?? 0) + $cantidad;
                    } elseif ($genero === 'CABALLERO') {
                        $caballero[$tallaNombre] = ($caballero[$tallaNombre] ?? 0) + $cantidad;
                    } else {
                        $unisex[$tallaNombre] = ($unisex[$tallaNombre] ?? 0) + $cantidad;
                    }
                }

                $reciboKey = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
                $observacionProceso = (string) (DB::table('observaciones_recibos_procesos')
                    ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                    ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                    ->where('tipo_proceso', $reciboKey)
                    ->value('observacion') ?? '');
                $responseData['prendas'] = [[
                    'id' => (int) $parcial->prenda_pedido_id,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'prenda_pedido_id' => (int) $parcial->prenda_pedido_id,
                    'nombre' => (string) ($prendaEloquent->nombre_prenda ?? 'PRENDA'),
                    'nombre_prenda' => (string) ($prendaEloquent->nombre_prenda ?? 'PRENDA'),
                    'descripcion' => (string) ($prendaEloquent->descripcion ?? ''),
                    'tela' => $primerColorTela['tela_nombre'] ?? null,
                    'color' => $primerColorTela['color_nombre'] ?? null,
                    'ref' => $primerColorTela['referencia'] ?? null,
                    'colores_telas' => $coloresTelas,
                    'manga' => $variante ? ($variante->tipoManga?->nombre ?? $variante->manga) : null,
                    'broche' => $variante ? ($variante->tipoBroche?->nombre ?? $variante->broche) : null,
                    'tallas' => $tallasParcial,
                    'talla_colores' => [],
                    'procesos' => [[
                        'proceso' => $reciboKey,
                        'tipo_proceso' => $reciboKey,
                        'es_parcial' => true,
                        'pedido_parcial_id' => (int) $parcial->id,
                        'tallas' => [
                            'dama' => $dama,
                            'caballero' => $caballero,
                            'unisex' => $unisex,
                        ],
                    ]],
                    'recibos' => [
                        $reciboKey => [
                            'id' => $parcial->id,
                            'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                            'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                            'consecutivo_original' => (float) $parcial->consecutivo_original,
                            'tipo_recibo' => $parcial->tipo_recibo,
                            'area' => $parcial->area ?? 'Costura',
                            'encargado' => $parcial->encargado ?? null,
                            'tallas' => $tallasParcial,
                            'observaciones' => $observacionProceso,
                        ],
                    ],
                ]];

                \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Prendas vacías en parcial, usando fallback mínimo', [
                    'parcial_id' => (int) $parcial->id,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'total_tallas' => count($tallasParcial),
                ]);
            }

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Respuesta de parcial enviada', [
                'keys' => array_keys($responseData),
                'tiene_prendas' => isset($responseData['prendas']),
                'total_prendas' => count($responseData['prendas'] ?? []),
            ]);

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'data' => $responseData,
                ],
            ];
        }

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Llamando ObtenerPedidoUseCase');
        $datosPedido = $this->obtenerPedidoUseCase->ejecutar((int) $pedido->id, false);
        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Datos obtenidos del UseCase');

        $responseData = $datosPedido->toArray();

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Respuesta enviada', [
            'keys' => array_keys($responseData),
            'tiene_prendas' => isset($responseData['prendas']),
            'total_prendas' => count($responseData['prendas'] ?? []),
        ]);

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'data' => $responseData,
            ],
        ];
    }
}
