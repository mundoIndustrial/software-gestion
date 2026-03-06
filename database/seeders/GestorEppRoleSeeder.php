<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GestorEppRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol de Gestor EPP
        DB::table('roles')->insert([
            'name' => 'gestor_epp',
            'description' => 'Gestor de EPP - Solo puede acceder a la gestión de EPPs',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Rol "gestor_epp" creado exitosamente');
    }
}
