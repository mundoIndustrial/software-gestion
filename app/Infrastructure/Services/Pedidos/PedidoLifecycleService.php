<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Infrastructure\Services\Pedidos\PedidoSequenceService;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class PedidoLifecycleService
{
    public function __construct(
        private PedidoSequenceService $pedidoSequenceService,
    ) {}

    public function crearPedidoBase(array $datos, int $asesorId): PedidoProduccion
    {
        $area = $this->resolverArea($datos);
        $estado = $this->resolverEstadoInicial($datos);
        $numeroPedido = $this->pedidoSequenceService->generarNumeroPedido();

        Log::info('[PedidoLifecycleService] Creando pedido base con numero consecutivo', [
            'numero_pedido' => $numeroPedido,
            'area' => $area,
            'estado' => $estado,
        ]);

        return PedidoProduccion::create([
            'numero_pedido' => $numeroPedido,
            'orden_compra' => $datos['orden_compra'] ?? null,
            'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
            'asesor_id' => $asesorId,
            'cliente_id' => $datos['cliente_id'] ?? null,
            'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
            'novedades' => $datos['descripcion'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'estado' => $estado,
            'cantidad_total' => 0,
            'area' => $area,
            'created_at' => now(),
        ]);
    }

    public function crearPedidoBaseBorrador(array $datos, int $asesorId): PedidoProduccion
    {
        $area = $this->resolverArea($datos);

        Log::info('[PedidoLifecycleService] Creando pedido borrador base', [
            'numero_pedido' => 'NULL (Borrador)',
            'area' => $area,
            'estado' => 'Borrador',
        ]);

        return PedidoProduccion::create([
            'numero_pedido' => null,
            'orden_compra' => $datos['orden_compra'] ?? null,
            'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
            'asesor_id' => $asesorId,
            'cliente_id' => $datos['cliente_id'] ?? null,
            'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
            'novedades' => null,
            'observaciones' => $datos['observaciones'] ?? null,
            'estado' => 'Borrador',
            'cantidad_total' => 0,
            'area' => $area,
            'created_at' => now(),
        ]);
    }

    public function convertirBorradorEnPedido(PedidoProduccion $borrador, array $datosValidados): PedidoProduccion
    {
        $numeroPedido = $this->pedidoSequenceService->generarNumeroPedido();
        $estado = $this->resolverEstadoInicial([
            'items' => $borrador->prendas()->exists() ? ['present'] : [],
            'epps' => $borrador->epps()->exists() ? ['present'] : [],
        ]);

        Log::info('[PedidoLifecycleService] Convirtiendo borrador en pedido real', [
            'borrador_id' => $borrador->id,
            'numero_pedido' => $numeroPedido,
            'estado' => $estado,
        ]);

        $borrador->update([
            'numero_pedido' => $numeroPedido,
            'estado' => $estado,
            'cliente' => $datosValidados['cliente'] ?? $borrador->cliente,
            'cliente_id' => $datosValidados['cliente_id'] ?? $borrador->cliente_id,
            'orden_compra' => $datosValidados['orden_compra'] ?? $borrador->orden_compra,
            'forma_de_pago' => $datosValidados['forma_de_pago'] ?? $borrador->forma_de_pago,
            'observaciones' => $datosValidados['observaciones'] ?? $borrador->observaciones,
            'created_at' => now(),
        ]);

        Log::info('[PedidoLifecycleService] Borrador convertido exitosamente', [
            'pedido_id' => $borrador->id,
            'numero_pedido' => $numeroPedido,
        ]);

        return $borrador->fresh();
    }

    public function obtenerBorradorPorId(int $borradorId): ?PedidoProduccion
    {
        return PedidoProduccion::query()
            ->where('id', $borradorId)
            ->where('estado', 'Borrador')
            ->first();
    }

    private function resolverArea(array $datos): string
    {
        $area = $datos['area'] ?? $datos['estado_area'] ?? 'Creación Orden';

        if (!is_string($area)) {
            return 'creacion de pedido';
        }

        $area = trim($area);

        return $area === '' ? 'Creación Orden' : $area;
    }

    private function resolverEstadoInicial(array $datos): string
    {
        return 'pendiente_cartera';
    }
}
