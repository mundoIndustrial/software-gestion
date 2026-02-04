<?php

namespace Database\Seeders;

use App\Models\ReciboPrenda;
use App\Models\Asesor;
use App\Models\Empresa;
use App\Models\Articulo;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReciboPrendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener datos existentes o crear
        $asesores = Asesor::limit(5)->pluck('id')->toArray();
        $empresas = Empresa::limit(5)->pluck('id')->toArray();
        $articulos = Articulo::limit(10)->pluck('id')->toArray();

        if (empty($asesores)) {
            $this->command->warn('No se encontraron asesores. Ejecuta primero: php artisan db:seed AsesorsSeeder');
            return;
        }

        // Datos de ejemplo
        $datos = [
            [
                'numero_pedido' => '1029',
                'cantidad' => 6,
                'observaciones' => null,
                'fecha_entrega' => null,
                'estado' => 'pendiente',
            ],
            [
                'numero_pedido' => '1027',
                'cantidad' => 24,
                'observaciones' => null,
                'fecha_entrega' => null,
                'estado' => 'pendiente',
            ],
            [
                'numero_pedido' => '1026',
                'cantidad' => 0,
                'observaciones' => null,
                'fecha_entrega' => Carbon::parse('2024-02-04'),
                'estado' => 'entregado',
            ],
            [
                'numero_pedido' => '1025',
                'cantidad' => 12,
                'observaciones' => 'Pendiente ajuste en talla',
                'fecha_entrega' => Carbon::parse('2026-01-20'),
                'estado' => 'retrasado',
            ],
            [
                'numero_pedido' => '1024',
                'cantidad' => 8,
                'observaciones' => 'Conforme',
                'fecha_entrega' => Carbon::parse('2026-02-10'),
                'estado' => 'pendiente',
            ],
            [
                'numero_pedido' => '1023',
                'cantidad' => 15,
                'observaciones' => null,
                'fecha_entrega' => Carbon::parse('2026-02-08'),
                'estado' => 'pendiente',
            ],
        ];

        foreach ($datos as $dato) {
            $asesor = $asesores[array_rand($asesores)];
            $empresa = $empresas[array_rand($empresas)];
            $articulo = $articulos[array_rand($articulos)];

            ReciboPrenda::create([
                'numero_pedido' => $dato['numero_pedido'],
                'asesor_id' => $asesor,
                'empresa_id' => $empresa,
                'articulo_id' => $articulo,
                'cantidad' => $dato['cantidad'],
                'observaciones' => $dato['observaciones'],
                'fecha_entrega' => $dato['fecha_entrega'],
                'estado' => $dato['estado'],
                'fecha_entrega_real' => $dato['estado'] === 'entregado' ? now() : null,
            ]);

            // Crear 2-3 items más por pedido
            for ($i = 0; $i < rand(1, 2); $i++) {
                ReciboPrenda::create([
                    'numero_pedido' => $dato['numero_pedido'],
                    'asesor_id' => $asesor,
                    'empresa_id' => $empresa,
                    'articulo_id' => $articulos[array_rand($articulos)],
                    'cantidad' => rand(5, 20),
                    'observaciones' => null,
                    'fecha_entrega' => $dato['fecha_entrega'],
                    'estado' => $dato['estado'],
                    'fecha_entrega_real' => $dato['estado'] === 'entregado' ? now() : null,
                ]);
            }
        }

        $this->command->info('ReciboPrenda seeder ejecutado exitosamente.');
    }
}
