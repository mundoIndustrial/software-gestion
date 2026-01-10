<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSupervisorPersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insertar el rol supervisor-personal si no existe
        DB::table('roles')->insertOrIgnore([
            'name' => 'supervisor-personal',
            'description' => 'Supervisor encargado de la gestión de asistencia personal',
            'requires_credentials' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✓ Rol 'supervisor-personal' insertado correctamente.\n";
    }
}
