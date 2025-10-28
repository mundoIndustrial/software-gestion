<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Maquina;

class MaquinasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maquinas = [
            ['nombre_maquina' => 'Máquina 1'],
            ['nombre_maquina' => 'Máquina 2'],
            ['nombre_maquina' => 'Máquina 3'],
            ['nombre_maquina' => 'Máquina 4'],
            ['nombre_maquina' => 'Máquina 5'],
        ];

        foreach ($maquinas as $maquina) {
            Maquina::create($maquina);
        }
    }
}
