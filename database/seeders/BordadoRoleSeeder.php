<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BordadoRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea el rol 'bordado' para usuarios que trabajan en el módulo de Bordado
     */
    public function run(): void
    {
        // Verificar si el rol ya existe
        $exists = DB::table('roles')->where('name', 'bordado')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'bordado',
                'description' => 'Rol de Bordado - Acceso a la cartera de pedidos del módulo de Bordado',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info(' Rol "bordado" creado exitosamente.');
        } else {
            $this->command->warn('  El rol "bordado" ya existe.');
        }
    }
}
