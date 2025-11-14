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
        \App\Models\Role::create([
            'name' => 'admin',
            'description' => 'Administrador del sistema',
            'requires_credentials' => true,
        ]);

        \App\Models\Role::create([
            'name' => 'operador',
            'description' => 'Operador de producción',
            'requires_credentials' => true,
        ]);

        \App\Models\Role::create([
            'name' => 'cortador',
            'description' => 'Operario de piso de corte',
            'requires_credentials' => false,
        ]);

        \App\Models\Role::create([
            'name' => 'supervisor',
            'description' => 'Supervisor de gestión de órdenes (solo lectura)',
            'requires_credentials' => true,
        ]);
    }
}
