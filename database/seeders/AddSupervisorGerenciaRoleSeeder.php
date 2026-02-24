<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddSupervisorGerenciaRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si el rol ya existe
        $exists = DB::table('roles')->where('name', 'supervisor_gerencia')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'supervisor_gerencia',
                'description' => 'Supervisor Gerencia - Acceso a gestión de bodega, despacho, pedidos, entregas y costura',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ Rol supervisor_gerencia agregado exitosamente');
        } else {
            $this->command->warn('⚠ El rol supervisor_gerencia ya existe');
        }
    }
}
