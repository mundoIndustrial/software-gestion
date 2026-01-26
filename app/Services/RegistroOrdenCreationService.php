<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\News;
use Illuminate\Support\Facades\DB;

/**
 * RegistroOrdenCreationService
 * 
 * Responsabilidad: Lógica de creación de nuevas órdenes
 * Cumple con SRP: Solo maneja creación, no validación ni persistencia de resultados
 * Cumple con DIP: Inyecta modelos necesarios
 */
class RegistroOrdenCreationService
{
    /**
     * Obtener el próximo número de pedido disponible
     */
    public function getNextPedidoNumber(): int
    {
        $lastPedido = PedidoProduccion::max('numero_pedido');
        return $lastPedido ? $lastPedido + 1 : 1;
    }

    /**
     * Crear nueva orden con sus prendas asociadas
     * 
     * @param array $data Datos validados de la orden
     * @return PedidoProduccion La orden creada
     * @throws \Exception Si falla la creación
     */
    public function createOrder(array $data): PedidoProduccion
    {
        DB::beginTransaction();

        try {
            // Crear pedido en PedidoProduccion
            // Valores por defecto si no se proporcionan
            $estado = $data['estado'] ?? 'Pendiente';
            $area = $data['area'] ?? 'creacion de pedido';
            
            \Log::info('[REGISTRO-ORDEN] Creando pedido con valores por defecto', [
                'numero_pedido' => $data['pedido'],
                'estado_guardado' => $estado,
                'area_guardada' => $area,
            ]);
            
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $data['pedido'],
                'cliente' => $data['cliente'],
                'estado' => $estado,
                'area' => $area,
                'forma_de_pago' => $data['forma_pago'] ?? null,
                'fecha_de_creacion_de_orden' => $data['fecha_creacion'],
                'novedades' => null,
            ]);

            \Log::info('[REGISTRO-ORDEN] Pedido creado exitosamente', [
                'numero_pedido' => $pedido->numero_pedido,
                'pedido_id' => $pedido->id,
                'estado_verificado' => $pedido->estado,
                'area_verificada' => $pedido->area,
            ]);

            // Crear prendas en PrendaPedido
            $this->createPrendas($pedido->numero_pedido, $data['prendas']);

            // Crear el proceso inicial "Creación de Orden" para el pedido
            $this->createInitialProcesso($pedido, $data);

            DB::commit();

