<?php

namespace Tests\Feature\ControlCalidad;

use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaReciboCompletado;
use App\Models\PrendaReciboCompletadoTalla;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ControlCalidadDashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_no_muestra_recibo_cuando_todas_sus_tallas_ya_fueron_completadas(): void
    {
        $user = User::factory()->create();
        $pedido = null;
        $prenda = null;
        $recibo = null;
        $completado = null;

        Model::withoutEvents(function () use ($user, &$pedido, &$prenda, &$recibo, &$completado) {
            $pedido = PedidoProduccion::factory()->create([
                'numero_pedido' => '99191' . random_int(100, 999),
            ]);

            $prenda = PrendaPedido::factory()->create([
                'pedido_produccion_id' => $pedido->id,
                'nombre_prenda' => 'GORRO ANTIFLUIDO NEGRO',
                'descripcion' => 'GORRO EN TELA ANTIFLUIDO MUNDIAL NEGRO TIPO PIRATA DE AMARRAR ATRAS',
            ]);

            PrendaPedidoTalla::create([
                'prenda_pedido_id' => $prenda->id,
                'genero' => 'CABALLERO',
                'talla' => 'UNICA',
                'cantidad' => 2,
                'es_sobremedida' => false,
            ]);

            $recibo = ConsecutivoReciboPedido::create([
                'pedido_produccion_id' => $pedido->id,
                'prenda_id' => $prenda->id,
                'tipo_recibo' => 'COSTURA',
                'consecutivo_actual' => 49,
                'consecutivo_inicial' => 49,
                'activo' => 1,
                'estado' => 'En Ejecución',
                'area' => 'Control de Calidad',
            ]);

            $completado = PrendaReciboCompletado::create([
                'id_recibo' => $recibo->id,
                'area' => 'Control de Calidad',
                'numero_recibo' => 49,
                'nombre_operario' => $user->name,
                'fecha_completado' => now(),
            ]);

            PrendaReciboCompletadoTalla::create([
                'prenda_recibo_completado_id' => $completado->id,
                'talla' => 'UNICA',
                'cantidad' => 2,
                'genero' => 'CABALLERO',
                'color_nombre' => null,
            ]);
        });

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get(route('control-calidad.dashboard'));

        $response->assertOk();
        $response->assertSee('No hay pedidos en Control de Calidad');
        $response->assertDontSee('#49');
        $response->assertDontSee('GORRO ANTIFLUIDO NEGRO');
    }
}
