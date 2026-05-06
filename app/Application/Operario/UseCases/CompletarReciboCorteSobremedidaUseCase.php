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
    ) {}

    public function execute(int $idRecibo): ReciboCommandResultDTO
    {
        try {
            $usuario = Auth::user();

            // Validar que el usuario tenga rol administrador-costura
            if (!$usuario->hasRole('administrador-costura')) {
                return new ReciboCommandResultDTO(false, 'Rol no autorizado', 403);
            }

            // Buscar el recibo
            $recibo = $this->recibos->findActiveById((int) $idRecibo);

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado', 404);
            }

            // Validar que el recibo esté en área "Corte"
            $areaRecibo = trim((string) ($recibo->area ?? ''));
            if (strcasecmp($areaRecibo, 'Corte') !== 0) {
                return new ReciboCommandResultDTO(false, 'Este recibo no está en el área Corte', 403);
            }

            // Obtener el encargado del proceso de Corte asociado
            $encargadoCorte = null;
            if (!empty($recibo->prenda_id)) {
                $numeroPedido = $this->workflow->findNumeroPedidoByPrendaId((int) $recibo->prenda_id);
                
                if (!empty($numeroPedido)) {
                    $procesoCorte = $this->procesos->findLatestByProceso(
                        numeroPedido: (int) $numeroPedido,
                        prendaId: (int) $recibo->prenda_id,
                        proceso: 'Corte',
                    );
                    
                    if ($procesoCorte) {
                        $encargadoCorte = trim((string) ($procesoCorte->encargado ?? ''));
                    }
                }
            }
            
            if (empty($encargadoCorte)) {
                return new ReciboCommandResultDTO(false, 'El recibo no tiene encargado de Corte asignado', 422);
            }
            
            $nombreOperario = $encargadoCorte;

            // Ejecutar en transacción
            $this->workflow->runInTransaction(function () use ($recibo, $nombreOperario) {
                // 1. Crear registro en prenda_recibo_completado para Corte
                $this->workflow->upsertCompletado(
                    idRecibo: (int) $recibo->id,
                    idParcial: null,
                    area: 'Corte',
                    numeroRecibo: (string) ($recibo->consecutivo_actual ?? 0),
                    nombreOperario: $nombreOperario
                );

                // 2. Mover recibo a área "Costura"
                $recibo->area = 'Costura';
                $this->recibos->save($recibo);

                // 3. Crear registro en prenda_recibo_completado para Costura
                $this->workflow->upsertCompletado(
                    idRecibo: (int) $recibo->id,
                    idParcial: null,
                    area: 'Costura',
                    numeroRecibo: (string) ($recibo->consecutivo_actual ?? 0),
                    nombreOperario: $nombreOperario
                );

                // 4. Crear proceso de costura si no existe
                if (!empty($recibo->prenda_id)) {
                    $numeroPedido = $this->workflow->findNumeroPedidoByPrendaId((int) $recibo->prenda_id);

                    if (!empty($numeroPedido)) {
                        $procesoCostura = $this->procesos->findLatestByProceso(
                            numeroPedido: (int) $numeroPedido,
                            prendaId: (int) $recibo->prenda_id,
                            proceso: 'Costura',
                        );

                        if (!$procesoCostura) {
                            $this->procesos->create([
                                'numero_pedido' => $numeroPedido,
                                'prenda_pedido_id' => $recibo->prenda_id,
                                'numero_recibo' => $recibo->consecutivo_actual,
                                'proceso' => 'Costura',
                                'fecha_inicio' => now(),
                                'encargado' => $nombreOperario,
                                'estado_proceso' => 'Pendiente',
                                'codigo_referencia' => 'COS-' . ($recibo->consecutivo_actual ?? 0) . '-' . date('YmdHis'),
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
