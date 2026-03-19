<?php

namespace Tests\Feature;

use App\Models\Epp;
use App\Models\PedidoAnexoHistorial;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PedidoAnexoHistorialTest
 *
 * Prueba funcional del sistema de historial de anexos.
 * NO usa RefreshDatabase — trabaja sobre la base de datos real
 * y limpia los registros que crea al finalizar.
 *
 * Cubre:
 * - Registro correcto al agregar prenda nueva
 * - Registro correcto al editar prenda existente
 * - Registro correcto al agregar EPP nuevo
 * - Registro correcto al editar EPP existente
 * - Ordenamiento: pedidos con anexos recientes aparecen primero
 */
class PedidoAnexoHistorialTest extends TestCase
{
    /** @var int[] IDs creados en este test para limpiar en tearDown */
    private array $historialCreadosIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = User::first();
        if (!$usuario) {
            $this->markTestSkipped('No hay usuarios en la base de datos.');
        }

        $this->actingAs($usuario);
    }

    protected function tearDown(): void
    {
        // Eliminar solo los registros de historial creados en este test
        if (!empty($this->historialCreadosIds)) {
            DB::table('pedido_anexos_historial')
                ->whereIn('id', $this->historialCreadosIds)
                ->delete();
        }

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    
    private function pedidoExistente(): PedidoProduccion
    {
        $pedido = PedidoProduccion::whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->first();

        if (!$pedido) {
            $this->markTestSkipped('No hay pedidos disponibles en la base de datos.');
        }

        return $pedido;
    }

    private function eppExistente(): Epp
    {
        $epp = Epp::first();

        if (!$epp) {
            $this->markTestSkipped('No hay EPPs disponibles en la base de datos.');
        }

        return $epp;
    }

    private function track(PedidoAnexoHistorial $registro): PedidoAnexoHistorial
    {
        $this->historialCreadosIds[] = $registro->id;
        return $registro;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function registra_prenda_nueva_correctamente(): void
    {
        $pedido = $this->pedidoExistente();

        $registro = $this->track(
            PedidoAnexoHistorial::registrarPrendaNueva($pedido->id, 999, 'CAMISA DRILL')
        );

        $this->assertDatabaseHas('pedido_anexos_historial', [
            'id'                   => $registro->id,
            'pedido_produccion_id' => $pedido->id,
            'tipo'                 => 'PRENDA',
            'referencia_id'        => 999,
        ]);

        $this->assertStringContainsString('CAMISA DRILL', $registro->descripcion);
        $this->assertStringContainsString('NUEVA', $registro->descripcion);

        echo "\n  ✓ Prenda nueva registrada — ID historial: {$registro->id}, pedido: {$pedido->numero_pedido}\n";
    }

    #[Test]
    public function registra_prenda_editada_correctamente(): void
    {
        $pedido = $this->pedidoExistente();

        $registro = $this->track(
            PedidoAnexoHistorial::registrarPrendaEditada($pedido->id, 888, 'PANTALÓN JEAN')
        );

        $this->assertDatabaseHas('pedido_anexos_historial', [
            'id'                   => $registro->id,
            'pedido_produccion_id' => $pedido->id,
            'tipo'                 => 'PRENDA',
            'referencia_id'        => 888,
        ]);

        $this->assertStringContainsString('EDITADA', $registro->descripcion);

        echo "\n  ✓ Prenda editada registrada — ID historial: {$registro->id}, pedido: {$pedido->numero_pedido}\n";
    }

    #[Test]
    public function registra_epp_nuevo_correctamente(): void
    {
        $pedido = $this->pedidoExistente();
        $epp    = $this->eppExistente();

        $registro = $this->track(
            PedidoAnexoHistorial::registrarEppNuevo($pedido->id, 777, $epp->id)
        );

        $this->assertDatabaseHas('pedido_anexos_historial', [
            'id'                   => $registro->id,
            'pedido_produccion_id' => $pedido->id,
            'tipo'                 => 'EPP',
            'referencia_id'        => 777,
        ]);

        $this->assertStringContainsString('NUEVO', $registro->descripcion);
        $this->assertStringContainsString((string)$epp->id, $registro->descripcion);

        echo "\n  ✓ EPP nuevo registrado — ID historial: {$registro->id}, epp_id: {$epp->id}\n";
    }

    #[Test]
    public function registra_epp_editado_correctamente(): void
    {
        $pedido = $this->pedidoExistente();
        $epp    = $this->eppExistente();

        $registro = $this->track(
            PedidoAnexoHistorial::registrarEppEditado($pedido->id, 666, $epp->id)
        );

        $this->assertDatabaseHas('pedido_anexos_historial', [
            'id'                   => $registro->id,
            'pedido_produccion_id' => $pedido->id,
            'tipo'                 => 'EPP',
            'referencia_id'        => 666,
        ]);

        $this->assertStringContainsString('EDITADO', $registro->descripcion);

        echo "\n  ✓ EPP editado registrado — ID historial: {$registro->id}, epp_id: {$epp->id}\n";
    }

    #[Test]
    public function created_by_corresponde_al_usuario_autenticado(): void
    {
        $pedido  = $this->pedidoExistente();
        $usuario = auth()->user();

        $registro = $this->track(
            PedidoAnexoHistorial::registrarPrendaNueva($pedido->id, 555, 'BLUSA')
        );

        $this->assertEquals($usuario->id, $registro->created_by);

        echo "\n  ✓ created_by correcto — user_id: {$usuario->id}\n";
    }

    #[Test]
    public function multiples_anexos_del_mismo_pedido_se_registran_independientemente(): void
    {
        $pedido = $this->pedidoExistente();

        $r1 = $this->track(PedidoAnexoHistorial::registrarPrendaNueva($pedido->id, 111, 'CAMISA'));
        $r2 = $this->track(PedidoAnexoHistorial::registrarEppNuevo($pedido->id, 222, 1));
        $r3 = $this->track(PedidoAnexoHistorial::registrarPrendaEditada($pedido->id, 111, 'CAMISA'));

        $this->assertNotEquals($r1->id, $r2->id);
        $this->assertNotEquals($r2->id, $r3->id);

        $conteo = DB::table('pedido_anexos_historial')
            ->whereIn('id', [$r1->id, $r2->id, $r3->id])
            ->count();

        $this->assertEquals(3, $conteo);

        echo "\n  ✓ 3 registros independientes creados para pedido #{$pedido->numero_pedido}\n";
    }

    #[Test]
    public function pedidos_con_anexos_recientes_aparecen_primero_en_supervisor(): void
    {
        $pedido = $this->pedidoExistente();

        // Crear un registro de historial ahora
        $registro = $this->track(
            PedidoAnexoHistorial::registrarPrendaNueva($pedido->id, 444, 'PRENDA PRUEBA ORDEN')
        );

        // Ejecutar la misma query que usa SupervisorPedidosController
        $pedidos = PedidoProduccion::whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->where('estado', '!=', 'Anulada')
            ->orderByRaw('(SELECT MAX(created_at) FROM pedido_anexos_historial WHERE pedido_produccion_id = pedidos_produccion.id) IS NULL ASC')
            ->orderByRaw('(SELECT MAX(created_at) FROM pedido_anexos_historial WHERE pedido_produccion_id = pedidos_produccion.id) DESC')
            ->orderBy('numero_pedido', 'desc')
            ->limit(5)
            ->get();

        // El pedido con el historial recién creado debe aparecer primero
        $primerPedidoId = $pedidos->first()?->id;

        $this->assertEquals(
            $pedido->id,
            $primerPedidoId,
            "El pedido con historial reciente ({$pedido->numero_pedido}) debería aparecer primero, pero apareció primero el ID {$primerPedidoId}."
        );

        echo "\n  ✓ Pedido con anexo reciente aparece primero — #{$pedido->numero_pedido} (ID: {$pedido->id})\n";
    }

    #[Test]
    public function pedidos_con_anexos_recientes_aparecen_primero_en_bodega(): void
    {
        $pedido = $this->pedidoExistente();

        $registro = $this->track(
            PedidoAnexoHistorial::registrarEppNuevo($pedido->id, 333, 1)
        );

        // Misma query que BodegaRepository::obtenerPedidosBase
        // (ReciboPrenda usa la tabla pedidos_produccion, por eso la subquery funciona igual)
        $pedidos = PedidoProduccion::whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->orderByRaw('(SELECT MAX(created_at) FROM pedido_anexos_historial WHERE pedido_produccion_id = pedidos_produccion.id) IS NULL ASC')
            ->orderByRaw('(SELECT MAX(created_at) FROM pedido_anexos_historial WHERE pedido_produccion_id = pedidos_produccion.id) DESC')
            ->limit(5)
            ->get();

        $primerPedidoId = $pedidos->first()?->id;

        $this->assertEquals(
            $pedido->id,
            $primerPedidoId,
            "El pedido con anexo bodega debería aparecer primero, pero apareció ID {$primerPedidoId}."
        );

        echo "\n  ✓ Ordenamiento bodega correcto — #{$pedido->numero_pedido} es el primero\n";
    }

    #[Test]
    public function relacion_pedido_carga_correctamente(): void
    {
        $pedido = $this->pedidoExistente();

        $registro = $this->track(
            PedidoAnexoHistorial::registrarPrendaNueva($pedido->id, 123, 'CHALECO')
        );

        $registroCargado = PedidoAnexoHistorial::with('pedido')->find($registro->id);

        $this->assertNotNull($registroCargado->pedido);
        $this->assertEquals($pedido->id, $registroCargado->pedido->id);
        $this->assertEquals($pedido->numero_pedido, $registroCargado->pedido->numero_pedido);

        echo "\n  ✓ Relación con pedido carga correctamente — #{$pedido->numero_pedido}\n";
    }
}
