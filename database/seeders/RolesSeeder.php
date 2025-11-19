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
            'name' => 'asesor',
            'description' => 'Asesor de ventas - Gestión de órdenes',
            'requires_credentials' => true,
        ]);

        \App\Models\Role::create([
            'name' => 'contador',
            'description' => 'Contador - Gestión de cotizaciones',
            'requires_credentials' => true,
        ]);
    }
}
