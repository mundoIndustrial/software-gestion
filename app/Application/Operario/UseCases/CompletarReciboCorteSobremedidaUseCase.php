<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Services\ReciboOperarioWorkflow;
use Illuminate\Support\Facades\Auth;

class CompletarReciboCorteSobremedidaUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ReciboOperarioWorkflow $workflow,
        private readonly \App\Domain\Operario\Services\OperarioDashboardReadService $dashboardReadService,
    ) {}

    public function execute(int $idRecibo): ReciboCommandResultDTO
    {
        try {
            $usuario = Auth::user();

            // Validar que el usuario tenga rol administrador-costura
            if (!$usuario->hasRole('administrador-costura')) {
                return new ReciboCommandResultDTO(false, 'Rol no autorizado', 403);
            }

            // Buscar el recibo (Regular o Parcial/Anexo)
            $recibo = $this->recibos->findActiveById((int) $idRecibo);
            $esParcial = false;
            
            if (!$recibo) {
                $recibo = $this->workflow->findParcialById((int) $idRecibo, true);
                if ($recibo) {
                    $esParcial = true;
                }
            }

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado', 404);
            }

            // Validar que el recibo esté en área "Corte"
            // Nota: PedidoParcial no tiene campo area explícito, se asume por su estado/contexto
            $areaRecibo = $esParcial ? 'Corte' : trim((string) ($recibo->area ?? ''));
            if (!$esParcial && strcasecmp($areaRecibo, 'Corte') !== 0) {
                return new ReciboCommandResultDTO(false, 'Este recibo no está en el área Corte', 403);
            }

            $prendaId = (int) ($esParcial ? ($recibo->prenda_pedido_id ?? 0) : ($recibo->prenda_id ?? 0));
            $consecutivoActual = (string) ($recibo->consecutivo_actual ?? 0);
            $pedidoProduccionId = (int) ($recibo->pedido_produccion_id ?? 0);

            // Obtener el encargado (Priorizar el que sea Confección Sobremedida)
            $usuariosSobremedida = $this->dashboardReadService->obtenerUsuariosSobremedidaNormalizados();
            $nombreOperario = null;
            
            if ($prendaId > 0) {
                $numeroPedido = $this->workflow->findNumeroPedidoByPrendaId($prendaId);
                
                if (!empty($numeroPedido)) {
                    // Buscar procesos específicos de este recibo
                    $procesoCorte = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                        (int) $numeroPedido,
                        $prendaId,
                        'Corte',
                        (int) $consecutivoActual
                    );
                    
                    $procesoCostura = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                        (int) $numeroPedido,
                        $prendaId,
                        'Costura',
                        (int) $consecutivoActual
                    );

                    // 1. Priorizar el encargado de Corte si es de sobremedida
                    if ($procesoCorte && !empty($procesoCorte->encargado)) {
                        $enc = strtolower(trim((string)$procesoCorte->encargado));
                        if ($usuariosSobremedida->contains($enc)) {
                            $nombreOperario = $procesoCorte->encargado;
                        }
                    }

                    // 2. Si no, usar el encargado de Costura si es de sobremedida
                    if (empty($nombreOperario) && $procesoCostura && !empty($procesoCostura->encargado)) {
                        $enc = strtolower(trim((string)$procesoCostura->encargado));
                        if ($usuariosSobremedida->contains($enc)) {
                            $nombreOperario = $procesoCostura->encargado;
                        }
                    }
                    
            if (empty($nombreOperario)) {
                $procesoGen = \App\Models\ProcesoPrenda::where('numero_pedido', (int)$numeroPedido)
                    ->where('prenda_pedido_id', $prendaId)
                    ->whereIn('proceso', ['Corte', 'Costura'])
                    ->get()
                    ->first(function($p) use ($usuariosSobremedida) {
                        $enc = strtolower(trim((string)$p->encargado));
                        return $usuariosSobremedida->contains($enc);
                    });
                
                if ($procesoGen) {
                    $nombreOperario = $procesoGen->encargado;
                }
            }

            // 4. Fallback final: usar el que haya en Corte (comportamiento original)
            if (empty($nombreOperario) && isset($procesoCorte)) {
                $nombreOperario = $procesoCorte->encargado;
            }
        }
    }
    
    if (empty($nombreOperario)) {
        return new ReciboCommandResultDTO(false, 'No se encontró un encargado de Sobremedida asignado', 422);
    }

    // Ejecutar en transacción
    $this->workflow->runInTransaction(function () use ($recibo, $nombreOperario, $esParcial, $prendaId, $consecutivoActual) {
        // 1. Crear registro en prenda_recibo_completado para Corte
        $this->workflow->upsertCompletado(
            idRecibo: $esParcial ? null : (int) $recibo->id,
            idParcial: $esParcial ? (int) $recibo->id : null,
            area: 'Corte',
            numeroRecibo: $consecutivoActual,
            nombreOperario: $nombreOperario
        );

        // 2. Mover recibo a área "Costura"
        if (!$esParcial) {
            $recibo->area = 'Costura';
            $this->recibos->save($recibo);
        } else {
            // Para PedidoParcial, si estuviera en algún estado de Corte, lo actualizamos si aplica
            // pero normalmente se maneja por la tabla prenda_recibo_completado
        }

        // 3. Crear registro en prenda_recibo_completado para Costura
        $this->workflow->upsertCompletado(
            idRecibo: $esParcial ? null : (int) $recibo->id,
            idParcial: $esParcial ? (int) $recibo->id : null,
            area: 'Costura',
            numeroRecibo: $consecutivoActual,
            nombreOperario: $nombreOperario
        );

        // 4. Crear proceso de costura si no existe
        if ($prendaId > 0) {
            $numeroPedido = $this->workflow->findNumeroPedidoByPrendaId($prendaId);

            if (!empty($numeroPedido)) {
                $procesoCostura = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                    (int) $numeroPedido,
                    $prendaId,
                    'Costura',
                    (int) $consecutivoActual
                );

                if (!$procesoCostura) {
                    $this->procesos->create([
                        'numero_pedido' => $numeroPedido,
                        'prenda_pedido_id' => $prendaId,
                        'numero_recibo' => (int) $consecutivoActual,
                        'proceso' => 'Costura',
                        'fecha_inicio' => now(),
                        'encargado' => $nombreOperario,
                        'estado_proceso' => 'Pendiente',
                        'codigo_referencia' => 'COS-' . $consecutivoActual . '-' . date('YmdHis'),
                    ]);
                } else {
                    // Si ya existe, actualizar el encargado
                    $this->procesos->update($procesoCostura, [
                        'encargado' => $nombreOperario,
                    ]);
                }
            }
        }
    });

            return new ReciboCommandResultDTO(
                true,
                'Recibo completado en Corte y movido a Costura',
                200,
                [
                    'recibo_id' => (int) $recibo->id,
                    'consecutivo' => (string) ($recibo->consecutivo_actual ?? 0),
                    'area_anterior' => 'Corte',
                    'area_nueva' => 'Costura',
                    'nombre_operario' => $nombreOperario,
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Error al completar recibo en Corte (sobremedida): ' . $e->getMessage(), [
                'id_recibo' => $idRecibo,
                'exception' => $e,
            ]);

            return new ReciboCommandResultDTO(false, 'Error al completar el recibo', 500);
        }
    }
}
