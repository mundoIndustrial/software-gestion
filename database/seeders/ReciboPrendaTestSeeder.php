<?php

namespace Database\Seeders;

use App\Models\ReciboPrenda;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReciboPrendaSeeder extends Seeder
{
    public function run(): void
    {
        // Datos de prueba para bodeguero
        $pedidos = [
            [
                'numero_pedido' => 'PED-2026-001',
                'asesor_id' => 1,
                'empresa_id' => 1,
                'articulo_id' => 1,
                'cantidad' => 5,
                'observaciones' => 'Prenda de prueba - Pendiente entrega',
                'fecha_entrega' => Carbon::now()->addDays(2),
                'fecha_entrega_real' => null,
                'estado' => 'pendiente',
                'usuario_bodeguero_id' => null,
            ],
            [
                'numero_pedido' => 'PED-2026-001',
                'asesor_id' => 1,
                'empresa_id' => 1,
                'articulo_id' => 2,
                'cantidad' => 3,
                'observaciones' => 'Segunda prenda del mismo pedido',
                'fecha_entrega' => Carbon::now()->addDays(2),
                'fecha_entrega_real' => null,
                'estado' => 'pendiente',
                'usuario_bodeguero_id' => null,
            ],
            [
                'numero_pedido' => 'PED-2026-002',
                'asesor_id' => 2,
                'empresa_id' => 2,
                'articulo_id' => 3,
                'cantidad' => 10,
                'observaciones' => 'Pedido entregado correctamente',
                'fecha_entrega' => Carbon::now()->subDays(3),
                'fecha_entrega_real' => Carbon::now()->subDays(2),
                'estado' => 'entregado',
                'usuario_bodeguero_id' => 2,
            ],
            [
                'numero_pedido' => 'PED-2026-003',
                'asesor_id' => 1,
                'empresa_id' => 3,
                'articulo_id' => 1,
                'cantidad' => 7,
                'observaciones' => 'Pedido retrasado - Requiere seguimiento',
                'fecha_entrega' => Carbon::now()->subDays(5),
                'fecha_entrega_real' => null,
                'estado' => 'retrasado',
                'usuario_bodeguero_id' => null,
            ],
            [
                'numero_pedido' => 'PED-2026-004',
                'asesor_id' => 3,
                'empresa_id' => 1,
                'articulo_id' => 2,
                'cantidad' => 4,
                'observaciones' => 'Pedido nuevo en bodega',
                'fecha_entrega' => Carbon::now()->addDays(7),
                'fecha_entrega_real' => null,
                'estado' => 'pendiente',
                'usuario_bodeguero_id' => null,
            ],
        ];

        foreach ($pedidos as $pedido) {
            ReciboPrenda::create($pedido);
        }

        $this->command->info('ReciboPrendaSeeder ejecutado exitosamente. ' . count($pedidos) . ' registros creados.');
    }
}
