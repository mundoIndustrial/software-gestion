<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prenda;
use App\Models\Balanceo;
use App\Models\OperacionBalanceo;

class BalanceoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear prenda de ejemplo: Camisa Polo
        $prenda = Prenda::create([
            'nombre' => 'Camisa Polo Básica',
            'descripcion' => 'Camisa polo de manga corta con cuello y botones frontales',
            'referencia' => 'POLO-001',
            'tipo' => 'polo',
            'activo' => true,
        ]);

        // Crear balanceo para la prenda
        $balanceo = Balanceo::create([
            'prenda_id' => $prenda->id,
            'version' => '1.0',
            'total_operarios' => 10,
            'turnos' => 1,
            'horas_por_turno' => 8.00,
            'activo' => true,
        ]);

        // Crear operaciones de ejemplo
        $operaciones = [
            ['letra' => 'A', 'operacion' => 'Filetear vista x2', 'maquina' => 'FL', 'sam' => 4.8, 'operario' => 'LEONARDO', 'op' => 'op1', 'seccion' => 'DEL', 'orden' => 1],
            ['letra' => 'B', 'operacion' => 'Pegar vista a delantero x2', 'maquina' => 'PL', 'sam' => 12.5, 'operario' => 'LEONARDO', 'op' => 'op1', 'seccion' => 'DEL', 'orden' => 2],
            ['letra' => 'C', 'operacion' => 'Pespunte vista x2', 'maquina' => '2 AG 1/4', 'sam' => 15.2, 'operario' => 'FELIPE', 'op' => 'op2', 'seccion' => 'DEL', 'orden' => 3],
            ['letra' => 'D', 'operacion' => 'Cerrar hombros', 'precedencia' => 'C', 'maquina' => 'FL', 'sam' => 8.3, 'operario' => 'DIEGO', 'op' => 'op3', 'seccion' => 'ENS', 'orden' => 4],
            ['letra' => 'E', 'operacion' => 'Pegar cuello', 'precedencia' => 'D', 'maquina' => 'CRR', 'sam' => 18.7, 'operario' => 'MARIA', 'op' => 'op4', 'seccion' => 'ENS', 'orden' => 5],
            ['letra' => 'F', 'operacion' => 'Pespunte cuello', 'precedencia' => 'E', 'maquina' => '2 AG 1/4', 'sam' => 16.4, 'operario' => 'MARIA', 'op' => 'op4', 'seccion' => 'ENS', 'orden' => 6],
            ['letra' => 'G', 'operacion' => 'Cerrar costados', 'maquina' => 'FL', 'sam' => 10.5, 'operario' => 'CARLOS', 'op' => 'op5', 'seccion' => 'ENS', 'orden' => 7],
            ['letra' => 'H', 'operacion' => 'Dobladillo manga x2', 'maquina' => 'PRET', 'sam' => 14.8, 'operario' => 'ANA', 'op' => 'op6', 'seccion' => 'ENS', 'orden' => 8],
            ['letra' => 'I', 'operacion' => 'Dobladillo ruedo', 'precedencia' => 'G', 'maquina' => 'PRET', 'sam' => 12.6, 'operario' => 'ANA', 'op' => 'op6', 'seccion' => 'ENS', 'orden' => 9],
            ['letra' => 'J', 'operacion' => 'Pegar botones x3', 'maquina' => 'PRES', 'sam' => 9.2, 'operario' => 'LUIS', 'op' => 'op7', 'seccion' => 'DEL', 'orden' => 10],
            ['letra' => 'K', 'operacion' => 'Hacer ojales x3', 'maquina' => 'PRES', 'sam' => 8.5, 'operario' => 'LUIS', 'op' => 'op7', 'seccion' => 'DEL', 'orden' => 11],
            ['letra' => 'L', 'operacion' => 'Inspección final', 'sam' => 5.3, 'operario' => 'SOFIA', 'op' => 'op8', 'seccion' => 'OTRO', 'orden' => 12],
        ];

        foreach ($operaciones as $op) {
            $op['balanceo_id'] = $balanceo->id;
            $op['operario_a'] = $op['operario'];
            OperacionBalanceo::create($op);
        }

        // Calcular métricas del balanceo
        $balanceo->calcularMetricas();

        // Crear otra prenda de ejemplo: Pantalón
        $prenda2 = Prenda::create([
            'nombre' => 'Pantalón Jean Clásico',
            'descripcion' => 'Pantalón jean de corte recto con 5 bolsillos',
            'referencia' => 'JEAN-001',
            'tipo' => 'pantalon',
            'activo' => true,
        ]);

        $balanceo2 = Balanceo::create([
            'prenda_id' => $prenda2->id,
            'version' => '1.0',
            'total_operarios' => 12,
            'turnos' => 1,
            'horas_por_turno' => 8.00,
            'activo' => true,
        ]);

        $operaciones2 = [
            ['letra' => 'A', 'operacion' => 'Cerrar entrepierna', 'maquina' => 'FL', 'sam' => 11.2, 'operario' => 'PEDRO', 'op' => 'op1', 'seccion' => 'ENS', 'orden' => 1],
            ['letra' => 'B', 'operacion' => 'Pegar bolsillos traseros x2', 'maquina' => 'PL', 'sam' => 22.5, 'operario' => 'JUAN', 'op' => 'op2', 'seccion' => 'TRAS', 'orden' => 2],
            ['letra' => 'C', 'operacion' => 'Pegar bolsillos delanteros x2', 'maquina' => 'PL', 'sam' => 20.3, 'operario' => 'JUAN', 'op' => 'op2', 'seccion' => 'DEL', 'orden' => 3],
            ['letra' => 'D', 'operacion' => 'Cerrar costados', 'maquina' => 'FL', 'sam' => 13.7, 'operario' => 'ROSA', 'op' => 'op3', 'seccion' => 'ENS', 'orden' => 4],
            ['letra' => 'E', 'operacion' => 'Pegar pretina', 'precedencia' => 'D', 'maquina' => 'PL', 'sam' => 25.8, 'operario' => 'CARMEN', 'op' => 'op4', 'seccion' => 'ENS', 'orden' => 5],
            ['letra' => 'F', 'operacion' => 'Pegar cierre', 'maquina' => 'PL', 'sam' => 18.4, 'operario' => 'LAURA', 'op' => 'op5', 'seccion' => 'DEL', 'orden' => 6],
            ['letra' => 'G', 'operacion' => 'Dobladillo piernas x2', 'maquina' => 'PRET', 'sam' => 16.9, 'operario' => 'ELENA', 'op' => 'op6', 'seccion' => 'ENS', 'orden' => 7],
            ['letra' => 'H', 'operacion' => 'Pegar botón', 'maquina' => 'PRES', 'sam' => 6.5, 'operario' => 'JORGE', 'op' => 'op7', 'seccion' => 'DEL', 'orden' => 8],
            ['letra' => 'I', 'operacion' => 'Hacer ojal', 'maquina' => 'PRES', 'sam' => 7.2, 'operario' => 'JORGE', 'op' => 'op7', 'seccion' => 'DEL', 'orden' => 9],
            ['letra' => 'J', 'operacion' => 'Inspección y empaque', 'sam' => 8.5, 'operario' => 'PATRICIA', 'op' => 'op8', 'seccion' => 'OTRO', 'orden' => 10],
        ];

        foreach ($operaciones2 as $op) {
            $op['balanceo_id'] = $balanceo2->id;
            $op['operario_a'] = $op['operario'];
            OperacionBalanceo::create($op);
        }

        $balanceo2->calcularMetricas();

        $this->command->info('✓ Datos de ejemplo de balanceo creados exitosamente');
        $this->command->info('  - 2 prendas creadas');
        $this->command->info('  - 2 balanceos con operaciones completas');
    }
}
