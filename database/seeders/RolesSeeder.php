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
                'name' => 'asesor',
                'description' => 'Asesor de ventas - Gestión de órdenes',
                'requires_credentials' => true,
            ],
            [
                'name' => 'contador',
                'description' => 'Contador - Gestión de cotizaciones',
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
                'name' => 'supervisor-admin',
                'description' => 'Supervisor Administrador - Gestión de cotizaciones y reportes',
                'requires_credentials' => true,
            ],
            [
                'name' => 'admin',
                'description' => 'Administrador del sistema',
                'requires_credentials' => true,
            ],
            [
                'name' => 'patronista',
                'description' => 'Patronista - Visualización de insumos (solo lectura)',
                'requires_credentials' => true,
            ],
            [
                'name' => 'supervisor_asesores',
                'description' => 'Supervisor de Asesores - Gestión de cotizaciones y pedidos de todos los asesores',
                'requires_credentials' => true,
            ],
            [
                'name' => 'costurero',
                'description' => 'Operario de costura',
                'requires_credentials' => false,
            ],
            [
                'name' => 'diseñador-logos',
                'description' => 'Diseñador Logos - Solo ve Pedidos Logo en área DISEÑO',
                'requires_credentials' => true,
            ],
            [
                'name' => 'bordador',
                'description' => 'Bordador - Operario que borda el diseño/logo en la prenda (solo ve Pedidos Logo en área BORDANDO)',
                'requires_credentials' => true,
            ],
            [
                'name' => 'control de calidad',
                'description' => 'encargado de gestion de entregas de prendas control de calidad',
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
