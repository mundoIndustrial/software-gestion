<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarteraRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea el rol 'cartera' para usuarios que aprueban/rechazan pedidos
     */
    public function run(): void
    {
        // Verificar si el rol ya existe
        $exists = DB::table('roles')->where('name', 'cartera')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'cartera',
                'description' => 'Rol de Cartera - Aprueba y rechaza pedidos en estado Pendiente cartera',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info(' Rol "cartera" creado exitosamente.');
        } else {
            $this->command->warn('  El rol "cartera" ya existe.');
        }
    }
}
