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
        $reciboId = $request->query('recibo_id');

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] INICIO', [
            'numero_pedido' => $numeroPedido,
            'tipo_recibo' => $tipoRecibo,
            'parcial_id' => $parcialId,
            'consecutivo_parcial' => $consecutivoParcial,
            'recibo_id' => $reciboId,
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
            'recibo_id' => $reciboId,
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

        // Si se proporciona recibo_id, filtrar por ese recibo específico
        if ($reciboId) {
            $reciboEspecifico = DB::table('consecutivos_recibos_pedidos')
                ->where('id', (int) $reciboId)
                ->where('pedido_produccion_id', (int) $pedido->id)
                ->first(['prenda_id', 'tipo_recibo']);

            if ($reciboEspecifico) {
                // Actualizar los parámetros para filtrar por este recibo específico
                $tipoRecibo = (string) $reciboEspecifico->tipo_recibo;
                if ($reciboEspecifico->prenda_id) {
                    $request->merge(['prenda_id' => (int) $reciboEspecifico->prenda_id]);
                }
                
                \Log::info('[ObtenerDatosRecibosOperarioUseCase] Recibo específico encontrado', [
                    'recibo_id' => $reciboId,
                    'prenda_id' => $reciboEspecifico->prenda_id,
                    'tipo_recibo' => $tipoRecibo,
                ]);
            }
        }

        if ($parcialId) {
            $prendaId = $request->query('prenda_id');
            $generoBase = 'CABALLERO';

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

            // Fecha real de activación del recibo (tabla consecutivos_recibos_pedidos.created_at)
            // para mostrarla en la vista de detalle del parcial.
            $tipoReciboParcial = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
            $consecutivoParcialInt = (int) round((float) ($parcial->consecutivo_parcial ?? 0));
            $consecutivoOriginalInt = (int) round((float) ($parcial->consecutivo_original ?? 0));

            // Prioridad 1: recibo del anexo identificado por parcial_id en notas.
            $baseReciboActivacionQuery = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', (int) $pedido->id)
                ->where('prenda_id', (int) $parcial->prenda_pedido_id)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoReciboParcial]);

            $reciboActivacion = (clone $baseReciboActivacionQuery)
                ->where('notas', 'like', '%parcial_id:' . (int) $parcial->id . '%')
                ->orderByDesc('created_at')
                ->first(['id', 'created_at']);

            // Prioridad 2: consecutivo del parcial (ej: 55 en tu caso).
            if (!$reciboActivacion && $consecutivoParcialInt > 0) {
                $reciboActivacion = (clone $baseReciboActivacionQuery)
                    ->where('consecutivo_actual', $consecutivoParcialInt)
                    ->orderByDesc('created_at')
                    ->first(['id', 'created_at']);
            }

            // Prioridad 3: consecutivo original como último fallback.
            if (!$reciboActivacion && $consecutivoOriginalInt > 0) {
                $reciboActivacion = (clone $baseReciboActivacionQuery)
                    ->where('consecutivo_actual', $consecutivoOriginalInt)
                    ->orderByDesc('created_at')
                    ->first(['id', 'created_at']);
            }

            $fechaActivacionRecibo = $reciboActivacion?->created_at
                ? (string) $reciboActivacion->created_at
                : null;

            $datosPedido = $this->obtenerPedidoUseCase->ejecutar((int) $pedido->id, false);
            $responseData = $datosPedido->toArray();

            if (isset($responseData['prendas']) && $parcial->prenda_pedido_id) {
                $responseData['prendas'] = collect($responseData['prendas'])
                    ->filter(function ($prenda) use ($parcial) {
                        $id = $prenda['id'] ?? $prenda['prenda_id'] ?? $prenda['prenda_pedido_id'] ?? null;
                        return $id !== null && (int) $id === (int) $parcial->prenda_pedido_id;
                    })
                    ->map(function ($prenda) use ($parcial, $generoBase, $fechaActivacionRecibo, $pedido) {
                        $generoPrenda = strtoupper(trim((string) (
                            $prenda['genero']
                            ?? $prenda['tipo_flujo_tallas']
                            ?? $prenda['genero_principal']
                            ?? 'CABALLERO'
                        )));
                        if (!in_array($generoPrenda, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                            $generoPrenda = $generoBase;
                        }

                        // Cargar tallas desde la tabla correcta
                        $isReciboPorPartes = ($parcial instanceof \App\Models\ReciboPorPartes);
                        $tallasTable = $isReciboPorPartes ? 'recibos_por_partes_tallas' : 'pedidos_parciales_tallas';
                        $foreignKey = $isReciboPorPartes ? 'recibo_por_partes_id' : 'pedido_parcial_id';
                        $columns = $isReciboPorPartes ? ['talla', 'cantidad', 'color_nombre', 'genero'] : ['genero', 'talla', 'cantidad', 'color_nombre'];

                        $tallasRaw = DB::table($tallasTable)
                            ->where($foreignKey, (int) $parcial->id)
                            ->get($columns);

                        // Fallback de compatibilidad:
                        // Solo si no se encontraron tallas en la tabla principal, intentar buscar en la tabla legacy
                        // por contexto (no por ID directo si es ReciboPorPartes para evitar colisiones).
                        if ($tallasRaw->isEmpty()) {
                            $tipoReciboParcial = strtoupper(trim((string) ($parcial->tipo_recibo ?? '')));
                            $legacyParcial = DB::table('pedidos_parciales')
                                ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                                ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                                ->where('consecutivo_actual', (float) $parcial->consecutivo_parcial)
                                ->when(
                                    $tipoReciboParcial !== '',
                                    fn ($q) => $q->whereRaw('UPPER(tipo_recibo) = ?', [$tipoReciboParcial])
                                )
                                ->whereNull('deleted_at')
                                ->orderByDesc('id')
                                ->first(['id']);

                            if ($legacyParcial?->id) {
                                $tallasRaw = DB::table('pedidos_parciales_tallas')
                                    ->where('pedido_parcial_id', (int) $legacyParcial->id)
                                    ->get(['genero', 'talla', 'cantidad', 'color_nombre']);
                            }
                        }
                        
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
                        
                        $tallasParcial = collect($tallasRaw)->map(function ($talla) use ($generoPrenda) {
                            \Log::info('[NORMAL DEBUG 6] Procesando talla: ' . json_encode($talla));
                            
                            // Soportar tanto acceso como objeto como array
                            $genero = is_array($talla) ? ($talla['genero'] ?? null) : ($talla->genero ?? null);
                            $genero = strtoupper(trim((string) ($genero ?: $generoPrenda)));
                            if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                                $genero = $generoPrenda;
                            }
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

                        $reciboKey = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
                        $observacionProceso = (string) (DB::table('observaciones_recibos_procesos')
                            ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                            ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                            ->where('tipo_proceso', $reciboKey)
                            ->value('observacion') ?? '');

                        $tallaColoresParcial = [];
                        if (!empty($cantidadesPorTallaYColor)) {
                            $telaNombre = $prenda['tela_nombre'] ?? $prenda['tela'] ?? null;
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
                        }

                        // IMPORTANTE: Si no hay colores pero SÍ hay tallas en el parcial,
                        // construir talla_colores desde las tallas del parcial, NO desde el recibo original.
                        if (empty($tallaColoresParcial) && !empty($tallasParcial)) {
                            $telaNombre = $prenda['tela_nombre'] ?? $prenda['tela'] ?? null;
                            foreach ($tallasParcial as $tallaParcial) {
                                $genero = strtoupper(trim((string) ($tallaParcial['genero'] ?? 'UNISEX')));
                                $talla = strtoupper(trim((string) ($tallaParcial['talla'] ?? '')));
                                $cantidad = (int) ($tallaParcial['cantidad'] ?? 0);
                                if ($talla === '' || $cantidad <= 0) {
                                    continue;
                                }
                                $tallaColoresParcial[] = [
                                    'genero' => $genero,
                                    'talla' => $talla,
                                    'tela_nombre' => $telaNombre,
                                    'color_nombre' => null,
                                    'cantidad' => $cantidad,
                                    'referencia' => null,
                                    'observaciones' => null,
                                    'imagen_ruta' => null,
                                ];
                            }
                        } elseif (empty($tallaColoresParcial)) {
                            // Solo si NO hay tallas en el parcial, usar el fallback del recibo original
                            $tallaColoresParcial = $this->construirTallaColoresDesdePrenda(
                                (int) ($parcial->prenda_pedido_id ?? 0),
                                $tallasParcial,
                                $prenda['tela_nombre'] ?? $prenda['tela'] ?? null
                            );
                        }

                        $reciboParcialData = [
                            'id' => $parcial->id,
                            'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                            'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                            'consecutivo_original' => (float) $parcial->consecutivo_original,
                            'tipo_recibo' => 'PARCIAL',
                            'area' => $parcial->area,
                            'encargado' => $this->obtenerEncargadoDelParcial($parcial, $pedido),
                            'tallas' => $tallasParcial,
                            'tallas_estructura' => $tallasProceso,
                            'talla_colores' => $tallaColoresParcial,
                            'observaciones' => $observacionProceso,
                            'es_parcial' => true,
                            'pedido_parcial_id' => (int) $parcial->id,
                            'fecha_activacion_recibo' => $fechaActivacionRecibo,
                            'created_at' => $fechaActivacionRecibo,
                        ];

                        $prenda['procesos'] = [[
                            'proceso' => $reciboKey,
                            'tipo_proceso' => $reciboKey,
                            'nombre_proceso' => $reciboKey,
                            'es_parcial' => true,
                            'pedido_parcial_id' => (int) $parcial->id,
                            'created_at' => $fechaActivacionRecibo,
                            'tallas' => $tallasProceso,
                            'talla_colores' => $tallaColoresParcial,
                            'ubicaciones' => null,
                            'observaciones' => $observacionProceso,
                        ]];

                        // Mantener `recibos` como objeto (no array) para que el front lo detecte.
                        $prenda['recibos'] = [
                            $reciboKey => array_merge($reciboParcialData, ['tipo_recibo' => $reciboKey]),
                            'PARCIAL' => $reciboParcialData,
                        ];

                        return $prenda;
                    })
                    ->values()
                    ->toArray();
            }

            $responseData['fecha_activacion_recibo'] = $fechaActivacionRecibo;

            // Fallback defensivo: si por alguna incompatibilidad del transformador la prenda queda vacía,
            // construir una prenda mínima para que el recibo parcial siempre renderice descripción/tallas.
            if (empty($responseData['prendas']) && $parcial->prenda_pedido_id) {
                $prendaEloquent = PrendaPedido::with(['coloresTelas.color', 'coloresTelas.tela', 'variantes.tipoManga', 'variantes.tipoBroche'])
                    ->find((int) $parcial->prenda_pedido_id);

                $generoPrenda = strtoupper(trim((string) (
                    $prendaEloquent?->genero
                    ?? $prendaEloquent?->tipo_flujo_tallas
                    ?? 'CABALLERO'
                )));
                if (!in_array($generoPrenda, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                    $generoPrenda = 'CABALLERO';
                }
                
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
                $columns = $isReciboPorPartes ? ['talla', 'cantidad', 'color_nombre', 'genero'] : ['genero', 'talla', 'cantidad', 'color_nombre'];

                $tallasRaw = DB::table($tallasTable)
                    ->where($foreignKey, (int) $parcial->id)
                    ->get($columns);

                // Fallback de compatibilidad:
                // Solo si no se encontraron tallas en la tabla principal, intentar buscar en la tabla legacy
                // por contexto (no por ID directo si es ReciboPorPartes para evitar colisiones).
                if ($tallasRaw->isEmpty()) {
                    $tipoReciboParcial = strtoupper(trim((string) ($parcial->tipo_recibo ?? '')));
                    $legacyParcial = DB::table('pedidos_parciales')
                        ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                        ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                        ->where('consecutivo_actual', (float) $parcial->consecutivo_parcial)
                        ->when(
                            $tipoReciboParcial !== '',
                            fn ($q) => $q->whereRaw('UPPER(tipo_recibo) = ?', [$tipoReciboParcial])
                        )
                        ->whereNull('deleted_at')
                        ->orderByDesc('id')
                        ->first(['id']);

                    if ($legacyParcial?->id) {
                        $tallasRaw = DB::table('pedidos_parciales_tallas')
                            ->where('pedido_parcial_id', (int) $legacyParcial->id)
                            ->get(['genero', 'talla', 'cantidad', 'color_nombre']);
                    }
                }
                
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

                $tallasParcial = collect($tallasRaw)->map(function ($talla) use ($generoPrenda) {
                    \Log::info('[FALLBACK DEBUG 6] Procesando talla: ' . json_encode($talla));
                    
                    // Soportar tanto acceso como objeto como array
                    $genero = is_array($talla) ? ($talla['genero'] ?? null) : ($talla->genero ?? null);
                    $genero = strtoupper(trim((string) ($genero ?: $generoPrenda)));
                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                        $genero = $generoPrenda;
                    }
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
                $cantidadesPorTallaYColor = [];
                $generosPorTalla = [];

                foreach ($tallasParcial as $registro) {
                    $tallaNombre = strtoupper(trim((string) ($registro['talla'] ?? '')));
                    $cantidad = (int) ($registro['cantidad'] ?? 0);
                    $genero = strtoupper(trim((string) ($registro['genero'] ?? 'UNISEX')));
                    $color = strtoupper(trim((string) ($registro['color_nombre'] ?? '')));
                    
                    if ($tallaNombre === '' || $cantidad <= 0) {
                        continue;
                    }
                    
                    $generosPorTalla[$tallaNombre] = $genero;

                    if ($genero === 'DAMA') {
                        $dama[$tallaNombre] = ($dama[$tallaNombre] ?? 0) + $cantidad;
                    } elseif ($genero === 'CABALLERO') {
                        $caballero[$tallaNombre] = ($caballero[$tallaNombre] ?? 0) + $cantidad;
                    } else {
                        $unisex[$tallaNombre] = ($unisex[$tallaNombre] ?? 0) + $cantidad;
                    }

                    if ($color !== '') {
                        if (!isset($cantidadesPorTallaYColor[$tallaNombre])) {
                            $cantidadesPorTallaYColor[$tallaNombre] = [];
                        }
                        $cantidadesPorTallaYColor[$tallaNombre][$color] = ($cantidadesPorTallaYColor[$tallaNombre][$color] ?? 0) + $cantidad;
                    }
                }

                $tallasProceso = [
                    'dama' => $dama,
                    'caballero' => $caballero,
                    'unisex' => $unisex,
                ];

                $reciboKey = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
                $observacionProceso = (string) (DB::table('observaciones_recibos_procesos')
                    ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                    ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                    ->where('tipo_proceso', $reciboKey)
                    ->value('observacion') ?? '');

                $tallaColoresParcial = [];
                if (!empty($cantidadesPorTallaYColor)) {
                    $telaNombre = $coloresTelas[0]['tela_nombre'] ?? null;
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
                }

                if (empty($tallaColoresParcial)) {
                    $tallaColoresParcial = $this->construirTallaColoresDesdePrenda(
                        (int) ($parcial->prenda_pedido_id ?? 0),
                        $tallasParcial,
                        $coloresTelas[0]['tela_nombre'] ?? null
                    );
                }

                $reciboParcialData = [
                    'id' => $parcial->id,
                    'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                    'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                    'consecutivo_original' => (float) $parcial->consecutivo_original,
                    'tipo_recibo' => 'PARCIAL',
                    'area' => $parcial->area ?? 'Costura',
                    'encargado' => $this->obtenerEncargadoDelParcial($parcial, $pedido),
                    'tallas' => $tallasParcial,
                    'tallas_estructura' => $tallasProceso,
                    'talla_colores' => $tallaColoresParcial,
                    'observaciones' => $observacionProceso,
                    'es_parcial' => true,
                    'pedido_parcial_id' => (int) $parcial->id,
                    'fecha_activacion_recibo' => $fechaActivacionRecibo,
                    'created_at' => $fechaActivacionRecibo,
                ];

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
                    'talla_colores' => $tallaColoresParcial,
                    'procesos' => [[
                        'proceso' => $reciboKey,
                        'tipo_proceso' => $reciboKey,
                        'nombre_proceso' => $reciboKey,
                        'es_parcial' => true,
                        'pedido_parcial_id' => (int) $parcial->id,
                        'created_at' => $fechaActivacionRecibo,
                        'tallas' => [
                            'dama' => $dama,
                            'caballero' => $caballero,
                            'unisex' => $unisex,
                        ],
                        'talla_colores' => $tallaColoresParcial,
                    ]],
                    'recibos' => [
                        $reciboKey => array_merge($reciboParcialData, ['tipo_recibo' => $reciboKey]),
                        'PARCIAL' => $reciboParcialData,
                    ],
                ]];

                $responseData['fecha_activacion_recibo'] = $fechaActivacionRecibo;

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

        // Si se proporcionó recibo_id, filtrar las prendas para mostrar solo la del recibo específico
        $prendaIdParam = $request->query('prenda_id');
        if ($reciboId && $prendaIdParam) {
            $prendaIdParam = (int) $prendaIdParam;
            
            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Filtrando prendas por recibo_id', [
                'recibo_id' => $reciboId,
                'prenda_id' => $prendaIdParam,
                'prendas_antes' => count($responseData['prendas'] ?? []),
            ]);
            
            // Filtrar prendas para mostrar solo la del recibo específico
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                $responseData['prendas'] = array_values(array_filter(
                    $responseData['prendas'],
                    fn($prenda) => (int) ($prenda['id'] ?? $prenda['prenda_id'] ?? 0) === $prendaIdParam
                ));
            }
            
            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Prendas después del filtro', [
                'prendas_despues' => count($responseData['prendas'] ?? []),
            ]);
        }

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

    /**
     * Reconstruye talla_colores del parcial usando la distribución de colores de la prenda base.
     *
     * @param int $prendaPedidoId
     * @param array<int,array{genero:string,talla:string,cantidad:int,color_nombre:mixed}> $tallasParcial
     * @param string|null $telaNombre
     * @return array<int,array<string,mixed>>
     */
    private function construirTallaColoresDesdePrenda(int $prendaPedidoId, array $tallasParcial, ?string $telaNombre = null): array
    {
        if ($prendaPedidoId <= 0 || empty($tallasParcial)) {
            return [];
        }

        $rows = DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaPedidoId)
            ->select(['ppt.genero', 'ppt.talla', 'pptc.color_nombre', 'pptc.cantidad'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $fuente = [];
        foreach ($rows as $row) {
            $genero = strtoupper(trim((string) ($row->genero ?? '')));
            $talla = strtoupper(trim((string) ($row->talla ?? '')));
            $color = trim((string) ($row->color_nombre ?? ''));
            $cantidad = (int) ($row->cantidad ?? 0);
            if ($genero === '' || $talla === '' || $color === '' || $cantidad <= 0) {
                continue;
            }
            $key = $genero . '|' . $talla;
            $fuente[$key][] = ['color' => $color, 'cantidad' => $cantidad];
        }

        if (empty($fuente)) {
            return [];
        }

        $resultado = [];
        foreach ($tallasParcial as $tallaParcial) {
            $genero = strtoupper(trim((string) ($tallaParcial['genero'] ?? 'UNISEX')));
            $talla = strtoupper(trim((string) ($tallaParcial['talla'] ?? '')));
            $cantidadObjetivo = (int) ($tallaParcial['cantidad'] ?? 0);
            if ($talla === '' || $cantidadObjetivo <= 0) {
                continue;
            }

            $key = $genero . '|' . $talla;
            $coloresFuente = $fuente[$key] ?? [];
            if (empty($coloresFuente)) {
                continue;
            }

            $sumaFuente = array_sum(array_map(static fn ($c) => (int) ($c['cantidad'] ?? 0), $coloresFuente));
            if ($sumaFuente <= 0) {
                continue;
            }

            $distribucion = [];
            $asignado = 0;
            $residuos = [];
            foreach ($coloresFuente as $idx => $cf) {
                $exacto = ($cantidadObjetivo * (int) $cf['cantidad']) / $sumaFuente;
                $base = (int) floor($exacto);
                $distribucion[$idx] = $base;
                $asignado += $base;
                $residuos[$idx] = $exacto - $base;
            }

            $faltante = $cantidadObjetivo - $asignado;
            if ($faltante > 0) {
                arsort($residuos);
                foreach (array_keys($residuos) as $idx) {
                    if ($faltante <= 0) {
                        break;
                    }
                    $distribucion[$idx] = ($distribucion[$idx] ?? 0) + 1;
                    $faltante--;
                }
            }

            foreach ($coloresFuente as $idx => $cf) {
                $cantidadColor = (int) ($distribucion[$idx] ?? 0);
                if ($cantidadColor <= 0) {
                    continue;
                }
                $resultado[] = [
                    'genero' => $genero,
                    'talla' => $talla,
                    'tela_nombre' => $telaNombre,
                    'color_nombre' => (string) $cf['color'],
                    'cantidad' => $cantidadColor,
                    'referencia' => null,
                    'observaciones' => null,
                    'imagen_ruta' => null,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Obtener el encargado del parcial desde procesos_prenda
     * Busca primero en procesos_prenda, luego en el campo encargado del parcial
     */
    private function obtenerEncargadoDelParcial($parcial, $pedido): ?string
    {
        try {
            // Obtener el número de recibo del parcial
            $numeroRecibo = $parcial->consecutivo_actual ?? $parcial->consecutivo_parcial;
            
            if (!$numeroRecibo || !$pedido) {
                return $parcial->encargado ?? null;
            }

            // Buscar proceso en procesos_prenda con numero_recibo = consecutivo del parcial
            $proceso = DB::table('procesos_prenda')
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->where('numero_recibo', $numeroRecibo)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->whereNull('numero_recibo_parcial')  // Asegurar que es un proceso de anexo, no de parcial
                ->whereNull('deleted_at')
                ->orderByDesc('fecha_de_asignacion_encargado')
                ->orderByDesc('created_at')
                ->value('encargado');

            // Si encontró encargado en procesos_prenda, devolverlo
            if ($proceso) {
                return $proceso;
            }

            // Fallback: usar el campo encargado del parcial
            return $parcial->encargado ?? null;
        } catch (\Exception $e) {
            \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Error obteniendo encargado del parcial', [
                'parcial_id' => $parcial->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return $parcial->encargado ?? null;
        }
    }
}
