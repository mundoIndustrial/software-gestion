<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TiempoCiclo;

class TiempoCiclosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiempoCiclos = [
            ['tela_id' => 1, 'maquina_id' => 1, 'tiempo_ciclo' => 2.5],
            ['tela_id' => 1, 'maquina_id' => 2, 'tiempo_ciclo' => 3.0],
            ['tela_id' => 2, 'maquina_id' => 1, 'tiempo_ciclo' => 1.8],
            ['tela_id' => 2, 'maquina_id' => 2, 'tiempo_ciclo' => 2.2],
            ['tela_id' => 3, 'maquina_id' => 1, 'tiempo_ciclo' => 4.0],
            ['tela_id' => 3, 'maquina_id' => 2, 'tiempo_ciclo' => 4.5],
            ['tela_id' => 4, 'maquina_id' => 1, 'tiempo_ciclo' => 1.5],
            ['tela_id' => 4, 'maquina_id' => 2, 'tiempo_ciclo' => 1.7],
            ['tela_id' => 5, 'maquina_id' => 1, 'tiempo_ciclo' => 3.5],
            ['tela_id' => 5, 'maquina_id' => 2, 'tiempo_ciclo' => 3.8],
        ];

        foreach ($tiempoCiclos as $tiempoCiclo) {
            TiempoCiclo::create($tiempoCiclo);
        }
    }
}
