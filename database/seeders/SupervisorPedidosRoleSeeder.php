<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupervisorPedidosRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol supervisor_pedidos si no existe
        $roleExists = DB::table('roles')
            ->where('name', 'supervisor_pedidos')
            ->exists();

        if (!$roleExists) {
            DB::table('roles')->insert([
                'name' => 'supervisor_pedidos',
                'description' => 'Supervisor de Pedidos de Producción',
                'requires_credentials' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info(' Rol "supervisor_pedidos" creado exitosamente.');
        } else {
            $this->command->warn('  El rol "supervisor_pedidos" ya existe.');
        }
    }
}