            return $pedido;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('[REGISTRO-ORDEN] Error al crear pedido', [
                'numero_pedido' => $data['pedido'] ?? 'UNKNOWN',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Crear prendas para una orden
     */
    private function createPrendas(int $numeroPedido, array $prendas): void
    {
        foreach ($prendas as $prendaData) {
            $this->createSinglePrenda($numeroPedido, $prendaData);
        }
    }

    /**
     * Crear una prenda individual
     */
    private function createSinglePrenda(int $numeroPedido, array $prendaData): void
    {
        // Calcular cantidad total de la prenda
        $cantidadPrenda = 0;
        $cantidadesPorTalla = [];
        
        foreach ($prendaData['tallas'] as $talla) {
            $cantidadPrenda += $talla['cantidad'];
            $cantidadesPorTalla[$talla['talla']] = $talla['cantidad'];
        }

        // Crear prenda
        PrendaPedido::create([
            'numero_pedido' => $numeroPedido,
            'nombre_prenda' => $prendaData['prenda'],
            'cantidad' => $cantidadPrenda,
            'descripcion' => $prendaData['descripcion'] ?? '',
            'cantidad_talla' => json_encode($cantidadesPorTalla),
        ]);
    }

    /**
     * Crear el proceso inicial "Creación de Orden" para un nuevo pedido
     * 
     * @param PedidoProduccion $pedido El pedido creado
     * @param array $data Datos del pedido
     * @return void
     * @throws \Exception Si falla la creación del proceso
     */
    private function createInitialProcesso(PedidoProduccion $pedido, array $data): void
    {
        try {
            \Log::info('[REGISTRO-ORDEN-PROCESO] Iniciando creación de proceso inicial', [
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // Crear el proceso "Creación de Orden" con estado "Pendiente"
            $procesoInicial = ProcesoPrenda::create([
                'numero_pedido'    => $pedido->numero_pedido,
                'prenda_pedido_id' => null, // Null porque es un proceso general del pedido
                'proceso'          => 'Creación de Orden',
                'estado_proceso'   => 'Pendiente',
                'fecha_inicio'     => now(),
                'dias_duracion'    => $data['dias_duracion_proceso'] ?? 1,
                'encargado'        => $data['encargado_proceso'] ?? null,
                'observaciones'    => 'Proceso inicial de creación del pedido',
                'codigo_referencia' => $pedido->numero_pedido,
            ]);

            \Log::info('[REGISTRO-ORDEN-PROCESO] Proceso inicial creado exitosamente', [
                'numero_pedido' => $pedido->numero_pedido,
                'proceso' => $procesoInicial->proceso,
                'estado_proceso' => $procesoInicial->estado_proceso,
                'proceso_id' => $procesoInicial->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('[REGISTRO-ORDEN-PROCESO] Error al crear proceso inicial', [
                'numero_pedido' => $pedido->numero_pedido,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Crear un proceso adicional para un pedido ya existente
     * (Puede ser utilizado posteriormente para agregar más procesos)
     * 
     * @param PedidoProduccion $pedido El pedido
     * @param string $nombreProceso Nombre del proceso a crear
     * @param array $datos Datos adicionales del proceso
     * @return ProcesoPrenda|null
     */
    public function createAdditionalProcesso(PedidoProduccion $pedido, string $nombreProceso, array $datos = []): ?ProcesoPrenda
    {
        try {
            \Log::info('[REGISTRO-ORDEN-PROCESO] Creando proceso adicional', [
                'numero_pedido' => $pedido->numero_pedido,
                'proceso' => $nombreProceso,
            ]);

            $proceso = ProcesoPrenda::create([
                'numero_pedido'     => $pedido->numero_pedido,
                'prenda_pedido_id'  => $datos['prenda_pedido_id'] ?? null,
                'proceso'           => $nombreProceso,
                'estado_proceso'    => $datos['estado_proceso'] ?? 'Pendiente',
                'fecha_inicio'      => $datos['fecha_inicio'] ?? now(),
                'dias_duracion'     => $datos['dias_duracion'] ?? 1,
                'encargado'         => $datos['encargado'] ?? null,
                'observaciones'     => $datos['observaciones'] ?? null,
                'codigo_referencia' => $datos['codigo_referencia'] ?? $pedido->numero_pedido,
            ]);

            \Log::info('[REGISTRO-ORDEN-PROCESO] Proceso adicional creado exitosamente', [
                'numero_pedido' => $pedido->numero_pedido,
                'proceso' => $proceso->proceso,
                'proceso_id' => $proceso->id,
            ]);

            return $proceso;

        } catch (\Exception $e) {
            \Log::error('[REGISTRO-ORDEN-PROCESO] Error al crear proceso adicional', [
                'numero_pedido' => $pedido->numero_pedido,
                'proceso' => $nombreProceso,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Registrar evento de creación de orden
     */
    public function logOrderCreated(int $pedido, string $cliente, string $estado): void
    {
        News::create([
            'event_type' => 'order_created',
            'description' => "Nueva orden registrada: Pedido {$pedido} para cliente {$cliente}",
            'user_id' => auth()->id(),
            'pedido' => $pedido,
            'metadata' => ['cliente' => $cliente, 'estado' => $estado]
        ]);
    }

    /**
     * Broadcast evento de orden creada
     */
    public function broadcastOrderCreated(PedidoProduccion $pedido): void
    {
        broadcast(new \App\Events\OrdenUpdated($pedido, 'created'));
    }
}
