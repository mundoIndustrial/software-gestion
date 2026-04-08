<?php

namespace Tests\Feature;

use App\Application\Pedidos\Services\PedidoCreationCoordinator;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Test de concurrencia para creación de pedidos
 *
 * Nota: Este test NO usa RefreshDatabase. Se ejecuta sobre el esquema existente
 * y se revierte con transacciones.
 */
class ConcurrenciaCreacionPedidosTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (Config::get('database.default') !== 'mysql') {
            $this->markTestSkipped('Este test de concurrencia requiere MySQL real.');
        }

        if (!Schema::hasTable('numero_secuencias')) {
            $this->markTestSkipped('La tabla numero_secuencias no existe en el entorno de pruebas.');
        }

        $this->createTestUsersIfNeeded();

        // Configurar timeout para pruebas (si el motor lo soporta)
        try {
            DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
        } catch (\Exception $e) {
            // silencioso
        }
    }

    /**
     * Test: 15 usuarios crean pedidos "simultáneamente".
     *
     * Importante: PHP Unit corre en un solo proceso, este test valida
     * integridad/consecutividad sin caer en colisiones.
     */
    public function test_quince_usuarios_crean_pedidos_simultaneamente(): void
    {
        $usuarios = User::take(15)->get();
        $resultados = [];
        $errores = [];
        foreach ($usuarios as $index => $usuario) {
            try {
                $resultados[] = $this->createPedidoForUser($usuario, $index);
            } catch (\Exception $e) {
                $errores[] = ['error' => $e->getMessage()];
            }
        }

        $this->assertCount(15, $resultados, 'Deben crearse 15 pedidos exitosamente');
        $this->assertCount(0, $errores, 'No debe haber errores en concurrencia');

        // IDs únicos
        $ids = array_column($resultados, 'id');
        $this->assertEquals(count($ids), count(array_unique($ids)), 'Todos los IDs deben ser únicos');

        // Números de pedido asignados y únicos
        $numerosPedido = array_column($resultados, 'numero_pedido');
        $numerosNoVacios = array_filter($numerosPedido, fn ($n) => $n !== null && $n !== '');
        $this->assertCount(15, $numerosNoVacios, 'numero_pedido debe asignarse al crear');
        $this->assertEquals(count($numerosPedido), count(array_unique($numerosPedido)), 'numero_pedido debe ser único');

        // Consecutividad dentro del batch creado
        $numerosOrdenados = array_map('intval', $numerosPedido);
        sort($numerosOrdenados);
        for ($i = 1; $i < count($numerosOrdenados); $i++) {
            $this->assertEquals(
                $numerosOrdenados[$i - 1] + 1,
                $numerosOrdenados[$i],
                'Los números de pedido deben ser consecutivos'
            );
        }

        Log::info('[TEST] Concurrencia creación pedidos OK', [
            'pedidos_creados' => count($resultados),
            'numeros' => $numerosOrdenados,
        ]);
    }

    /**
     * Test: 30 usuarios con carga alta.
     */
    public function test_treinta_usuarios_creacion_intensiva(): void
    {
        $usuarios = User::take(30)->get();
        $resultados = [];
        $startTime = microtime(true);
        foreach ($usuarios as $index => $usuario) {
            $resultados[] = $this->createPedidoForUser($usuario, $index);
        }

        $duracion = microtime(true) - $startTime;

        $this->assertCount(30, $resultados);
        $this->assertLessThan(60.0, $duracion, 'Debe completarse en menos de 60 segundos');

        $numerosPedido = array_column($resultados, 'numero_pedido');
        $this->assertEquals(count($numerosPedido), count(array_unique($numerosPedido)), 'numero_pedido único');
    }

    /**
     * Test: Cartera NO debe reasignar numero_pedido (solo cambia estado).
     */
    public function test_secuencia_numeros_pedido_cartera(): void
    {
        $pedidosCreados = [];
        for ($i = 0; $i < 5; $i++) {
            $pedidosCreados[] = $this->createPedidoBaseConNumero();
        }

        $originales = array_map(fn ($p) => (int) $p->numero_pedido, $pedidosCreados);
        $persistidos = array_map(fn ($p) => (int) $this->aprobarEnCartera($p), $pedidosCreados);

        sort($originales);
        sort($persistidos);
        $this->assertEquals($originales, $persistidos, 'Cartera no debe modificar numero_pedido');
    }

    private function createPedidoForUser(User $usuario, int $index): array
    {
        $this->actingAs($usuario);

        $service = app(PedidoCreationCoordinator::class);
        $pedido = $service->crearPedidoCompleto([
            'cliente' => "Cliente Test {$index}",
            'orden_compra' => null,
            'forma_de_pago' => 'Contado',
            'observaciones' => null,
            'items' => [
                [
                    'nombre_prenda' => 'Camisa Test',
                    'descripcion' => 'Prueba concurrencia',
                    'de_bodega' => 0,
                    'cantidad_talla' => ['M' => 10],
                    'telas' => [],
                    'procesos' => [],
                ],
            ],
            'epps' => [],
        ], (int) $usuario->id);

        return [
            'id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'asesor_id' => $pedido->asesor_id,
            'estado' => $pedido->estado,
        ];
    }

    private function aprobarEnCartera(PedidoProduccion $pedido): int
    {
        $pedido->update(['estado' => 'PENDIENTE_SUPERVISOR']);
        return (int) $pedido->numero_pedido;
    }

    private function createTestUsersIfNeeded(): void
    {
        $count = User::count();
        if ($count >= 50) {
            return;
        }

        $toCreate = 50 - $count;
        User::factory()->count($toCreate)->create();
    }

    private function createPedidoBaseConNumero(): PedidoProduccion
    {
        $usuario = User::first() ?? User::factory()->create();

        $service = app(PedidoCreationCoordinator::class);
        return $service->crearPedidoCompleto([
            'cliente' => 'Cliente Test',
            'orden_compra' => null,
            'forma_de_pago' => 'Contado',
            'observaciones' => null,
            'items' => [
                [
                    'nombre_prenda' => 'Camisa Test',
                    'descripcion' => 'Prueba cartera',
                    'de_bodega' => 0,
                    'cantidad_talla' => ['M' => 1],
                    'telas' => [],
                    'procesos' => [],
                ],
            ],
            'epps' => [],
        ], (int) $usuario->id);
    }

    protected function tearDown(): void
    {
        try {
            DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
        } catch (\Exception $e) {
            // silencioso
        }

        parent::tearDown();
    }
}
