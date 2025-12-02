<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddSupervisorPlantaRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si el rol ya existe
        $exists = DB::table('roles')->where('name', 'supervisor_planta')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'supervisor_planta',
                'description' => 'Supervisor de Planta - Gestión de órdenes, entregas, tableros, balanceo, vistas e insumos',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✅ Rol supervisor_planta agregado exitosamente');
        } else {
            $this->command->warn('⚠️ El rol supervisor_planta ya existe');
        }
    }
}
