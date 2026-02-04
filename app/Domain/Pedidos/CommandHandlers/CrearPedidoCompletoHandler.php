<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\Pedidos\Commands\CrearPedidoCompletoCommand;
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Commands\AgregarPrendaAlPedidoCommand;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CrearPedidoCompletoHandler
 * 
 * Orquestador principal de la creación de pedidos completos
 * 
 * Flujo:
 * 1. Genera número de pedido
 * 2. Crea pedido raíz (usando CrearPedidoCommand)
 * 3. Itera items y agrega cada prenda (usando AgregarPrendaAlPedidoCommand)
 * 4. Cada prenda se procesa con Strategy (CreacionPrendaSinCtaStrategy)
 * 5. Strategy guarda: prenda base, tallas, variantes, colores/telas, procesos, imágenes
 * 
 * Responsabilidades:
 * - Orquestar la transacción completa
 * - Generar número de pedido
 * - Coordinar Commands hijos
 * - Manejar rollback en caso de error
 * - Logging de todo el proceso
 */
class CrearPedidoCompletoHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commandBus,
        private PedidoProduccion $pedidoModel,
    ) {}

    /**
     * Ejecutar el command
     * 
     * @param CrearPedidoCompletoCommand $command
     * @return PedidoProduccion Pedido creado con todas sus prendas
     */
    public function handle(Command $command): mixed
    {
        if (!$command instanceof CrearPedidoCompletoCommand) {
            throw new \InvalidArgumentException('Command debe ser CrearPedidoCompletoCommand');
        }

        DB::beginTransaction();
        
        try {
            Log::info('⚡ [CrearPedidoCompletoHandler] Iniciando creación de pedido completo', [
                'cliente' => $command->getCliente(),
                'items_count' => count($command->getItems()),
                'asesor_id' => $command->getAsesorId(),
            ]);

            // ===== PASO 1: GENERAR NÚMERO DE PEDIDO =====
            $numeroPedido = $this->generarNumeroPedido();
            
            Log::info(' [CrearPedidoCompletoHandler] Número de pedido generado', [
                'numero_pedido' => $numeroPedido,
            ]);

            // ===== PASO 2: CREAR PEDIDO BASE (ENTIDAD RAÍZ) =====
            $crearPedidoCmd = new CrearPedidoCommand(
                numeroPedido: $numeroPedido,
                cliente: $command->getCliente(),
                formaPago: $command->getFormaPago(),
                asesorId: $command->getAsesorId(),
                cantidadInicial: 0, // Se actualizará automáticamente
            );
            
            /** @var PedidoProduccion $pedido */
            $pedido = $this->commandBus->execute($crearPedidoCmd);
            
            Log::info(' [CrearPedidoCompletoHandler] Pedido base creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // ===== PASO 3: AGREGAR CADA PRENDA (AGREGADOS) =====
            $prendasCreadas = [];
            
            foreach ($command->getItems() as $index => $itemData) {
                try {
                    Log::info("🔹 [CrearPedidoCompletoHandler] Procesando item #{$index}", [
                        'nombre_prenda' => $itemData['nombre_prenda'] ?? 'Sin nombre',
                        'tipo' => $itemData['tipo'] ?? 'prenda_nueva',
                    ]);

                    // Determinar el tipo de prenda
                    $tipoPrenda = $this->determinarTipoPrenda($itemData);
                    
                    // Crear command para agregar prenda
                    $agregarPrendaCmd = new AgregarPrendaAlPedidoCommand(
                        pedidoId: $pedido->id,
                        prendaData: $itemData,
                        tipo: $tipoPrenda,
                    );
                    
                    // Ejecutar (esto llamará a PrendaCreationService y luego a Strategy)
                    $prenda = $this->commandBus->execute($agregarPrendaCmd);
                    
                    $prendasCreadas[] = $prenda;
                    
                    Log::info(" [CrearPedidoCompletoHandler] Prenda #{$index} agregada", [
                        'prenda_id' => $prenda->id,
                        'nombre' => $prenda->nombre_prenda,
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error(" [CrearPedidoCompletoHandler] Error en item #{$index}", [
                        'error' => $e->getMessage(),
                        'item_data' => $itemData,
                    ]);
                    
                    throw new \Exception("Error al procesar prenda #{$index}: " . $e->getMessage());
                }
            }

            // ===== PASO 4: COMMIT TRANSACCIÓN =====
            DB::commit();

            // Recargar pedido con relaciones
            $pedido->refresh();
            
            Log::info('🎉 [CrearPedidoCompletoHandler] Pedido completo creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prendas_creadas' => count($prendasCreadas),
                'cantidad_total' => $pedido->cantidad_total,
            ]);

            return $pedido;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error(' [CrearPedidoCompletoHandler] Error al crear pedido completo', [
                'cliente' => $command->getCliente(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generar número de pedido secuencial
     * Formato: Incremental desde el último pedido
     * 
     * @return string
     */
    private function generarNumeroPedido(): string
    {
        $ultimoPedido = $this->pedidoModel
            ->orderBy('id', 'desc')
            ->first();

        if (!$ultimoPedido) {
            return '1';
        }

        // Si el número es numérico, incrementar
        if (is_numeric($ultimoPedido->numero_pedido)) {
            return (string)((int)$ultimoPedido->numero_pedido + 1);
        }

        // Si tiene formato especial, extraer número y aumentar
        // Ej: "PED-00123" -> "PED-00124"
        if (preg_match('/^(.+?)(\d+)$/', $ultimoPedido->numero_pedido, $matches)) {
            $prefijo = $matches[1];
            $numero = (int)$matches[2] + 1;
            $padding = strlen($matches[2]);
            return $prefijo . str_pad($numero, $padding, '0', STR_PAD_LEFT);
        }

        // Fallback: usar ID del último pedido + 1
        return (string)($ultimoPedido->id + 1);
    }

    /**
     * Determinar el tipo de prenda según los datos recibidos
     * 
     * @param array $itemData
     * @return string 'sin_cotizacion' | 'reflectivo'
     */
    private function determinarTipoPrenda(array $itemData): string
    {
        // Si viene explícitamente el tipo
        if (isset($itemData['tipo'])) {
            if ($itemData['tipo'] === 'reflectivo' || $itemData['tipo'] === 'prenda_reflectivo') {
                return 'reflectivo';
            }
        }

        // Si tiene procesos de tipo reflectivo
        if (isset($itemData['procesos']['reflectivo'])) {
            return 'reflectivo';
        }

        // Por defecto: sin cotización
        return 'sin_cotizacion';
    }
}
