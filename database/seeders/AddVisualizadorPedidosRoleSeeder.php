<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddVisualizadorPedidosRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si el rol ya existe
        $exists = DB::table('roles')->where('name', 'visualizador-pedidos')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'visualizador-pedidos',
                'description' => 'Visualizador de Pedidos - Solo puede visualizar el módulo de pedidos',
                'requires_credentials' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ Rol visualizador-pedidos agregado exitosamente');
        } else {
            $this->command->warn('⚠ El rol visualizador-pedidos ya existe');
        }
    }
}
