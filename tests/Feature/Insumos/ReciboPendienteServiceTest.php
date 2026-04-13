<?php

namespace Tests\Feature\Insumos;

use App\Application\Insumos\UseCases\CambiarEstadoReciboInsumosUseCase;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReciboPendienteServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_aprobar_recibo_crea_proceso_corte_para_la_prenda(): void
    {
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 910001,
            'cliente' => 'Cliente Test Insumos',
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Chaqueta Test',
            'descripcion' => 'Prenda para prueba de aprobacion',
            'de_bodega' => false,
        ]);

        $recibo = ConsecutivoReciboPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_id' => $prenda->id,
            'tipo_recibo' => 'COSTURA',
            'consecutivo_actual' => 701,
            'consecutivo_inicial' => 701,
            'activo' => true,
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $resultado = app(CambiarEstadoReciboInsumosUseCase::class)
            ->execute($recibo->id, 'En Ejecución');

        $this->assertTrue($resultado['success']);

        $proceso = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->where('prenda_pedido_id', $prenda->id)
            ->where('numero_recibo', 701)
            ->where('proceso', 'Corte')
            ->first();

        $this->assertNotNull($proceso);
        $this->assertEquals('En Progreso', $proceso->estado_proceso);
    }

    public function test_aprobar_recibo_no_duplica_proceso_corte_si_ya_existe(): void
    {
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 910002,
            'cliente' => 'Cliente Test Insumos 2',
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Pantalon Test',
            'descripcion' => 'Prenda para prueba sin duplicados',
            'de_bodega' => false,
        ]);

        $recibo = ConsecutivoReciboPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_id' => $prenda->id,
            'tipo_recibo' => 'COSTURA',
            'consecutivo_actual' => 702,
            'consecutivo_inicial' => 702,
            'activo' => true,
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        ProcesoPrenda::create([
            'numero_pedido' => $pedido->numero_pedido,
            'prenda_pedido_id' => $prenda->id,
            'numero_recibo' => 702,
            'proceso' => 'Corte',
            'fecha_inicio' => now(),
            'estado_proceso' => 'Pendiente',
            'observaciones' => 'Proceso ya existente',
            'codigo_referencia' => 'P910002-COR-PP' . $prenda->id . '-R702',
        ]);

        $resultado = app(CambiarEstadoReciboInsumosUseCase::class)
            ->execute($recibo->id, 'No iniciado');

        $this->assertTrue($resultado['success']);
        $this->assertEquals(
            1,
            ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $prenda->id)
                ->where('numero_recibo', 702)
                ->where('proceso', 'Corte')
                ->count()
        );
    }

    public function test_insumos_pedidos_no_crea_proceso_corte_ni_mueve_area_a_corte(): void
    {
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 910003,
            'cliente' => 'Cliente Test Insumos 3',
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Buzo Test',
            'descripcion' => 'Prenda para validar insumos pedidos',
            'de_bodega' => false,
        ]);

        $recibo = ConsecutivoReciboPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_id' => $prenda->id,
            'tipo_recibo' => 'COSTURA',
            'consecutivo_actual' => 703,
            'consecutivo_inicial' => 703,
            'activo' => true,
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $resultado = app(CambiarEstadoReciboInsumosUseCase::class)
            ->execute($recibo->id, 'Insumos Pedidos');

        $this->assertTrue($resultado['success']);
        $this->assertDatabaseHas('consecutivos_recibos_pedidos', [
            'id' => $recibo->id,
            'estado' => 'INSUMOS_PEDIDOS',
            'area' => 'INSUMOS',
        ]);

        $this->assertDatabaseHas('pedidos_produccion', [
            'id' => $pedido->id,
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $this->assertEquals(
            0,
            ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $prenda->id)
                ->where('numero_recibo', 703)
                ->where('proceso', 'Corte')
                ->count()
        );
    }
}
