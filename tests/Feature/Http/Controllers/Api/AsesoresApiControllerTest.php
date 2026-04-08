<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Application\Pedidos\UseCases\MarcarNotificacionLeidaUseCase;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\Pedidos\UseCases\ObtenerNotificacionesUseCase;
use App\Application\Services\Asesores\ObservacionesDespachoApplicationService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class AsesoresApiControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_is_redirected_from_asesores_api(): void
    {
        $response = $this->get('/api/asesores/pendientes-asesor');

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_allowed_role_cannot_access_asesores_api(): void
    {
        $operario = $this->createUserWithRole('operario');

        $this->actingAs($operario, 'web')
            ->getJson('/api/asesores/pendientes-asesor?page=1&per_page=10')
            ->assertForbidden();
    }

    public function test_asesor_can_access_notifications_endpoints(): void
    {
        $asesor = $this->createUserWithRole('asesor');

        $getNotificationsMock = Mockery::mock(ObtenerNotificacionesUseCase::class);
        $getNotificationsMock->shouldReceive('ejecutar')
            ->once()
            ->andReturn([
                'total_notificaciones' => 2,
                'notificaciones_fecha_estimada' => [],
                'pedidos_otros_asesores' => [],
            ]);
        $this->app->instance(ObtenerNotificacionesUseCase::class, $getNotificationsMock);

        $markReadMock = Mockery::mock(MarcarNotificacionLeidaUseCase::class);
        $markReadMock->shouldReceive('ejecutar')
            ->twice()
            ->andReturn(['success' => true]);
        $this->app->instance(MarcarNotificacionLeidaUseCase::class, $markReadMock);

        $this->actingAs($asesor, 'web')
            ->getJson('/api/asesores/notificaciones')
            ->assertOk()
            ->assertJsonPath('total_notificaciones', 2);

        $this->actingAs($asesor, 'web')
            ->postJson('/api/asesores/notificaciones/marcar-todas-leidas')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($asesor, 'web')
            ->postJson('/api/asesores/notificaciones/123/marcar-leida')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_asesor_can_access_observaciones_read_and_mark_endpoints(): void
    {
        $asesor = $this->createUserWithRole('asesor');

        $serviceMock = Mockery::mock(ObservacionesDespachoApplicationService::class);
        $serviceMock->shouldReceive('validarAccesoPedidoPorId')
            ->times(3)
            ->andReturn(55);
        $serviceMock->shouldReceive('obtenerObservacionesUnificadas')
            ->once()
            ->with(55)
            ->andReturn([]);
        $serviceMock->shouldReceive('obtenerResumen')
            ->once()
            ->andReturn(['55' => ['unread' => 1]]);
        $serviceMock->shouldReceive('marcarDespachoLeidas')
            ->once()
            ->with(55);
        $serviceMock->shouldReceive('marcarBodegaVistas')
            ->once()
            ->with(55);
        $this->app->instance(ObservacionesDespachoApplicationService::class, $serviceMock);

        $this->actingAs($asesor, 'web')
            ->getJson('/api/asesores/pedidos/55/observaciones-despacho')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);

        $this->actingAs($asesor, 'web')
            ->postJson('/api/asesores/pedidos/observaciones-despacho/resumen', [
                'pedido_ids' => [55],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.55.unread', 1);

        $this->actingAs($asesor, 'web')
            ->postJson('/api/asesores/pedidos/55/observaciones-despacho/marcar-leidas')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($asesor, 'web')
            ->postJson('/api/asesores/pedidos/55/observaciones-despacho/marcar-bodega-vistas')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_asesor_can_access_factura_datos_endpoint(): void
    {
        $asesor = $this->createUserWithRole('asesor');

        $facturaMock = Mockery::mock(ObtenerFacturaUseCase::class);
        $facturaMock->shouldReceive('ejecutar')
            ->once()
            ->andReturn([
                'id' => 55,
                'numero_pedido' => 'PED-55',
                'cliente' => 'Cliente Test',
                'prendas' => [],
            ]);
        $this->app->instance(ObtenerFacturaUseCase::class, $facturaMock);

        $this->actingAs($asesor, 'web')
            ->getJson('/api/asesores/pedidos/55/factura-datos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 55)
            ->assertJsonPath('data.numero_pedido', 'PED-55');
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['description' => 'Rol de prueba', 'requires_credentials' => false]
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'roles_ids' => [$role->id],
        ]);
    }
}
