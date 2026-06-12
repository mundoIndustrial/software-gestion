<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\CambiarAreaControlCalidadCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Services\ControlCalidadWorkflow;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaBodega;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaTallasBodega;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CambiarAreaControlCalidadUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ControlCalidadWorkflow $workflowService,
    ) {}

    private function construirClaveTalla(?string $talla, ?string $genero, ?string $colorNombre): string
    {
        return implode('|', [
            strtoupper(trim((string) $talla)),
            strtoupper(trim((string) $genero)),
            strtoupper(trim((string) $colorNombre)),
        ]);
    }

    private function normalizarTallasPorClave(array $tallas): array
    {
        $normalizadas = [];

        foreach ($tallas as $talla) {
            $clave = $this->construirClaveTalla(
                (string) ($talla['talla'] ?? ''),
                (string) ($talla['genero'] ?? ''),
                (string) ($talla['color_nombre'] ?? '')
            );

            if ($clave === '||') {
                continue;
            }

            $normalizadas[$clave] = ($normalizadas[$clave] ?? 0) + (int) ($talla['cantidad'] ?? 0);
        }

        return $normalizadas;
    }

    private function obtenerTallasOriginalesPrendaPedido(int $prendaId): array
    {
        return PrendaPedidoTalla::query()
            ->with('coloresAsignados')
            ->where('prenda_pedido_id', $prendaId)
            ->get()
            ->flatMap(function (PrendaPedidoTalla $talla) {
                if ($talla->coloresAsignados->isNotEmpty()) {
                    return $talla->coloresAsignados->map(function ($color) use ($talla) {
                        return [
                            'talla' => (string) ($talla->talla ?? ''),
                            'genero' => (string) ($talla->genero ?? ''),
                            'color_nombre' => (string) ($color->color_nombre ?? ''),
                            'cantidad' => (int) ($color->cantidad ?? 0),
                        ];
                    });
                }

                return [[
                    'talla' => (string) ($talla->talla ?? ''),
                    'genero' => (string) ($talla->genero ?? ''),
                    'color_nombre' => '',
                    'cantidad' => (int) $talla->obtenerCantidadTotal(),
                ]];
            })
            ->values()
            ->all();
    }

    private function obtenerTallasOriginalesPrendaBodega(int $prendaBodegaId): array
    {
        return PrendaTallasBodega::query()
            ->where('prenda_bodega_id', $prendaBodegaId)
            ->get()
            ->map(function (PrendaTallasBodega $talla) {
                return [
                    'talla' => (string) ($talla->talla ?? ''),
                    'genero' => (string) ($talla->genero ?? ''),
                    'color_nombre' => (string) ($talla->color ?? ''),
                    'cantidad' => (int) ($talla->cantidad ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function reciboQuedaCompletoEnControlCalidad(array $tallasOriginales, array $tallasControlCalidad): bool
    {
        $originalesPorClave = $this->normalizarTallasPorClave($tallasOriginales);
        $enviadasPorClave = $this->normalizarTallasPorClave($tallasControlCalidad);

        if (empty($originalesPorClave) || empty($enviadasPorClave)) {
            return false;
        }

        foreach ($originalesPorClave as $clave => $cantidadOriginal) {
            $cantidadEnviada = (int) ($enviadasPorClave[$clave] ?? 0);
            if ($cantidadEnviada < (int) $cantidadOriginal) {
                return false;
            }
        }

        return true;
    }

    private function esTallaSinDetalle(array $talla): bool
    {
        $nombreTalla = strtoupper(trim((string) ($talla['talla'] ?? '')));

        return $nombreTalla === '' || in_array($nombreTalla, ['SIN_TALLA', 'SIN TALLA', 'SIN-TALLA'], true);
    }

    private function soloManejaCantidad(array $tallas): bool
    {
        $tallasValidas = array_filter($tallas, function (array $talla) {
            return (int) ($talla['cantidad'] ?? 0) > 0;
        });

        if (empty($tallasValidas)) {
            return false;
        }

        foreach ($tallasValidas as $talla) {
            if (!$this->esTallaSinDetalle($talla)) {
                return false;
            }
        }

        return true;
    }

    private function calcularCantidadTotal(array $tallas): int
    {
        return (int) collect($tallas)->sum(function (array $talla) {
            return (int) ($talla['cantidad'] ?? 0);
        });
    }

    public function execute(CambiarAreaControlCalidadCommandDTO $cmd): ReciboCommandResultDTO
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                throw new \InvalidArgumentException('No tienes permisos para realizar esta accion');
            }

            if ($cmd->prendaBodegaId !== null) {
                return $this->executeBodega($cmd);
            }

            $pedido = $this->workflowService->findPedidoOrFail($cmd->pedidoId);

            Log::info('[CC] Buscando recibo para cambiar area', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'tipo_recibo' => $cmd->tipoRecibo,
                'numero_recibo' => $cmd->numeroRecibo,
            ]);

            // IMPORTANTE: priorizar el consecutivo exacto recibido desde UI para evitar
            // tomar otro recibo activo de la misma prenda/tipo (ej: 87 vs 106).
            $recibo = $this->recibos->findActiveByPedidoConsecutivoTipo(
                pedidoProduccionId: (int) $pedido->id,
                consecutivoActual: (int) $cmd->numeroRecibo,
                tipoRecibo: (string) $cmd->tipoRecibo,
            );

            if ($recibo && (int) $recibo->prenda_id !== (int) $cmd->prendaId) {
                Log::warning('[CC] Recibo por consecutivo no coincide con prenda solicitada', [
                    'pedido_id' => $pedido->id,
                    'consecutivo' => (int) $cmd->numeroRecibo,
                    'prenda_id_solicitada' => (int) $cmd->prendaId,
                    'prenda_id_recibo' => (int) $recibo->prenda_id,
                    'recibo_id' => (int) $recibo->id,
                ]);
                $recibo = null;
            }

            if (!$recibo) {
                $recibo = $this->recibos->findActiveByPedidoPrendaTipo(
                    pedidoProduccionId: (int) $pedido->id,
                    prendaId: (int) $cmd->prendaId,
                    tipoRecibo: (string) $cmd->tipoRecibo,
                );
            }

            if (!$recibo) {
                Log::error('[CC] Recibo no encontrado - diagnostico', [
                    'pedido_id' => $pedido->id,
                    'prenda_id_buscado' => $cmd->prendaId,
                    'tipo_buscado' => $cmd->tipoRecibo,
                ]);

                throw new \InvalidArgumentException('Recibo no encontrado');
            }

            [$nuevoProceso, $areaPosterior, $areaNueva, $estadoControlCalidad] = $this->workflowService->runInTransaction(function () use ($pedido, $cmd, $recibo) {
                $areaPosterior = $recibo->area;

                $nuevoProceso = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                    numeroPedido: (int) $pedido->numero_pedido,
                    prendaId: (int) $cmd->prendaId,
                    proceso: 'Control de Calidad',
                    numeroRecibo: (int) $recibo->consecutivo_actual,
                );

                if (!$nuevoProceso) {
                    $nuevoProceso = $this->procesos->create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $cmd->prendaId,
                        'numero_recibo' => $recibo->consecutivo_actual,
                        'proceso' => 'Control de Calidad',
                        'fecha_inicio' => now(),
                        'encargado' => 'control',
                        'estado_proceso' => 'En Progreso',
                        'codigo_referencia' => 'CC-' . $recibo->consecutivo_actual . '-' . date('YmdHis'),
                    ]);
                } else {
                    $this->procesos->update($nuevoProceso, [
                        'encargado' => $nuevoProceso->encargado ?: 'control',
                        'estado_proceso' => $nuevoProceso->estado_proceso ?: 'En Progreso',
                    ]);
                }

                $prenda = PrendaPedido::find($cmd->prendaId);
                $completadoExistente = DB::table('prenda_recibo_completado')
                    ->where('id_recibo', (int) $recibo->id)
                    ->where('area', 'Control Calidad')
                    ->first();
                $tallasAcumuladas = $this->acumularTallasControlCalidad(
                    $completadoExistente?->tallas_control_calidad,
                    $cmd->tallasControlCalidad
                );
                $tallasOriginales = $this->obtenerTallasOriginalesPrendaPedido((int) $cmd->prendaId);
                $reciboCompletoEnCc = $this->reciboQuedaCompletoEnControlCalidad($tallasOriginales, $tallasAcumuladas);
                $areaNueva = $reciboCompletoEnCc ? 'Control Calidad' : 'Costura';
                $estadoControlCalidad = $reciboCompletoEnCc ? 'completo' : 'parcial';

                $recibo->area = $areaNueva;
                $this->recibos->save($recibo);

            // Fuente de verdad de completado por área:
            // registrar explícitamente el paso a Control Calidad.
                DB::table('prenda_recibo_completado')->updateOrInsert(
                    [
                        'id_recibo' => (int) $recibo->id,
                        'area' => 'Control Calidad',
                    ],
                    [
                        'numero_recibo' => (int) ($recibo->consecutivo_actual ?? $cmd->numeroRecibo),
                        'nombre_operario' => (string) (auth()->user()->name ?? 'control'),
                        'fecha_completado' => now(),
                        'id_parcial' => null,
                        'tallas_control_calidad' => !empty($tallasAcumuladas)
                            ? json_encode(array_values($tallasAcumuladas), JSON_UNESCAPED_UNICODE)
                            : null,
                    ]
                );

                $completado = DB::table('prenda_recibo_completado')
                    ->where('id_recibo', (int) $recibo->id)
                    ->where('area', 'Control Calidad')
                    ->first('id');

                if ($completado) {
                    $this->guardarTallasControlCalidadDetalle((int) $completado->id, $tallasAcumuladas);
                }

            try {
                broadcast(new \App\Events\ReciboPasadoControlCalidad(
                    $pedido->id,
                    $cmd->prendaId,
                    $recibo->consecutivo_actual,
                    $prenda?->nombre_prenda ?? 'Prenda desconocida',
                    $cmd->tipoRecibo
                ));

                Log::info('Broadcast enviado a costureros - recibo pasado a Control Calidad', [
                    'pedido_id' => $pedido->id,
                    'prenda_id' => $cmd->prendaId,
                    'numero_recibo' => $recibo->consecutivo_actual,
                ]);
            } catch (\Exception $e) {
                Log::warning('Error al enviar broadcast a costureros', [
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                broadcast(new \App\Events\ControlCalidadUpdated([
                    'id' => (int) $recibo->id,
                    'pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'prenda_id' => (int) $cmd->prendaId,
                    'nombre_prenda' => $prenda?->nombre_prenda,
                    'descripcion' => $prenda?->descripcion,
                    'tipo_recibo' => (string) $cmd->tipoRecibo,
                    'consecutivo_actual' => (string) ($recibo->consecutivo_actual ?? ''),
                    'consecutivo_original' => (string) ($recibo->consecutivo_inicial ?? $recibo->consecutivo_actual ?? ''),
                    'es_parcial' => false,
                    'parcial_id' => null,
                    'completado_area' => false,
                    'area' => $areaNueva,
                    'proceso_actual' => $areaNueva,
                    'estado_control_calidad' => $estadoControlCalidad,
                    'fecha_creacion' => now()->toISOString(),
                    'numero_pedido' => $pedido->numero_pedido,
                ], 'added', 'pedido'));
            } catch (\Throwable $e) {
                Log::warning('[CC] Error al emitir ControlCalidadUpdated para recibo original', [
                    'pedido_id' => $pedido->id,
                    'prenda_id' => $cmd->prendaId,
                    'numero_recibo' => $recibo->consecutivo_actual,
                    'error' => $e->getMessage(),
                ]);
            }

                return [$nuevoProceso, $areaPosterior, $areaNueva, $estadoControlCalidad];
            });

            Log::info('Recibo enviado a Control Calidad', [
                'pedido_id' => $cmd->pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'numero_recibo' => $recibo->consecutivo_actual,
                'proceso_id' => $nuevoProceso->id,
                'usuario_id' => auth()->id(),
            ]);

            return new ReciboCommandResultDTO(true, 'Recibo enviado a Control Calidad correctamente', 200, [
                'proceso_id' => $nuevoProceso->id,
                'proceso_nombre' => 'Control de Calidad',
                'area_anterior' => $areaPosterior,
                'area_nueva' => $areaNueva,
                'estado_control_calidad' => $estadoControlCalidad,
            ]);

        } catch (\InvalidArgumentException $e) {
            return new ReciboCommandResultDTO(false, $e->getMessage(), 400);

        } catch (\Exception $e) {
            Log::error('Error cambiando area de recibo a Control Calidad', [
                'pedido_id' => $cmd->pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al cambiar el area: ' . $e->getMessage(), 500);
        }
    }

    private function executeBodega(CambiarAreaControlCalidadCommandDTO $cmd): ReciboCommandResultDTO
    {
        $prendaBodega = PrendaBodega::find($cmd->prendaBodegaId);

        Log::info('[CC][BODEGA] Buscando recibo para cambiar area', [
            'prenda_bodega_id' => $cmd->prendaBodegaId,
            'tipo_recibo' => $cmd->tipoRecibo,
            'numero_recibo' => $cmd->numeroRecibo,
        ]);

        $recibo = $this->recibos->findActiveByPedidoPrendaTipo(
            pedidoProduccionId: 0,
            prendaId: 0,
            tipoRecibo: (string) $cmd->tipoRecibo,
            prendaBodegaId: (int) $cmd->prendaBodegaId,
        );

        if (!$recibo) {
            $recibo = $this->recibos->findActiveByPedidoConsecutivoTipo(
                pedidoProduccionId: 0,
                consecutivoActual: (int) $cmd->numeroRecibo,
                tipoRecibo: (string) $cmd->tipoRecibo,
            );
        }

        if (!$recibo) {
            return new ReciboCommandResultDTO(false, 'Recibo no encontrado', 404);
        }

        [$nuevoProceso, $areaPosterior, $areaNueva, $estadoControlCalidad] = $this->workflowService->runInTransaction(function () use ($cmd, $recibo) {
            $areaPosterior = $recibo->area;

            $nuevoProceso = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                numeroPedido: 0,
                prendaId: 0,
                proceso: 'Control de Calidad',
                numeroRecibo: (int) $recibo->consecutivo_actual,
                prendaBodegaId: (int) $cmd->prendaBodegaId,
            );

            if (!$nuevoProceso) {
                $nuevoProceso = $this->procesos->create([
                    'numero_pedido' => null,
                    'prenda_pedido_id' => null,
                    'prenda_bodega_id' => (int) $cmd->prendaBodegaId,
                    'numero_recibo' => $recibo->consecutivo_actual,
                    'proceso' => 'Control de Calidad',
                    'fecha_inicio' => now(),
                    'encargado' => 'control',
                    'estado_proceso' => 'En Progreso',
                    'codigo_referencia' => 'CC-BOD-' . $recibo->consecutivo_actual . '-' . date('YmdHis'),
                ]);
            } else {
                $this->procesos->update($nuevoProceso, [
                    'encargado' => $nuevoProceso->encargado ?: 'control',
                    'estado_proceso' => $nuevoProceso->estado_proceso ?: 'En Progreso',
                ]);
            }

            $completadoExistente = DB::table('prenda_recibo_completado')
                ->where('id_recibo', (int) $recibo->id)
                ->where('area', 'Control Calidad')
                ->first();
            $tallasAcumuladas = $this->acumularTallasControlCalidad(
                $completadoExistente?->tallas_control_calidad,
                $cmd->tallasControlCalidad
            );
            $tallasOriginales = $this->obtenerTallasOriginalesPrendaBodega((int) $cmd->prendaBodegaId);
            $usaComparacionPorCantidad = $this->soloManejaCantidad($tallasOriginales)
                && $this->soloManejaCantidad($tallasAcumuladas);

            if ($usaComparacionPorCantidad) {
                $cantidadOriginal = $this->calcularCantidadTotal($tallasOriginales);
                $cantidadEnviada = $this->calcularCantidadTotal($tallasAcumuladas);
                $reciboCompletoEnCc = $cantidadOriginal > 0 && $cantidadEnviada >= $cantidadOriginal;
            } else {
                $reciboCompletoEnCc = $this->reciboQuedaCompletoEnControlCalidad($tallasOriginales, $tallasAcumuladas);
            }
            $areaNueva = $reciboCompletoEnCc ? 'Control Calidad' : 'Costura';
            $estadoControlCalidad = $reciboCompletoEnCc ? 'completo' : 'parcial';

            $recibo->area = $areaNueva;
            $this->recibos->save($recibo);

            DB::table('prenda_recibo_completado')->updateOrInsert(
                [
                    'id_recibo' => (int) $recibo->id,
                    'area' => 'Control Calidad',
                ],
                [
                    'numero_recibo' => (int) ($recibo->consecutivo_actual ?? $cmd->numeroRecibo),
                    'nombre_operario' => (string) (auth()->user()->name ?? 'control'),
                    'fecha_completado' => now(),
                    'id_parcial' => null,
                    'tallas_control_calidad' => !empty($tallasAcumuladas)
                        ? json_encode(array_values($tallasAcumuladas), JSON_UNESCAPED_UNICODE)
                        : null,
                ]
            );

            $completado = DB::table('prenda_recibo_completado')
                ->where('id_recibo', (int) $recibo->id)
                ->where('area', 'Control Calidad')
                ->first('id');

            if ($completado) {
                $this->guardarTallasControlCalidadDetalle((int) $completado->id, $tallasAcumuladas);
            }

            return [$nuevoProceso, $areaPosterior, $areaNueva, $estadoControlCalidad];
        });

        $nombrePrenda = $prendaBodega?->nombre ?? 'Prenda de bodega';

        try {
            broadcast(new \App\Events\ReciboPasadoControlCalidad(
                0,
                (int) $cmd->prendaBodegaId,
                $recibo->consecutivo_actual,
                $nombrePrenda,
                $cmd->tipoRecibo
            ));
        } catch (\Throwable $e) {
            Log::warning('[CC][BODEGA] Error al emitir ReciboPasadoControlCalidad', [
                'error' => $e->getMessage(),
            ]);
        }

        return new ReciboCommandResultDTO(true, 'Recibo enviado a Control Calidad correctamente', 200, [
            'proceso_id' => $nuevoProceso->id,
            'proceso_nombre' => 'Control de Calidad',
            'area_anterior' => $areaPosterior,
            'area_nueva' => $areaNueva,
            'estado_control_calidad' => $estadoControlCalidad,
            'prenda_bodega_id' => (int) $cmd->prendaBodegaId,
        ]);
    }

    private function guardarTallasControlCalidadDetalle(int $reciboCompletadoId, array $tallasControlCalidad): void
    {
        if ($reciboCompletadoId <= 0) {
            return;
        }

        $registros = collect($tallasControlCalidad)
            ->map(function (array $talla) use ($reciboCompletadoId) {
                $nombreTalla = trim((string) ($talla['talla'] ?? ''));
                $cantidad = (int) ($talla['cantidad'] ?? 0);

                return [
                    'prenda_recibo_completado_id' => $reciboCompletadoId,
                    'talla' => $nombreTalla,
                    'cantidad' => $cantidad,
                    'genero' => isset($talla['genero']) ? (string) $talla['genero'] : null,
                    'color_nombre' => isset($talla['color_nombre']) ? (string) $talla['color_nombre'] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->filter(fn (array $registro) => $registro['talla'] !== '' && $registro['cantidad'] > 0)
            ->values()
            ->all();

        if (!empty($registros)) {
            DB::table('prenda_recibo_completado_tallas')->insert($registros);
        }
    }

    private function acumularTallasControlCalidad(mixed $tallasExistentesRaw, array $tallasNuevas): array
    {
        $tallasExistentes = [];

        if (is_string($tallasExistentesRaw) && $tallasExistentesRaw !== '') {
            $decoded = json_decode($tallasExistentesRaw, true);
            $tallasExistentes = is_array($decoded) ? $decoded : [];
        } elseif (is_array($tallasExistentesRaw)) {
            $tallasExistentes = $tallasExistentesRaw;
        }

        $acumuladas = [];

        foreach (array_merge($tallasExistentes, $tallasNuevas) as $talla) {
            if (!is_array($talla)) {
                continue;
            }

            $nombreTalla = trim((string) ($talla['talla'] ?? ''));
            $genero = trim((string) ($talla['genero'] ?? ''));
            $colorNombre = trim((string) ($talla['color_nombre'] ?? ''));
            $cantidad = (int) ($talla['cantidad'] ?? 0);

            if ($nombreTalla === '' || $cantidad <= 0) {
                continue;
            }

            $clave = $this->construirClaveTalla($nombreTalla, $genero, $colorNombre);

            if (!isset($acumuladas[$clave])) {
                $acumuladas[$clave] = [
                    'talla' => $nombreTalla,
                    'cantidad' => 0,
                    'genero' => $genero,
                    'color_nombre' => $colorNombre,
                ];
            }

            $acumuladas[$clave]['cantidad'] += $cantidad;
        }

        return array_values($acumuladas);
    }
}
