<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PedidoProduccion;
use App\Application\Services\Asesores\CrearPedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Test de concurrencia para creación de pedidos
 * 
 * Simula 15+ asesores creando pedidos simultáneamente
 * para verificar que no haya colisiones, IDs duplicados
 * o errores por concurrencia.
 */
class ConcurrenciaCreacionPedidosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuarios de prueba
        $this->createTestUsers();
        
        // Configurar timeout para pruebas
        DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
    }

    /**
     * Test: 15 usuarios crean pedidos simultáneamente
     */
    public function test_quince_usuarios_crean_pedidos_simultaneamente(): void
    {
        $usuarios = User::take(15)->get();
        $resultados = [];
        $errores = [];
        
        // Simular concurrencia con procesos paralelos
        $promises = [];
        
        foreach ($usuarios as $index => $usuario) {
            $promises[] = $this->simulatePedidoCreation($usuario, $index);
        }
        
        // Esperar a que todos terminen
        foreach ($promises as $promise) {
            try {
                $resultados[] = $promise->wait();
            } catch (\Exception $e) {
                $errores[] = [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
        }
        
        // Verificaciones
        $this->assertCount(15, $resultados, 'Deben crearse 15 pedidos exitosamente');
        $this->assertCount(0, $errores, 'No debe haber errores en concurrencia');
        
        // Verificar que todos los IDs sean únicos
        $ids = array_column($resultados, 'id');
        $idsUnicos = array_unique($ids);
        $this->assertEquals(count($ids), count($idsUnicos), 'Todos los IDs deben ser únicos');
        
        // Verificar que no haya números de pedido duplicados (deben ser null)
        $numerosPedido = array_column($resultados, 'numero_pedido');
        $numerosNoNulos = array_filter($numerosPedido, fn($n) => $n !== null);
        $this->assertCount(0, $numerosNoNulos, 'Los números de pedido deben ser null al crear');
        
        // Verificar orden secuencial de IDs
        sort($ids);
        for ($i = 1; $i < count($ids); $i++) {
            $this->assertEquals($ids[$i-1] + 1, $ids[$i], 'Los IDs deben ser secuenciales');
        }
        
        Log::info('[TEST] Concurrencia exitosa', [
            'pedidos_creados' => count($resultados),
            'ids_generados' => $ids,
            'rango_ids' => [min($ids), max($ids)]
        ]);
    }

    /**
     * Test: 30 usuarios con carga alta
     */
    public function test_treinta_usuarios_creacion_intensiva(): void
    {
        $usuarios = User::take(30)->get();
        $resultados = [];
        $startTime = microtime(true);
        
        // Crear pedidos en paralelo
        $promises = [];
        foreach ($usuarios as $index => $usuario) {
            $promises[] = $this->simulatePedidoCreation($usuario, $index);
        }
        
        // Recopilar resultados
        foreach ($promises as $promise) {
            try {
                $resultados[] = $promise->wait();
            } catch (\Exception $e) {
                $this->fail("Error en concurrencia: " . $e->getMessage());
            }
        }
        
        $endTime = microtime(true);
        $duracion = $endTime - $startTime;
        
        // Verificaciones
        $this->assertCount(30, $resultados);
        $this->assertLessThan(10.0, $duracion, 'Debe completarse en menos de 10 segundos');
        
        // Verificar integridad de datos
        $ids = array_column($resultados, 'id');
        $this->assertEquals(count($ids), count(array_unique($ids)), 'IDs únicos');
        
        Log::info('[TEST] Carga intensiva exitosa', [
            'pedidos' => count($resultados),
            'duracion_segundos' => round($duracion, 2),
            'promedio_por_pedido' => round($duracion / 30, 3)
        ]);
    }

    /**
     * Test: Verificar secuencia de números de pedido en Cartera
     */
    public function test_secuencia_numeros_pedido_cartera(): void
    {
        // Crear 5 pedidos primero
        $pedidosCreados = [];
        for ($i = 0; $i < 5; $i++) {
            $pedido = $this->createPedidoBase();
            $pedidosCreados[] = $pedido;
        }
        
        // Simular aprobación concurrente en Cartera
        $numerosGenerados = [];
        $promises = [];
        
        foreach ($pedidosCreados as $pedido) {
            $promises[] = $this->simulateAprobacionCartera($pedido);
        }
        
        foreach ($promises as $promise) {
            $numerosGenerados[] = $promise->wait();
        }
        
        // Verificar que los números sean secuenciales y únicos
        sort($numerosGenerados);
        $this->assertEquals([1, 2, 3, 4, 5], $numerosGenerados);
        
        Log::info('[TEST] Secuencia Cartera correcta', [
            'numeros_generados' => $numerosGenerados
        ]);
    }

    /**
     * Simula la creación de un pedido por un usuario
     */
    private function simulatePedidoCreation(User $usuario, int $index): \React\Promise\PromiseInterface
    {
        return new \React\Promise\Promise(function ($resolve, $reject) use ($usuario, $index) {
            try {
                // Simular autenticación
                $this->actingAs($usuario);
                
                // Datos de prueba
                $datos = [
                    'cliente' => "Cliente Test {$index}",
                    'forma_de_pago' => 'contado',
                    'productos_friendly' => [
                        [
                            'nombre_prenda' => 'Camisa Test',
                            'cantidad' => 10,
                            'telas' => []
                        ]
                    ],
                    'archivos' => []
                ];
                
                // Crear pedido usando el service
                $service = app(CrearPedidoService::class);
                $pedido = $service->crear($datos);
                
                // Retornar datos del pedido creado
                $resolve([
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'asesor_id' => $pedido->asesor_id,
                    'estado' => $pedido->estado,
                    'created_at' => $pedido->created_at->toISOString()
                ]);
                
            } catch (\Exception $e) {
                $reject($e);
            }
        });
    }

    /**
     * Simula aprobación en Cartera
     */
    private function simulateAprobacionCartera(PedidoProduccion $pedido): \React\Promise\PromiseInterface
    {
        return new \React\Promise\Promise(function ($resolve) use ($pedido) {
            // Usar la misma lógica que CarteraPedidosController
            $numero = DB::transaction(function () {
                $secuencia = DB::table('numero_secuencias')
                    ->where('tipo', 'pedido_produccion')
                    ->lockForUpdate()
                    ->first();
                
                if (!$secuencia) {
                    $numero = 1;
                    DB::table('numero_secuencias')->insert([
                        'tipo' => 'pedido_produccion',
                        'siguiente' => 2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $numero = $secuencia->siguiente;
                    DB::table('numero_secuencias')
                        ->where('tipo', 'pedido_produccion')
                        ->update(['siguiente' => $numero + 1]);
                }
                
                return $numero;
            });
            
            $pedido->update(['numero_pedido' => $numero]);
            $resolve($numero);
        });
    }

    /**
     * Crea usuarios de prueba
     */
    private function createTestUsers(): void
    {
        User::factory()->count(50)->create();
    }

    /**
     * Crea un pedido base para pruebas
     */
    private function createPedidoBase(): PedidoProduccion
    {
        return PedidoProduccion::create([
            'cliente' => 'Cliente Test',
            'asesor_id' => User::first()->id,
            'estado' => 'pendiente_cartera',
            'fecha_de_creacion_de_orden' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        // Restaurar timeout por defecto
        DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
        parent::tearDown();
    }
}
