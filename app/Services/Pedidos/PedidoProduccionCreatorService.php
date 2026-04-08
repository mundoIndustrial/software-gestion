<?php

namespace App\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\PrendaCreacionDTO;
use App\Jobs\CrearPedidoProduccionJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

/**
 * Service para creación de pedidos de producción
 * LSP: Liskov Substitution - extensible para diferentes tipos de pedidos
 */
class PedidoProduccionCreatorService
{
    public function __construct(
        private PrendaProcessorService $prendaProcessor,
    ) {}

    /**
     * Crea un nuevo pedido de producción
     * Ejecuta sincronamente pero con protección de transacción y lock para números secuenciales
     */
    public function crear(CrearPedidoProduccionDTO $dto, int $asesorId): ?PedidoProduccion
    {
        \Log::info(' [PedidoProduccionCreatorService] ===== INICIO SERVICIO CREAR =====');
        \Log::info(' [PedidoProduccionCreatorService] Datos recibidos', [
            'dto_forma_de_pago' => $dto->formaDePago,
            'dto_cliente' => $dto->cliente,
            'dto_cotizacion_id' => $dto->cotizacionId,
            'asesor_id' => $asesorId,
        ]);

        // Validar DTO
        if (!$dto->esValido()) {
            \Log::error(' [PedidoProduccionCreatorService] DTO no válido');
            throw new \InvalidArgumentException('Datos inválidos para crear pedido');
        }

        \Log::info(' [PedidoProduccionCreatorService] DTO validado correctamente');

        // Obtener prendas válidas
        $prendas = $dto->prendasValidas();
        if (empty($prendas)) {
            \Log::error(' [PedidoProduccionCreatorService] No hay prendas válidas');
            throw new \InvalidArgumentException('No hay prendas con cantidades válidas');
        }

        \Log::info(' [PedidoProduccionCreatorService] Prendas válidas obtenidas', [
            'total_prendas' => count($prendas),
        ]);

        \Log::info(' [PedidoProduccionCreatorService] Ejecutando Job directamente');

        // Ejecutar el Job directamente (sin cola) para garantizar ejecución inmediata
        $job = new CrearPedidoProduccionJob($dto, $asesorId, $prendas);
        
        \Log::info(' [PedidoProduccionCreatorService] Job instanciado, llamando a handle()');
        
        $pedido = $job->handle(
            app(\App\Services\Pedidos\PrendaProcessorService::class),
            app(\App\Application\Services\PedidoPrendaService::class),
            app(\App\Application\Services\PedidoLogoService::class),
            app(\App\Application\Services\CopiarImagenesCotizacionAPedidoService::class),
            app(\App\Services\Pedidos\EnriquecerDatosService::class)
        );

        \Log::info(' [PedidoProduccionCreatorService] Job ejecutado, pedido retornado', [
            'pedido_id' => $pedido?->id,
            'numero_pedido' => $pedido?->numero_pedido,
            'forma_de_pago_guardada' => $pedido?->forma_de_pago,
        ]);

        return $pedido;
    }

}
