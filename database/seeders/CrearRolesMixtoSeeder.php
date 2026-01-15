<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CrearRolesMixtoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'produccion',
                'description' => 'Rol de Producción - Gestión de órdenes de producción',
                'requires_credentials' => true,
            ],
            [
                'name' => 'administrativo',
                'description' => 'Rol Administrativo - Gestión de tareas administrativas',
                'requires_credentials' => true,
            ],
            [
                'name' => 'mixto',
                'description' => 'Rol Mixto - Acceso a producción y tareas administrativas',
                'requires_credentials' => true,
            ],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
