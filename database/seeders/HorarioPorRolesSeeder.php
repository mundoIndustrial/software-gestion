<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HorarioPorRol;

class HorarioPorRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horarios = [
            [
                'id_rol' => 19, // produccion
                'entrada_manana' => '06:00:00',
                'salida_manana' => '12:00:00',
                'entrada_tarde' => '13:00:00',
                'salida_tarde' => '18:00:00',
            ],
            [
                'id_rol' => 20, // administrativo
                'entrada_manana' => '08:00:00',
                'salida_manana' => '12:00:00',
                'entrada_tarde' => '14:00:00',
                'salida_tarde' => '17:00:00',
            ],
            [
                'id_rol' => 21, // mixto
                'entrada_manana' => '07:00:00',
                'salida_manana' => '12:00:00',
                'entrada_tarde' => '13:30:00',
                'salida_tarde' => '18:00:00',
            ],
        ];

        foreach ($horarios as $horario) {
            HorarioPorRol::firstOrCreate(
                ['id_rol' => $horario['id_rol']],
                $horario
            );
        }
    }
}
