<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class SupervisorAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear solo el rol supervisor-admin si no existe
        Role::firstOrCreate(
            ['name' => 'supervisor-admin'],
            [
                'description' => 'Supervisor Administrador - Gestión de cotizaciones y reportes',
                'requires_credentials' => true,
            ]
        );

        echo " Rol 'supervisor-admin' creado exitosamente.\n";
    }
}
