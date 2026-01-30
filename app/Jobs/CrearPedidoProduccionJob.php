<?php

namespace App\Jobs;

use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\PrendaCreacionDTO;
use App\Services\Pedidos\PrendaProcessorService;
use App\Services\Pedidos\EnriquecerDatosService;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\PedidoLogoService;
use App\Application\Services\CopiarImagenesCotizacionAPedidoService;
use App\Enums\EstadoPedido;
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
     * 
     * Optimizado para concurrencia:
     * - Sin transacciones anidadas (el service ya maneja la transacción)
     * - Timeout reducido para evitar deadlocks
     * - Orden consistente de operaciones
     */
    public function handle(
        PrendaProcessorService $prendaProcessor,
        PedidoPrendaService $prendaService,
        PedidoLogoService $logoService,
        CopiarImagenesCotizacionAPedidoService $copiarImagenesService,
        EnriquecerDatosService $enriquecerService
    ): PedidoProduccion {
        \Log::info(' [CrearPedidoProduccionJob] ===== INICIO JOB HANDLE =====');
        \Log::info(' [CrearPedidoProduccionJob] Servicios inyectados correctamente');
        
        // Configurar timeout para evitar deadlocks
        DB::statement('SET SESSION innodb_lock_wait_timeout = 3');
        
        try {
            \Log::info(' [CrearPedidoProduccionJob] Datos del DTO', [
                'dto_forma_de_pago' => $this->dto->formaDePago,
                'dto_cliente' => $this->dto->cliente,
                'dto_cotizacion_id' => $this->dto->cotizacionId,
                'prendas_recibidas' => count($this->prendas),
            ]);

            //  CONVERTIR DTOS A ARRAYS ANTES DE ENRIQUECER
            $prendasArray = array_map(
                fn($prenda) => $prenda->toArray(),
                $this->prendas
            );
            
            //  ENRIQUECER PRENDAS DEL FRONTEND CON IDs FALTANTES
            $prendasEnriquecidas = $enriquecerService->enriquecerPrendas($prendasArray);
            
            \Log::info(' [CrearPedidoProduccionJob] Prendas enriquecidas - DETALLE COMPLETO', [
                'total_prendas' => count($prendasEnriquecidas),
                'prendas_completas' => $prendasEnriquecidas,
            ]);
            
            \Log::info(' [CrearPedidoProduccionJob] Primera prenda - análisis de telas', [
                'primera_prenda_tela_id' => $prendasEnriquecidas[0]['tela_id'] ?? null,
                'primera_prenda_color_id' => $prendasEnriquecidas[0]['color_id'] ?? null,
                'primera_prenda_telas' => $prendasEnriquecidas[0]['telas'] ?? null,
                'primera_prenda_fotos' => count($prendasEnriquecidas[0]['fotos'] ?? []),
                'primera_prenda_tiene_telas_array' => isset($prendasEnriquecidas[0]['telas']),
                'primera_prenda_cantidad_telas' => isset($prendasEnriquecidas[0]['telas']) ? count($prendasEnriquecidas[0]['telas']) : 0,
            ]);

            // El número de pedido se genera en Cartera, no aquí
            $numeroPedido = null;
            
            if (!$this->dto->esLogoPedido()) {
                $numeroPedido = null; // Cartera lo asignará
                \Log::info(' [CrearPedidoProduccionJob] numero_pedido establecido como NULL', [
                    'motivo' => 'Solo Cartera genera el número al aprobar'
                ]);
            } else {
                \Log::info('  [CrearPedidoProduccionJob] Es pedido LOGO, NO se asigna número en pedidos_produccion');
            }

            // Procesar prendas
            $prendasProcesadas = array_map(
                fn($prenda) => $prendaProcessor->procesar($prenda),
                $this->prendas
            );

            \Log::info(' [CrearPedidoProduccionJob] Datos a guardar en PedidoProduccion', [
                'numero_pedido' => $numeroPedido,
                'tipo_numero_pedido' => gettype($numeroPedido),
                'forma_de_pago' => $this->dto->formaDePago,
                'cliente' => $this->dto->cliente,
                'asesor_id' => $this->asesorId,
            ]);

            // Obtener número de cotización
            $cotizacion = Cotizacion::findOrFail($this->dto->cotizacionId);
            
            // Crear pedido con número generado (ID por AUTO_INCREMENT)
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $this->dto->cotizacionId,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'asesor_id' => $this->asesorId,
                'numero_pedido' => $numeroPedido, // NULL hasta que Cartera lo apruebe
                'cliente' => $this->dto->cliente,
                'cliente_id' => $this->dto->clienteId,
                'descripcion' => $this->dto->descripcion,
                'forma_de_pago' => $this->dto->formaDePago,
                'prendas' => $prendasProcesadas,
                'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
                'fecha_de_creacion_de_orden' => now(),
            ]);

            \Log::info(' [CrearPedidoProduccionJob] Pedido creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'forma_de_pago_guardada' => $pedido->forma_de_pago,
            ]);

            //  USAR PRENDAS ENRIQUECIDAS CON IDs CORRECTOS
            // Guardar prendas en tablas normalizadas (DDD)
            if (!empty($prendasEnriquecidas)) {
                \Log::info(' [CrearPedidoProduccionJob] Guardando prendas en pedido - ANÁLISIS ANTES DE GUARDAR', [
                    'total_prendas' => count($prendasEnriquecidas),
                    'primera_prenda_tela_id' => $prendasEnriquecidas[0]['tela_id'] ?? null,
                    'primera_prenda_color_id' => $prendasEnriquecidas[0]['color_id'] ?? null,
                    'primera_prenda_fotos' => count($prendasEnriquecidas[0]['fotos'] ?? []),
                    'primera_prenda_telas' => $prendasEnriquecidas[0]['telas'] ?? [],
                    'primera_prenda_cantidad_telas' => isset($prendasEnriquecidas[0]['telas']) ? count($prendasEnriquecidas[0]['telas']) : 0,
                ]);
                $prendaService->guardarPrendasEnPedido($pedido, $prendasEnriquecidas);
                \Log::info(' [CrearPedidoProduccionJob] Prendas guardadas exitosamente');
            }

        // NO COPIAR IMÁGENES DE COTIZACIÓN AUTOMÁTICAMENTE
        // Las fotos se guardarán a través del endpoint separado guardarFotosPedido()
        // De esta forma respetamos exactamente lo que el usuario seleccionó/eliminó
        
        \Log::info('[CrearPedidoProduccionJob] NO copiando imágenes de cotización');
        \Log::info('[CrearPedidoProduccionJob] Las fotos serán guardadas a través de endpoint /pedidos/guardar-fotos');
        \Log::info('[CrearPedidoProduccionJob] Esto garantiza respetar las fotos que el usuario eliminó');

            // Guardar logo si existe (DDD)
            if (!empty($this->dto->logo)) {
                $logoService->guardarLogoEnPedido($pedido, $this->dto->logo);
            }

            return $pedido;
            
        } catch (\Exception $e) {
            \Log::error('[CrearPedidoProduccionJob] Error en job:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'dto_id' => $this->dto->cotizacionId,
                'asesor_id' => $this->asesorId
            ]);
            throw $e;
        } finally {
            // Restaurar timeout por defecto
            DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
        }
    }
}
