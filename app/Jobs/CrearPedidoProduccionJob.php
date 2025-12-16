<?php

namespace App\Jobs;

use App\Models\PedidoProduccion;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\PrendaCreacionDTO;
use App\Services\Pedidos\PrendaProcessorService;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\PedidoLogoService;
use App\Application\Services\CopiarImagenesCotizacionAPedidoService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class CrearPedidoProduccionJob
{
    use Dispatchable, Queueable;

    public function __construct(
        private CrearPedidoProduccionDTO $dto,
        private int $asesorId,
        private array $prendas,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        PrendaProcessorService $prendaProcessor,
        PedidoPrendaService $prendaService,
        PedidoLogoService $logoService,
        CopiarImagenesCotizacionAPedidoService $copiarImagenesService
    ): PedidoProduccion {
        // Usar transacción para garantizar atomicidad
        return DB::transaction(function () use ($prendaProcessor, $prendaService, $logoService, $copiarImagenesService) {
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
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $this->dto->cotizacionId,
                'asesor_id' => $this->asesorId,
                'numero_pedido' => $numeroPedido,
                'cliente' => $this->dto->cliente,
                'cliente_id' => $this->dto->clienteId,
                'descripcion' => $this->dto->descripcion,
                'forma_de_pago' => $this->dto->formaDePago,
                'prendas' => $prendasProcesadas,
                'forma_de_pago' => $this->dto->formaDePago,
                'estado' => 'Pendiente',
            ]);

            // Guardar prendas en tablas normalizadas (DDD)
            // Convertir DTOs a arrays antes de guardar - CONVERSIÓN EXPLÍCITA
            if (!empty($this->prendas)) {
                $prendasArray = [];
                foreach ($this->prendas as $prenda) {
                    if ($prenda instanceof PrendaCreacionDTO) {
                        $prendasArray[] = $prenda->toArray();
                    } elseif (is_object($prenda) && method_exists($prenda, 'toArray')) {
                        $prendasArray[] = $prenda->toArray();
                    } else {
                        $prendasArray[] = $prenda;
                    }
                }
                $prendaService->guardarPrendasEnPedido($pedido, $prendasArray);
            }

            // Copiar imágenes de la cotización al pedido
            $copiarImagenesService->copiarImagenesCotizacionAPedido(
                $this->dto->cotizacionId,
                $pedido->id
            );

            // Guardar logo si existe (DDD)
            if (!empty($this->dto->logo)) {
                $logoService->guardarLogoEnPedido($pedido, $this->dto->logo);
            }

            return $pedido;
        });
    }
}
