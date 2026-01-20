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
        
        // Usar transacción para garantizar atomicidad
        return DB::transaction(function () use ($prendaProcessor, $prendaService, $logoService, $copiarImagenesService, $enriquecerService) {
            \Log::info('� [CrearPedidoProduccionJob] Dentro de transacción DB');
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

            // Obtener y incrementar número de pedido de forma segura
            // PERO: Si es LOGO, NO asignar número en pedidos_produccion
            $numeroPedido = null;
            
            if (!$this->dto->esLogoPedido()) {
                // Solo para pedidos normales, asignar número
                $secuenciaRow = DB::table('numero_secuencias')
                    ->where('tipo', 'pedido_produccion')
                    ->lockForUpdate()
                    ->first();
                
                $numeroPedido = $secuenciaRow->siguiente;
                
                \Log::info(' [CrearPedidoProduccionJob] Número obtenido de secuencia', [
                    'secuencia_row' => $secuenciaRow,
                    'numero_pedido_raw' => $numeroPedido,
                    'tipo_numero_pedido' => gettype($numeroPedido),
                    'es_string' => is_string($numeroPedido),
                    'es_int' => is_int($numeroPedido),
                ]);
                
                //  CRÍTICO: Asegurar que sea un entero, no string con prefijo
                if (is_string($numeroPedido) && str_contains($numeroPedido, 'PEP-')) {
                    // Si viene con prefijo, extraer solo el número
                    $numeroPedido = (int) str_replace('PEP-', '', $numeroPedido);
                    \Log::warning(' [CrearPedidoProduccionJob] Número tenía prefijo PEP-, se extrajo solo el número', [
                        'numero_limpio' => $numeroPedido
                    ]);
                } else {
                    // Convertir a entero para asegurar
                    $numeroPedido = (int) $numeroPedido;
                }

                // Incrementar para el próximo
                DB::table('numero_secuencias')
                    ->where('tipo', 'pedido_produccion')
                    ->increment('siguiente');
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
            
            // Crear pedido con número generado
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $this->dto->cotizacionId,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'asesor_id' => $this->asesorId,
                'numero_pedido' => $numeroPedido,
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
        });
    }
}
