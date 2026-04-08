<?php

namespace Tests\Unit\Application\Operario\UseCases;

use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Application\Operario\DTOs\ObtenerPedidosOperarioDTO;
use App\Application\Operario\UseCases\GetOperarioDashboardUseCase;
use App\Domain\Operario\Services\OperarioDashboardReadService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery as m;
use Tests\TestCase;

class GetOperarioDashboardUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_marca_recibos_completados_por_area_del_operario(): void
    {
        $user = new class extends User {
            public array $roleMap = [];
            public array $anyRoleMap = [];

            public function hasRole($roles, ?string $guard = null): bool
            {
                $key = is_array($roles) ? implode('|', $roles) : (string) $roles;
                return (bool) ($this->roleMap[$key] ?? false);
            }

            public function hasAnyRole($roles, ?string $guard = null): bool
            {
                $key = is_array($roles) ? implode('|', $roles) : (string) $roles;
                return (bool) ($this->anyRoleMap[$key] ?? false);
            }
        };
        $user->forceFill(['id' => 5, 'name' => 'Operario']);
        $user->roleMap = [
            'administrador-costura' => false,
            'cortador' => false,
            'vista-costura' => false,
        ];
        $user->anyRoleMap = [
            'costurero|confeccion-sobremedida' => true,
        ];

        Auth::shouldReceive('user')->once()->andReturn($user);

        $prendasService = m::mock(ObtenerPrendasRecibosService::class);
        $pedidosService = m::mock(ObtenerPedidosOperarioService::class);
        $dashboardReadService = m::mock(OperarioDashboardReadService::class);

        $prendasService
            ->shouldReceive('obtenerPrendasConRecibos')
            ->once()
            ->with($user)
            ->andReturn(collect([
                [
                    'prenda_id' => 10,
                    'recibos' => [
                        ['id' => 101, 'tipo_recibo' => 'COSTURA'],
                    ],
                ],
            ]));

        $dashboardReadService
            ->shouldReceive('obtenerCompletadosPorArea')
            ->once()
            ->with([101], 'Costura')
            ->andReturn(collect([101 => now()]));

        $pedidosService
            ->shouldReceive('obtenerPedidosDelOperario')
            ->once()
            ->with($user)
            ->andReturn(new ObtenerPedidosOperarioDTO(
                operarioId: 5,
                nombreOperario: 'Operario',
                tipoOperario: 'costurero',
                areaOperario: 'Costura',
                pedidos: [],
                totalPedidos: 1,
                pedidosEnProceso: 1,
                pedidosCompletados: 0
            ));

        $useCase = new GetOperarioDashboardUseCase($pedidosService, $prendasService, $dashboardReadService);
        $result = $useCase->execute(new Request());

        $this->assertTrue($result->prendasConRecibos[0]['recibos'][0]['completado_area']);
        $this->assertSame('costura', $result->tab);
    }
}
