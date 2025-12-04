<?php

namespace Tests\Feature;

use App\Events\PedidoCreado;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Models\Role;
use App\Notifications\PedidoCreado as NotificacionPedidoCreado;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificacionesPedidoTest extends TestCase
{
    /**
     * Test que verifica que se dispara el evento al crear un pedido
     */
    public function test_evento_pedido_creado_se_dispara()
    {
        Event::fake();

        // Crear un asesor
        $asesor = User::factory()->create(['roles_ids' => [1]]);

        // Crear un pedido manualmente
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'PED-' . time(),
            'numero_cotizacion' => 'COT-001',
            'cliente' => 'Cliente Test',
            'asesor_id' => $asesor->id,
            'forma_de_pago' => 'Crédito',
            'estado' => 'No iniciado',
            'fecha_de_creacion_de_orden' => now()->toDateString(),
        ]);

        // Verificar que el evento fue disparado
        Event::assertDispatched(PedidoCreado::class, function ($event) use ($pedido, $asesor) {
            return $event->pedido->id === $pedido->id &&
                   $event->asesor->id === $asesor->id;
        });
    }

    /**
     * Test que verifica que se envía notificación a supervisores
     */
    public function test_notificacion_enviada_a_supervisores()
    {
        Notification::fake();

        // Crear rol supervisor si no existe
        $rolSupervisor = Role::firstOrCreate(
            ['nombre_rol' => 'supervisor_pedido'],
            ['nombre_rol' => 'supervisor_pedido', 'descripcion' => 'Supervisor de Pedidos']
        );

        // Crear supervisores
        $supervisor1 = User::factory()->create(['roles_ids' => [$rolSupervisor->id]]);
        $supervisor2 = User::factory()->create(['roles_ids' => [$rolSupervisor->id]]);

        // Crear asesor
        $asesor = User::factory()->create(['roles_ids' => [1]]);

        // Crear un pedido
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'PED-' . time(),
            'numero_cotizacion' => 'COT-001',
            'cliente' => 'Cliente Test',
            'asesor_id' => $asesor->id,
            'forma_de_pago' => 'Crédito',
            'estado' => 'No iniciado',
            'fecha_de_creacion_de_orden' => now()->toDateString(),
        ]);

        // Verificar que se enviaron notificaciones a los supervisores
        Notification::assertSentTo([$supervisor1, $supervisor2], NotificacionPedidoCreado::class);
    }
}
