<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrador del sistema',
                'requires_credentials' => true,
            ],
            [
                'name' => 'operador',
                'description' => 'Operador de producción',
                'requires_credentials' => true,
            ],
            [
                'name' => 'cortador',
                'description' => 'Operario de piso de corte',
                'requires_credentials' => false,
            ],
            [
                'name' => 'supervisor',
                'description' => 'Supervisor de gestión de órdenes (solo lectura)',
                'requires_credentials' => true,
            ],
            [
                'name' => 'supervisor_planta',
                'description' => 'Supervisor de planta (acceso completo + insumos)',
                'requires_credentials' => true,
            ],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(
                ['name' => $role['name']],
                [
                    'description' => $role['description'],
                    'requires_credentials' => $role['requires_credentials'],
                ]
            );
        }
    }
}
