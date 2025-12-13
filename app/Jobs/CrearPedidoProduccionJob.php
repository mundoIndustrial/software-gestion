<?php

namespace App\Jobs;

use App\Models\PedidoProduccion;
use App\DTOs\CrearPedidoProduccionDTO;
use App\Services\Pedidos\PrendaProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CrearPedidoProduccionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private CrearPedidoProduccionDTO $dto,
        private int $asesorId,
        private array $prendas,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PrendaProcessorService $prendaProcessor): PedidoProduccion
    {
        // Usar transacción para garantizar atomicidad
        return DB::transaction(function () use ($prendaProcessor) {
            // Obtener y incrementar número de pedido de forma segura
            $numeroPedido = DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->lockForUpdate()
                ->first()
                ->siguiente;

            // Incrementar para el próximo
            DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->increment('siguiente');

            // Procesar prendas
            $prendasProcesadas = array_map(
                fn($prenda) => $prendaProcessor->procesar($prenda),
                $this->prendas
            );

            // Crear pedido con número generado
            return PedidoProduccion::create([
                'cotizacion_id' => $this->dto->cotizacionId,
                'asesor_id' => $this->asesorId,
                'numero_pedido' => $numeroPedido,
                'prendas' => $prendasProcesadas,
                'estado' => 'Pendiente',
            ]);
        });
    }
}
