<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddVisualizadorPlooterRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exists = DB::table('roles')->where('name', 'visualizador_plooter')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'visualizador_plooter',
                'description' => 'Visualizador de Plooter - Solo puede ver el registro de plooter (solo lectura)',
                'requires_credentials' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info(' Rol "visualizador_plooter" creado exitosamente');
        } else {
            $this->command->warn('⚠️ El rol "visualizador_plooter" ya existe');
        }
    }
}
